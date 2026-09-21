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
/* global globalRootUrl, Form */

const ModuleCalleridSearchCHIndex = {
    // Resolved in initialize(): jQuery is not guaranteed to be bound while this file is parsed.
    $formObj: null,

    initialize() {
        ModuleCalleridSearchCHIndex.$formObj = $('#module-callerid-search-ch-form');

        Form.$formObj = ModuleCalleridSearchCHIndex.$formObj;
        Form.url = `${globalRootUrl}module-callerid-search-c-h/module-callerid-search-c-h/save`;
        // The key is optional: without it the directory answers with the name only.
        Form.validateRules = {};
        Form.cbBeforeSendForm = (settings) => {
            const result = settings;
            result.data = ModuleCalleridSearchCHIndex.$formObj.form('get values');
            return result;
        };
        Form.cbAfterSendForm = () => {};
        Form.initialize();
    },
};

$(document).ready(() => {
    ModuleCalleridSearchCHIndex.initialize();
});
