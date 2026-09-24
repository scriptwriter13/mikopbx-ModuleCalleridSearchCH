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

namespace Modules\ModuleCalleridSearchCH\App\Controllers;

use MikoPBX\AdminCabinet\Controllers\BaseController;
use MikoPBX\AdminCabinet\Providers\AssetProvider;
use Modules\ModuleCalleridSearchCH\App\Forms\ModuleCalleridSearchCHForm;
use Modules\ModuleCalleridSearchCH\Models\ModuleCalleridSearchCH;

class ModuleCalleridSearchCHController extends BaseController
{
    private string $moduleUniqueID = 'ModuleCalleridSearchCH';

    public function initialize(): void
    {
        parent::initialize();
        // Single settings page: a plain Save button without "save and add new".
        $this->view->submitMode = null;
    }

    /**
     * Settings page.
     */
    public function indexAction(): void
    {
        $this->assets->collection(AssetProvider::FOOTER_JS)
            ->addJs('js/pbx/main/form.js', true)
            ->addJs('js/cache/' . $this->moduleUniqueID . '/module-callerid-search-ch-index.js', true);

        $settings = ModuleCalleridSearchCH::findFirst() ?? new ModuleCalleridSearchCH();
        $this->view->form = new ModuleCalleridSearchCHForm($settings);
    }

    /**
     * Saves the settings.
     */
    public function saveAction(): void
    {
        if (!$this->request->isPost()) {
            return;
        }
        $settings = ModuleCalleridSearchCH::findFirst() ?? new ModuleCalleridSearchCH();
        $settings->api_key = trim((string)$this->request->getPost('api_key', 'string', ''));

        $valCc = $this->request->getPost('dropCallcenter');
        $settings->dropCallcenter = (!empty($valCc) && $valCc !== 'false' && $valCc !== '0') ? '1' : '0';

        $valAnon = $this->request->getPost('dropAnonymousCalls');
        $settings->dropAnonymousCalls = (!empty($valAnon) && $valAnon !== 'false' && $valAnon !== '0') ? '1' : '0';

$settings->rejected_sound_path = trim((string)$this->request->getPost('rejected_sound_path', 'string', ''));

//$selectedSound = $this->request->getPost('rejected_sound_path', 'string', '');
//CalleridSearchCHMain::setConfigValue('rejected_sound_path', $selectedSound);

        $this->saveEntity($settings);
    }
}
