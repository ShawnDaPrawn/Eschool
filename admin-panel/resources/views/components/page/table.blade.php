{{--
    A server-side list: bootstrap-table with the panel's conventions
    (data-escape="true", server pagination, the shared queryParams), bulk
    actions over the ticked rows, and a designed empty state.

    <x-page.table id="table_list" :url="url('announcement-list')" toolbar="#notice-filters"
        :empty-title="__('no_notices_yet')" :empty-message="__('no_notices_yet_hint')"
        :empty-action-label="__('send_a_notice')" empty-action-href="#notice-form" empty-icon="announcement">
        <x-slot:columns>
            <th data-field="state" data-checkbox="true"></th>
            <th data-field="title" data-sortable="true">{{ __('title') }}</th>
            <th data-field="operate" data-escape="false" data-events="noticeEvents">{{ __('action') }}</th>
        </x-slot:columns>
        <x-slot:bulk>
            <button type="button" class="btn btn-danger btn-sm" data-bulk-action="{{ url('announcement/bulk-delete') }}"
                data-bulk-confirm="{{ __('delete_selected_notices') }}">{{ __('delete') }}</button>
        </x-slot:bulk>
    </x-page.table>

    Every cell is escaped (data-escape="true"); only a column whose formatter or
    server HTML is built safely may set data-escape="false" (the operate column,
    built with escapeHtml() and safeUrl()). page.js initialises the table, so
    it can render the empty state; don't add data-toggle="table".
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
<div class="page-table" data-page-table="{{ $id }}">
    @isset($bulk)
        <div class="page-bulk-bar" data-bulk-bar="{{ $id }}" role="region" aria-label="{{ __('page_bulk_actions') }}" hidden>
            <span class="page-bulk-count" data-bulk-count aria-live="polite"></span>
            <div class="page-bulk-actions">{{ $bulk }}</div>
        </div>
    @endisset
    <table id="{{ $id }}" class="table" data-page-table-init
        data-url="{{ $url }}"
        data-side-pagination="server"
        data-pagination="true"
        data-page-size="{{ $pageSize }}"
        data-page-list="[10, 25, 50, 100]"
        data-search="{{ $search ? 'true' : 'false' }}"
        data-show-refresh="true"
        data-escape="true"
        data-query-params="{{ $queryParams }}"
        data-sort-name="{{ $sortName }}"
        data-sort-order="{{ $sortOrder }}"
        data-mobile-responsive="true"
        data-click-to-select="false"
        data-maintain-meta-data="true"
        @if ($toolbar) data-toolbar="{{ $toolbar }}" @endif
        {{ $attributes }}>
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        <thead>
            <tr>
                {{ $columns }}
            </tr>
        </thead>
    </table>
    <template data-empty-template="{{ $id }}">
        <x-page.empty-state :icon="$emptyIcon" :title="$emptyTitle ?? __('page_empty_title')"
            :message="$emptyMessage" :action-label="$emptyActionLabel" :action-href="$emptyActionHref" />
    </template>
    <template data-no-matches-template="{{ $id }}">
        <x-page.empty-state icon="search" :title="__('page_no_matches_title')" :message="__('page_no_matches_hint')" />
    </template>
</div>
