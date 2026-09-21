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

// Self-check without PBX and network: php tests/LookupTest.php

use Modules\ModuleCalleridSearchCH\Lib\CalleridSearchCHMain as Main;

require_once __DIR__ . '/../Lib/CalleridSearchCHMain.php';

function feed(string $entries): string
{
    return '<?xml version="1.0" encoding="utf-8" ?>'
        . '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:tel="http://tel.search.ch/api/spec/result/1.0/">'
        . $entries . '</feed>';
}

function check(mixed $expected, mixed $actual, string $case): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL $case: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$author = '<author><name>tel.search.ch</name></author>';
$person = "<entry><title>Muster, Hans</title>$author<tel:name>Muster</tel:name><tel:firstname>Hans</tel:firstname>"
    . '<tel:street>Mittelweg</tel:street><tel:streetno>13</tel:streetno><tel:city>Bern</tel:city></entry>';
$second = "<entry><title>Muster, Anna</title>$author<tel:name>Muster-Meier</tel:name><tel:firstname>Anna</tel:firstname>"
    . '<tel:city>Thun</tel:city></entry>';

check('0441234567', Main::normalizeNumber('0441234567'), 'national');
check('0441234567', Main::normalizeNumber('+41 44 123 45 67'), 'plus');
check('0441234567', Main::normalizeNumber('0041441234567'), 'double zero');
check('0441234567', Main::normalizeNumber('41441234567'), 'no prefix');
check(null, Main::normalizeNumber('+4930123456'), 'foreign');
check(null, Main::normalizeNumber('00000000'), 'anonymous');
check(null, Main::normalizeNumber('201'), 'internal');

check('Muster H. Bern, Mittelweg 13', Main::parseCallerName(feed($person)), 'single entry with key');
// Two entries live at different addresses, so neither address may be shown.
check('Muster/Muster-Meier', Main::parseCallerName(feed($person . $second)), 'two entries');
check('Muster Bern', Main::parseCallerName(feed(str_replace(['<tel:street>Mittelweg</tel:street>', '<tel:streetno>13</tel:streetno>', '<tel:firstname>Hans</tel:firstname>'], '', $person))), 'city without street');
check('Bidcars AG', Main::parseCallerName(feed("<entry><title>Bidcars AG</title>$author</entry>")), 'no key, title only');
check('Evil Name', Main::parseCallerName(feed("<entry><title>Evil\"\nName</title>$author</entry>")), 'control characters');
check(null, Main::parseCallerName(feed('')), 'no entries');
check(null, Main::parseCallerName('not xml'), 'broken answer');
check(null, Main::parseCallerName(''), 'empty answer');
// A name made only of stripped characters must not wipe the CallerID.
check(null, Main::parseCallerName(feed("<entry><title>\"\"\"</title>$author</entry>")), 'name reduces to nothing');

$long = str_repeat('Sehr Langer Firmenname AG ', 10);
$capped = Main::parseCallerName(feed("<entry><title>$long</title>$author</entry>"));
check(80, mb_strlen((string)$capped), 'long name is capped');

check(
    'The submitted API-Key is invalid or blocked',
    Main::parseError(feed('<tel:errorMessage>The submitted API-Key is invalid or blocked</tel:errorMessage>')),
    'api key error is reported'
);
check(null, Main::parseError(feed($person)), 'no error on a normal answer');
check(null, Main::parseError('not xml'), 'no error on a broken answer');

echo 'OK' . PHP_EOL;
