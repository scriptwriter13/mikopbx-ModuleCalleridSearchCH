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

namespace Modules\ModuleCalleridSearchCH\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

/**
 * Module settings, a single row.
 */
class ModuleCalleridSearchCH extends ModulesModelsBase
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * tel.search.ch API key
     *
     * @Column(type="string", nullable=true)
     */
    public ?string $api_key = '';

    /**
     * @Column(type="integer", default="0", nullable=true)
     */
    public ?string $transliterate_Specialchars = '0';

    /**
     * @Column(type="integer", default="0", nullable=true)
     */
    public ?string $dropCallcenter = '0';

    /**
     * @Column(type="integer", default="0", nullable=true)
     */
    public ?string $dropAnonymousCalls = '0';

    /**
     * @Column(type="string", nullable=true)
     */
    public ?string $rejected_sound_path = '';

    public function initialize(): void
    {
        $this->setSource('m_ModuleCalleridSearchCH');
        parent::initialize();
    }
}
