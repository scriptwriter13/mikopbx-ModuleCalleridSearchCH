<?php
namespace Modules\CalleridSearchCH\Setup;

use MikoPBX\Modules\Setup\PbxExtensionSetupBase;
use MikoPBX\Core\System\Util;

class PbxExtensionSetup extends PbxExtensionSetupBase
{
    public function install(): bool 
    {
        // 1. Hier kannst du sicherstellen, dass dein AGI-Skript ausführbar ist
        $agiScript = __DIR__ . '/../agi-bin/lookup.php';
        if (file_exists($agiScript)) {
            chmod($agiScript, 0755);
        }
        
        // 2. Rufe die Basis-Installationslogik auf
        return parent::install();
    }

    public function uninstall(): bool 
    {
        // Hier kannst du Aufräumarbeiten für dein AGI-Skript vornehmen
        return parent::uninstall();
    }
}
