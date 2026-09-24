#!/usr/bin/php
<?php
/*
 * Copyright (C) 2026 by scriptwriter13
 *
 * Dieses Programm ist freie Software: Sie können es unter den Bedingungen der
 * GNU General Public License, wie von der Free Software Foundation veröffentlicht,
 * entweder Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren
 * Version, weiterverbreiten und/oder modifizieren.
 *
 * Dieses Programm wird in der Hoffnung, dass es nützlich sein wird, aber
 * OHNE JEDE GEWÄHRLEISTUNG, sogar ohne die implizite Gewährleistung der
 * MARKTGÄNGIGKEIT oder EIGNUNG FÜR EINEN BESTIMMTEN ZWECK. Siehe die
 * GNU General Public License für weitere Details.
 *
 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
 * Programm erhalten haben. Wenn nicht, siehe <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use MikoPBX\Core\Asterisk\AGI;
use Modules\ModuleCalleridSearchCH\Lib\CalleridSearchCHMain;

use Modules\ModuleCalleridSearchCH\Models\ModuleCalleridSearchCH;
use MikoPBX\Common\Models\SoundFiles;

require_once 'Globals.php';

$agi = new AGI();
$number = $argv[1] ?? '';

// Hilfsfunktion zum sicheren Abspielen der konfigurierten Ansage (mit Fallback auf Busy)
$executeRejection = function(AGI $agi): void {
    $soundPlayed = false;
    try {
        $settings = ModuleCalleridSearchCH::findFirst();
        $savedSoundKey = $settings ? trim($settings->rejected_sound_path ?? '') : '';

        if (!empty($savedSoundKey)) {
            $soundRecord = SoundFiles::findFirst([
                "path LIKE :key: AND category = :cat:",
                "bind" => [
                    "key" => "%{$savedSoundKey}%",
                    "cat" => SoundFiles::CATEGORY_CUSTOM
                ]
            ]);

            if ($soundRecord && !empty($soundRecord->path)) {
                $resolvedPath = SoundFiles::resolveAsteriskAudioPath($soundRecord->path);
                if (!empty($resolvedPath)) {
                    $agi->exec('Playback', $resolvedPath);
                    $soundPlayed = true;
                }
            }
        }
    } catch (\Throwable $e) {
        $agi->verbose('CalleridSearchCH: Sound playback error: ' . $e->getMessage());
    }

    if (!$soundPlayed) {
        $agi->exec('Busy', '5');
    }
};

try {
    // 1. Anonyme Anrufe prüfen und ggf. abwürgen
    if (CalleridSearchCHMain::shouldDropAnonymousCalls() && CalleridSearchCHMain::isAnonymousCall($number, $agi)) {
        $agi->verbose('CalleridSearchCH: Anonymous call detected, hanging up call.');
        $agi->set_variable('CALLERID(name)', 'Anonym');
        $agi->set_variable('CDR(userfield)', 'Rejected: Anonymous');
        $executeRejection($agi);
        $agi->exec('Busy', '5');
        ;$agi->hangup();
        exit;
    }
    // 2. Normaler Lookup für benannte Anrufe
    $name = CalleridSearchCHMain::lookup($number);
    if ($name !== null) {
        $agi->set_variable('CALLERID(name)', $name);
    }
   // 3. Callcenter-Drop prüfen
   if (CalleridSearchCHMain::shouldDropCallcenter() && CalleridSearchCHMain::isLastCallcenter()) {
        $agi->verbose('CalleridSearchCH: Callcenter detected, dropping call.');
        $agi->set_variable('CDR(userfield)', 'Rejected: Callcenter');
        $executeRejection($agi);
        $agi->exec('Busy', '5');
        $agi->hangup();
        exit;
    }


} catch (Throwable $e) {
    // Never break the call: the reason goes to the Asterisk log, the CallerID stays as it came.
    $agi->verbose('CalleridSearchCH: ' . $e->getMessage());
}
