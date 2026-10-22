{{--
    A server-side list in a Material 3 outlined card: search, sortable columns,
    pages, bulk actions over the ticked rows, and a designed empty state. page.js
    draws it (PageTable), with no Bootstrap and no bootstrap-table, from the
    same list endpoints the panel already has: it sends offset, limit, sort,
    order and search (through the page's queryParams function, if it has one,
    plus every named field in the toolbar) and reads {total, rows}.

    <x-page.table id="table_list" :url="url('announcement-list')" toolbar="#notice-filters"
        :empty-title="__('no_notices_yet')" :empty-message="__('no_notices_yet_hint')"
        :empty-action-label="__('send_a_notice')" empty-action-href="#notice-form" empty-icon="announcement">
        <x-slot:columns>
            <th data-field="state" data-checkbox="true"></th>
            <th data-field="title" data-sortable="true">{{ __('title') }}</th>
            <th data-field="operate" data-escape="false" data-events="noticeEvents">{{ __('action') }}</th>
        </x-slot:columns>
        <x-slot:bulk>
            <button type="button" class="m3-button m3-button--text m3-button--error" data-bulk-action="{{ url('announcement/bulk-delete') }}"
                data-bulk-confirm="{{ __('delete_selected_notices') }}">{{ __('delete') }}</button>
        </x-slot:bulk>
    </x-page.table>

    Every cell is escaped (data-escape="true"); only a column whose formatter or
    server HTML is built safely may set data-escape="false" (the operate column,
    built with escapeHtml() and safeUrl()). A column's data-formatter names a
    global function (value, row, index, field) returning HTML; data-events names
    a global object of {"click .selector": function (event, value, row, index)}.
--}}
@props([
    'id' => 'table_list',
    'url',
    'toolbar' => null,
    'queryParams' => 'queryParams',
    'sortName' => 'id',
    'sortOrder' => 'desc',
    'search' => true,
    'pageSize' => 10,
    'emptyIcon' => 'inbox',
    'emptyTitle' => null,
    'emptyMessage' => null,
    'emptyActionLabel' => null,
    'emptyActionHref' => null,
    'caption' => null,
])
<div class="m3 m3-card m3-card--outlined page-table" data-page-table="{{ $id }}">
    <div class="page-table__toolbar">
        @if ($search)
            <label class="m3-search page-table__search">
                <span class="m3-visually-hidden">{{ __('page_table_search') }}</span>
                <input type="search" class="m3-search__input" data-table-search="{{ $id }}" aria-controls="{{ $id }}"
                    placeholder="{{ __('page_table_search') }}" autocomplete="off" maxlength="100">
            </label>
        @endif
        <button type="button" class="m3-icon-button page-table__refresh" data-table-refresh="{{ $id }}" aria-controls="{{ $id }}"
            aria-label="{{ __('page_table_refresh') }}" data-m3-tooltip="{{ __('page_table_refresh') }}">
            <x-icon name="refresh" :size="24" />
        </button>
    </div>
    @isset($bulk)
        <div class="page-bulk-bar" data-bulk-bar="{{ $id }}" role="region" aria-label="{{ __('page_bulk_actions') }}" hidden>
            <span class="page-bulk-count" data-bulk-count aria-live="polite"></span>
            <div class="page-bulk-actions">{{ $bulk }}</div>
        </div>
    @endisset
    <div class="m3-progress-linear m3-progress-linear--indeterminate page-table__progress" data-table-progress hidden>
        <span class="m3-progress-linear__bar"></span>
    </div>
    <div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ $caption ?? __('page_table_region') }}">
        <table id="{{ $id }}" class="page-table__table" data-page-table-init
            data-url="{{ $url }}"
            data-side-pagination="server"
            data-pagination="true"
            data-page-size="{{ $pageSize }}"
            data-page-list="[10, 25, 50, 100]"
            data-search="{{ $search ? 'true' : 'false' }}"
            data-escape="true"
            data-query-params="{{ $queryParams }}"
            data-sort-name="{{ $sortName }}"
            data-sort-order="{{ $sortOrder }}"
            @if ($toolbar) data-toolbar="{{ $toolbar }}" @endif
            {{ $attributes }}>
            @if ($caption)
                <caption class="m3-visually-hidden">{{ $caption }}</caption>
            @endif
            <thead>
                <tr>
                    {{ $columns }}
                </tr>
            </thead>
            <tbody data-table-body></tbody>
        </table>
    </div>
    <div class="page-table__state" data-table-state aria-live="polite"></div>
    <div class="page-table__footer" data-table-footer hidden>
        <label class="m3-text-field m3-text-field--select panel-field--compact page-table__size">
            <select class="m3-text-field__input" data-table-page-size="{{ $id }}" aria-controls="{{ $id }}">
                @foreach ([10, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($size === (int) $pageSize)>{{ $size }}</option>
                @endforeach
            </select>
            <span class="m3-text-field__label">{{ __('page_table_rows_per_page') }}</span>
        </label>
        <span class="page-table__range" data-table-range></span>
        <nav aria-label="{{ __('page_table_pages') }}">
            <ul class="m3-pagination" data-table-pages></ul>
        </nav>
    </div>
    <template data-empty-template="{{ $id }}">
        <x-page.empty-state :icon="$emptyIcon" :title="$emptyTitle ?? __('page_empty_title')"
            :message="$emptyMessage" :action-label="$emptyActionLabel" :action-href="$emptyActionHref" />
    </template>
    <template data-no-matches-template="{{ $id }}">
        <x-page.empty-state icon="search" :title="__('page_no_matches_title')" :message="__('page_no_matches_hint')" />
    </template>
    <template data-error-template="{{ $id }}">
        <x-page.empty-state icon="alert" :title="__('page_table_error')">
            <button type="button" class="m3-button m3-button--outlined" data-table-refresh="{{ $id }}">{{ __('page_table_retry') }}</button>
        </x-page.empty-state>
    </template>
</div>
