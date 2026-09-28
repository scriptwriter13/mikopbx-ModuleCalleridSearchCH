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

namespace Modules\ModuleCalleridSearchCH\App\Forms;

use MikoPBX\AdminCabinet\Forms\BaseForm;
use Phalcon\Forms\Element\Text;
//use Phalcon\Forms\Element\Check;

use Phalcon\Forms\Element\Select;

require_once __DIR__ . '/../../Lib/CalleridSearchCHMain.php';
use Modules\ModuleCalleridSearchCH\Lib\CalleridSearchCHMain;


class ModuleCalleridSearchCHForm extends BaseForm
{
    public function initialize($entity = null, $options = null): void
    {
        parent::initialize($entity, $options);
        $this->add(new Text('api_key', ['autocomplete' => 'off']));


        // Sicheren Zugriff auf den MikoPBX Translation Service holen
        $translation = null;
        if ($this->di && $this->di->has('translation')) {
            $translation = $this->di->get('translation');
        }
        // Hilfsfunktion für saubere Übersetzung mit Text-Fallback
        $t = static function(string $key, string $fallback) use ($translation): string {
            if ($translation !== null && method_exists($translation, '_')) {
                return $translation->_($key);
            }
            return $fallback;
        };
        // Select-Box für das Encoding-Mode (mutually exclusive)
        $encodingSelect = new Select('encoding_mode', [
            'none'     => $t('module_callerid_search_ch_EncodingNone', ''),
            'ascii'    => $t('module_callerid_search_ch_TransliterateSpecialChars', ''),
            'iso646ch' => $t('module_callerid_search_ch_TransliterateISO646CH', ''),
            'iso88591' => $t('module_callerid_search_ch_TransliterateISO88591', '')
        ], [
            'class' => 'form-control select2'
        ]);
	$encodingSelect->setLabel($t('module_callerid_search_ch_EncodingModeLabel',''));
        $this->add($encodingSelect);



        $this->addCheckBox('dropCallcenter', intval($entity?->dropCallcenter) === 1);
        $this->addCheckBox('dropAnonymousCalls', intval($entity?->dropAnonymousCalls) === 1);

        $sounds = CalleridSearchCHMain::getAvailableCustomSounds();
        $sounds[''] = $t('module_callerid_search_ch_DefaultSoundNone', '');
        $soundSelect = new Select('rejected_sound_path', $sounds, [
            'class' => 'form-control select2'
        ]);
        //$soundSelect->setLabel($t('module_callerid_search_ch_CallcenterSoundLabel', ''));
        //$soundSelect->setLabel('Ansage bei Callcenter-Abweisung');
        $this->add($soundSelect);

    }
}
