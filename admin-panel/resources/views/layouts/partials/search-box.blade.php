{{--
    The header's global search box. Typing shows a few results per group
    (GET /search, JSON); Enter opens the highlighted result or the results page.
    "/" or Ctrl+K (Cmd+K on a Mac) jumps here from anywhere. Behaviour: panel.js.
--}}
<form class="panel-search" role="search" method="GET" action="{{ route('search') }}" data-panel-search>
    <label for="panel-search-input" class="sr-only">{{ __('search_label') }}</label>
    <x-icon name="search" :size="18" class="panel-search-icon" />
    <input id="panel-search-input" type="search" name="q" class="panel-search-input" autocomplete="off"
        placeholder="{{ __('search_box_placeholder') }}" maxlength="{{ \App\Services\Panel\GlobalSearch::MAX_LENGTH }}"
        role="combobox" aria-expanded="false" aria-controls="panel-search-results" aria-autocomplete="list"
        aria-keyshortcuts="/ Control+K Meta+K" aria-describedby="panel-search-hint">
    <kbd class="panel-search-kbd" aria-hidden="true">/</kbd>
    <span id="panel-search-hint" class="sr-only">{{ __('search_box_hint') }}</span>
    <div id="panel-search-results" class="panel-search-results" role="listbox" aria-label="{{ __('search_results') }}" hidden></div>
    <p class="sr-only" aria-live="polite" data-search-status></p>
</form>
