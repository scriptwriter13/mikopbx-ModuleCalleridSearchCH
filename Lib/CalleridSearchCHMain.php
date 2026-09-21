<?php
/*
 * Copyright (C) 2026 by scriptwriter13
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <https://www.gnu.org/licenses/>.
 */


declare(strict_types=1);

namespace Modules\ModuleCalleridSearchCH\Lib;

use DOMDocument;
use DOMXPath;
use Modules\ModuleCalleridSearchCH\Models\ModuleCalleridSearchCH;
use RuntimeException;

/**
 * Caller name lookup in the tel.search.ch directory.
 */
class CalleridSearchCHMain
{
    private const string API_URL = 'https://tel.search.ch/api/';
    private const string TEL_NS = 'http://tel.search.ch/api/spec/result/1.0/';

    // The caller waits for the answer, so this is a hard budget for the whole request.
    private const int API_TIMEOUT = 2;

    // A CallerID that long is useless on a phone display and overflows CDR columns.
    private const int MAX_NAME_LENGTH = 80;

    /**
     * Converts +41..., 0041..., 41... and 0... to the national format the directory expects.
     *
     * @return string|null null when the number is not a Swiss one
     */
    public static function normalizeNumber(string $number): ?string
    {
        $digits = (string)preg_replace('/\D/', '', $number);
        if (str_starts_with($digits, '0041')) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '41') && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }

        return preg_match('/^0[1-9]\d{8}$/', $digits) === 1 ? $digits : null;
    }

    /**
     * Builds "Name F. City, Street 1" from the API answer.
     * The tel:* fields come only with an API key, the Atom title is always there.
     *
     * @return string|null null when the directory has no usable entry
     */
    public static function parseCallerName(string $xml): ?string
    {
        $xpath = self::xpath($xml);
        if ($xpath === null) {
            return null;
        }

        $entries = $xpath->query('//a:entry');
        // Several entries share one number: they have different addresses, so only the names are shown.
        $single = $entries->length === 1;
        $names = [];
        $locations = [];
        foreach ($entries as $entry) {
            $value = static fn(string $query): string => trim((string)$xpath->evaluate("string($query)", $entry));

            $name = $value('tel:name');
            $firstName = $value('tel:firstname');
            if ($name === '') {
                // Without an API key the directory fills the Atom title only.
                $name = $value('a:title');
            } elseif ($single && $firstName !== '') {
                $name .= ' ' . mb_substr($firstName, 0, 1) . '.';
            }
            if ($name !== '') {
                $names[] = $name;
            }

            $loc = $value('tel:city');
            $street = trim($value('tel:street') . ' ' . $value('tel:streetno'));
            if ($street !== '') {
                $loc = $loc === '' ? $street : $loc . ', ' . $street;
            }
            $locations[] = $loc;
        }
        if ($names === []) {
            return null;
        }

        $result = implode('/', array_unique($names));

        $location = '';
        $nonEmptyLocations = array_filter($locations, fn($l) => $l !== '');
        $uniqueLocations = array_unique($nonEmptyLocations);

        if ($single && count($uniqueLocations) > 0) {
            $location = reset($uniqueLocations);
        } elseif (!$single && count($uniqueLocations) === 1 && count($nonEmptyLocations) === $entries->length) {
            $location = reset($uniqueLocations);
        }

        if ($location !== '') {
            $result .= ' ' . $location;
        }

        // The value goes into an AGI command and SIP headers.
        $result = trim((string)preg_replace('/[\x00-\x1F\x7F"]+/u', ' ', $result));

        // An entry made only of stripped characters would otherwise wipe the CallerID.
        return $result === '' ? null : mb_substr($result, 0, self::MAX_NAME_LENGTH);
    }

    /**
     * The directory reports a rejected or exhausted key inside the feed, with HTTP 403.
     *
     * @return string|null null when the answer carries no error
     */
    public static function parseError(string $xml): ?string
    {
        $xpath = self::xpath($xml);
        if ($xpath === null) {
            return null;
        }
        $message = trim((string)$xpath->evaluate('string(//tel:errorMessage)'));

        return $message === '' ? null : $message;
    }

    /**
     * @return string|null null when there is nothing to show and CallerID must stay untouched
     * @throws RuntimeException when the directory cannot be reached or rejects the key
     */
    public static function lookup(string $number): ?string
    {
        $national = self::normalizeNumber($number);
        if ($national === null) {
            return null;
        }
        $query = ['was' => $national, 'lang' => 'de'];
        $apiKey = self::getApiKey();
        if ($apiKey !== '') {
            $query['key'] = $apiKey;
        }

        $xml = self::request(self::API_URL . '?' . http_build_query($query));
        $error = self::parseError($xml);
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        return self::parseCallerName($xml);
    }

    /**
     * curl is used rather than file_get_contents: the stream timeout bounds a single read,
     * so a server that keeps dripping bytes can hold the caller far longer than API_TIMEOUT.
     *
     * @throws RuntimeException
     */
    private static function request(string $url): string
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::API_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);

        if ($body === false) {
            throw new RuntimeException('tel.search.ch request failed: ' . $error);
        }

        return (string)$body;
    }

    private static function xpath(string $xml): ?DOMXPath
    {
        if ($xml === '') {
            return null;
        }
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml, LIBXML_NONET)) {
            return null;
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('a', 'http://www.w3.org/2005/Atom');
        $xpath->registerNamespace('tel', self::TEL_NS);

        return $xpath;
    }

    /**
     * The TEL_SEARCH_KEY environment variable stays as a fallback for installations made before the settings page.
     */
    private static function getApiKey(): string
    {
        $apiKey = trim((string)ModuleCalleridSearchCH::findFirst()?->api_key);

        return $apiKey !== '' ? $apiKey : trim((string)getenv('TEL_SEARCH_KEY'));
    }
}
