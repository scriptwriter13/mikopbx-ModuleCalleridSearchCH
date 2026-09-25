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
use MikoPBX\Common\Models\SoundFiles;


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
 * Bereinigt einen Text (Name, Ort, Strasse etc.) universell für ältere IP-Telefone,
 * falls die Option transliterate_Specialchars aktiv ist.
 */

public static function cleanStringForPhone(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // 1. Prüfen ob die Option aktiv ist
        try {
            if (method_exists(self::class, 'shouldTransliterateSpecialchars') && !self::shouldTransliterateSpecialchars()) {
                return $text;
            }
        } catch (\Throwable $e) {
            // Ignorieren, im Zweifel fortfahren
        }

        // 2. Umfassendes Mapping für Umlaute und internationale Akzente (z.B. ç, é, à, ø etc.)
        $search = [
            'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß',
            'ç', 'Ç', 'é', 'è', 'ê', 'ë', 'É', 'È', 'Ê', 'Ë',
            'à', 'á', 'â', 'ã', 'å', 'À', 'Á', 'Â', 'Ã', 'Å',
            'ì', 'í', 'î', 'ï', 'Ì', 'Í', 'Î', 'Ï',
            'ò', 'ó', 'ô', 'õ', 'ø', 'Ò', 'Ó', 'Ô', 'Õ', 'Ø',
            'ù', 'ú', 'û', 'Ù', 'Ú', 'Û',
            'ñ', 'Ñ', 'ý', 'ÿ', 'Ý'
        ];
        
        $replace = [
            'ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss',
            'c', 'C', 'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E',
            'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A',
            'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I',
            'o', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'O',
            'u', 'u', 'u', 'U', 'U', 'U',
            'n', 'N', 'y', 'y', 'Y'
        ];

        $text = str_replace($search, $replace, $text);

        // 3. Fallback für alle restlichen Zeichen per iconv (optional)
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            // Entferne eventuell übrig gebliebene Fragezeichen oder unerwünschte Symbole
            $text = str_replace('?', '', $converted);
        }

        return $text;
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

            $name = self::cleanStringForPhone($value('tel:name'));
            $firstName = self::cleanStringForPhone($value('tel:firstname'));
            if ($name === '') {
                // Without an API key the directory fills the Atom title only.
                $name = $value('a:title');
            } elseif ($single && $firstName !== '') {
                $name .= ' ' . mb_substr($firstName, 0, 1) . '.';
            }
            if ($name !== '') {
                $names[] = $name;
            }

            $loc = self::cleanStringForPhone($value('tel:city'));
            $street = trim(self::cleanStringForPhone($value('tel:street')) . ' ' . $value('tel:streetno'));
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


    /** @var bool Tracks whether the last lookup identified a Call Center */
    private static bool $lastCallcenter = false;

    /**
     * Returns whether the last lookup identified a Call Center.
     *
     * @return bool true when the last entity was identified as a call center
     */
    public static function isLastCallcenter(): bool
    {
        return self::$lastCallcenter;
    }

    /**
     * Calls with 2 or fewer digits are treated as anonymous. Falls back to AGI channel data if empty.
     *
     * @param string|null $number Raw caller number string
     * @param \MikoPBX\Core\Asterisk\AGI|null $agi Active AGI instance for channel fallback
     * @return bool true when the caller ID has 2 or fewer digits
     */
    public static function isAnonymousCall(?string $number = null, ?\MikoPBX\Core\Asterisk\AGI $agi = null): bool
    {
        $numStr = trim($number ?? '');

        if ($numStr === '' && $agi !== null) {
            try {
                $res = $agi->get_variable('CALLERID(num)');
                if (is_array($res) && isset($res['data'])) {
                    $numStr = trim((string)$res['data']);
                }
            } catch (\Throwable) {
                // CLI ignorieren
            }
        }

        $digits = preg_replace('/[^\d]/', '', $numStr);
        
        // Mehr als 2 Stellen = nicht anonym (false), 2 oder weniger Stellen = anonym (true)
        return strlen($digits) <= 2;
    }

    /**
     * Retrieves all available custom sound files from the system database
     * and maps them into an associative array for use in form dropdowns.
     * 
     * Filters files by category CATEGORY_CUSTOM, extracts the base filename 
     * as the option key, and resolves a human-readable label (using name, 
     * description, or falling back to the filename).
     * 
     * @return array Associative array of [filename => display_label]
     */
     public static function getAvailableCustomSounds(): array {
        $options = ['' => '-- Keine Ansage (Standard: Busy) --'];
        
        try {
            if (class_exists(SoundFiles::class)) {
                $sounds = SoundFiles::find([
                    'category = :cat:',
                    'bind' => ['cat' => SoundFiles::CATEGORY_CUSTOM]
                ]);
                
                foreach ($sounds as $sound) {
                    $path = trim($sound->path ?? '');
                    if (empty($path)) {
                        continue;
                    }
                    
                    // Den reinen Dateinamen als sauberen Key für das Formular ermitteln (z.B. "test21")
                    $base = pathinfo($path, PATHINFO_FILENAME);
                    if (empty($base)) {
                        continue;
                    }
                    
                    // Sprechenden Namen oder Beschreibung bevorzugen, sonst Dateiname als Fallback
                    $label = !empty($sound->name) 
                        ? $sound->name 
                        : (!empty($sound->description) ? $sound->description : $base);
                    
                    // Key ist jetzt der kompakte Name (z.B. 'test21'), Value ist der schöne Name für den Admin
                    $options[$base] = $label;
                }
            }
        } catch (\Throwable $e) {
            // Fängt Ausnahmen ab
        }
        
        return $options;
    }



    /**
     * @return string|null null when there is nothing to show and CallerID must stay untouched
     * @throws RuntimeException when the directory cannot be reached or rejects the key
     */
    public static function lookup(string $number): ?string
    {  
	self::$lastCallcenter = false; 
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

	self::$lastCallcenter = self::isCallcenterXml($xml);

        return self::parseCallerName($xml);
    }

    /**
     * Checks whether the XML feed identifies the entity as a Call Center.
     *
     * @param string $xml Raw XML response payload
     * @return bool true when the category matches "call center"
     */
    private static function isCallcenterXml(string $xml): bool
    {
        $xpath = self::xpath($xml);
        if ($xpath === null) {
            return false;
        }
        foreach ($xpath->query('//tel:category') as $node) {
	   if (stripos(trim((string)$node->textContent), 'call center') !== false) {
                return true;
            }    
        }
        return false;
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
        return trim((string)ModuleCalleridSearchCH::findFirst()?->api_key);
    }

    /**
     * Checks whether call center dropping is enabled in the module settings.
     *
     * @return bool true when dropCallcenter is enabled ('1')
     */
    public static function shouldDropCallcenter(): bool
    {
        return ModuleCalleridSearchCH::findFirst()?->dropCallcenter === '1';
    }

    /**
     * Checks whether anonymous call dropping is enabled in the module settings.
     *
     * @return bool true when dropAnonymousCalls is enabled ('1')
     */
    public static function shouldDropAnonymousCalls(): bool
    {
        return ModuleCalleridSearchCH::findFirst()?->dropAnonymousCalls === '1';
    }

   /**
     * Checks whether transliteration of special characters is enabled in the module settings.
     *
     * @return bool true when transliterate_Specialchars is enabled ('1')
     */
    public static function shouldTransliterateSpecialchars(): bool
    {
        return ModuleCalleridSearchCH::findFirst()?->transliterate_Specialchars === '1';
    }
}
