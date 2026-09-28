<?php

return [
    'BreadcrumbModuleCalleridSearchCH'         => 'CallerID lookup tel.search.ch',
    'SubHeaderModuleCalleridSearchCH'          => 'Shows the caller name from the Swiss phone directory',
    'mo_ModuleCalleridSearchCH'                 => 'CallerID lookup tel.search.ch',
    'module_callerid_search_ch_AboutHeader'    => 'How it works',
    'module_callerid_search_ch_AboutText'      => 'On every incoming call the module looks the number up in the tel.search.ch directory and puts the name, city and street into the CallerID. Numbers outside Switzerland and numbers missing from the directory are left untouched.',
    'module_callerid_search_ch_ApiKeyHelp'     => 'The API key is free for moderate use. Without a key the directory returns the name only. Get a key here:',
    'module_callerid_search_ch_ApiKey'         => 'tel.search.ch API key',
    'module_callerid_search_ch_DropCallcenter' => 'Callcenter drop',
    'module_callerid_search_ch_DropAnonymousCalls' => 'Anonymous call drop',
    'module_callerid_search_ch_Callcontrol' => 'Active call flow intervention (risky)',
    'module_callerid_search_ch_Soundselector' => 'Announcement before call rejection',
    'module_callerid_search_ch_DefaultSoundNone' => 'Keine Ansage (Standard Busy)'),
    'module_callerid_search_ch_EncodingModeLabel' => 'Encoding Mode',
    'module_callerid_search_ch_EncodingNone' => 'Standard (UTF-8 / No conversion)',
    'module_callerid_search_ch_TransliterateISO646CH'      => 'Force Swiss ISO-646-CH character set (older Swiss phones, possibly analog)',
    'module_callerid_search_ch_TransliterateISO88591'     => 'Use ISO-8859-1 (Latin-1) encoding (mostly older digital phones)',
    'module_callerid_search_ch_TransliterateSpecialChars' => 'Transliterate special characters for legacy IP phones (ASCII ö->oe ä->ae etc.)',
];
