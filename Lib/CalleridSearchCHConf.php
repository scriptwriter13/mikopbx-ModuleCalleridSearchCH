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

namespace Modules\ModuleCalleridSearchCH\Lib;

use MikoPBX\Core\Asterisk\Configs\ExtensionsConf;
use MikoPBX\Modules\Config\ConfigClass;

class CalleridSearchCHConf extends ConfigClass
{
    public function generateIncomingRoutBeforeDial(string $rout_number): string
    {
        $agiPath = $this->moduleDir . '/agi-bin/lookup.php';
        // Single Quotes verhindern, dass PHP versucht, ${...} zu parsen
        return 'same => n,AGI(' . $agiPath . ',${CALLERID(num)})' . PHP_EOL;
    }

    /**
     * Stellt sicher, dass Änderungen sofort aktiv werden.
     */
    public function onAfterModuleEnable(): void
    {
        ExtensionsConf::reload();
    }

    /**
     * Ohne diesen Reload bleibt der AGI-Aufruf im Dialplan stehen und läuft bei jedem
     * eingehenden Anruf weiter, obwohl das Modul deaktiviert ist.
     */
    public function onAfterModuleDisable(): void
    {
        ExtensionsConf::reload();
    }
}
