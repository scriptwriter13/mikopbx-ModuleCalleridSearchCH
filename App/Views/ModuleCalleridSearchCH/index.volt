<form method="post" action="module-callerid-search-c-h/module-callerid-search-c-h/save" role="form"
      class="ui large form" id="module-callerid-search-ch-form">

    <div class="ui info message">
        <div class="header">{{ t._('module_callerid_search_ch_AboutHeader') }}</div>
        <p>{{ t._('module_callerid_search_ch_AboutText') }}</p>
        <p>{{ t._('module_callerid_search_ch_ApiKeyHelp') }}
            <a href="https://tel.search.ch/api/getkey" target="_blank" rel="noopener">tel.search.ch/api/getkey</a></p>
    </div>

    <div class="ten wide field">
        <label>{{ t._('module_callerid_search_ch_ApiKey') }}</label>
        {{ form.render('api_key') }}
    </div>

    <div class="field">
    <div class="ui checkbox">
        {{ form.render('transliterate_Specialchars') }}
        <label>{{ t._('module_callerid_search_ch_TransliterateSpecialChars') }}</label>
    </div>
    </div>

    <div class="ten wide field">
        <label>{{ t._('module_callerid_search_ch_Callcontrol') }}</label>
    </div>
    <div class="inline field">
        <div class="ui checkbox">
            {{ form.render('dropCallcenter') }}
            <label>{{ t._('module_callerid_search_ch_DropCallcenter') }}</label>
        </div>
        <div class="ui checkbox">
            {{ form.render('dropAnonymousCalls') }}
            <label>{{ t._('module_callerid_search_ch_DropAnonymousCalls') }}</label>
        </div>
    </div>
    <div class="ten wide field">
        <label>{{ t._('module_callerid_search_ch_Soundselector') }}</label>
    </div>
    <div class="field">
        {{ form.render('rejected_sound_path') }}
    </div>


    {{ partial("partials/submitbutton", ['indexurl': '']) }}
</form>
