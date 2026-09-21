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

    {{ partial("partials/submitbutton", ['indexurl': '']) }}
</form>
