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

require_once 'Globals.php';

$agi = new AGI();
$number = $argv[1] ?? '';

try {
    // 1. Anonyme Anrufe prüfen und ggf. abwürgen
    if (CalleridSearchCHMain::shouldDropAnonymousCalls() && CalleridSearchCHMain::isAnonymousCall($number, $agi)) {
        $agi->verbose('CalleridSearchCH: Anonymous call detected, hanging up call.');
        $agi->set_variable('CALLERID(name)', 'Anonym');
        $agi->hangup();
        exit;
    }
    // 2. Normaler Lookup für benannte Anrufe
    $name = CalleridSearchCHMain::lookup($number);
    if ($name !== null) {
        $agi->set_variable('CALLERID(name)', $name);
    }
   // 3. Callcenter-Drop prüfen
   if (CalleridSearchCHMain::shouldDropCallcenter() && CalleridSearchCHMain::isLastCallcenter()) {
        $agi->verbose('CalleridSearchCH: Call Center detected ("Call Center"), hanging up call.');
        $agi->hangup();
        exit;
    }


} catch (Throwable $e) {
    // Never break the call: the reason goes to the Asterisk log, the CallerID stays as it came.
    $agi->verbose('CalleridSearchCH: ' . $e->getMessage());
}
