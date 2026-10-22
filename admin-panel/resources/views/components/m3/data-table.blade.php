{{--
    M3 data table (public/assets/m3/m3-table.js), with the admin's defaults in one place:
    server-side paging, search, refresh, a columns menu, export, sticky action column,
    cards on phones. Columns are <x-m3.column>s in the slot.

        <x-m3.data-table :url="url('medium_list')" export-name="medium-list">
            <x-m3.column field="id" sortable :visible="false">{{ __('id') }}</x-m3.column>
            <x-m3.column field="no">{{ __('no.') }}</x-m3.column>
            <x-m3.column field="name">{{ __('name') }}</x-m3.column>
            <x-m3.column field="operate" actions events="actionEvents">{{ __('action') }}</x-m3.column>
        </x-m3.data-table>

    id            defaults to table_list (what the shared scripts refresh).
    url           the list endpoint ({ total, rows }); without one, rows come from load().
    toolbar       a selector for filters shown in the table's toolbar.
    query-params  the global function that builds the request (default queryParams).
    sort-name / sort-order, page-list, export-name, export-types, search, export, selectable.
    Every other attribute (data-* options, class) goes on the <table>.
--}}
@props([
    'id' => 'table_list',
    'url' => null,
    'toolbar' => null,
    'queryParams' => 'queryParams',
    'sortName' => 'id',
    'sortOrder' => 'desc',
    'pageList' => [5, 10, 20, 50, 100, 200],
    'exportName' => null,
    'exportTypes' => ['txt', 'excel'],
    'search' => true,
    'export' => true,
    'selectable' => false,
])
@php
    $options = [
        'data-pagination' => 'true',
        'data-page-list' => json_encode($pageList),
        'data-search' => $search ? 'true' : 'false',
        'data-trim-on-search' => 'false',
        'data-show-refresh' => 'true',
        'data-show-columns' => 'true',
        'data-mobile-responsive' => 'true',
        'data-fixed-columns' => 'true',
        'data-escape' => 'true',
        'data-sort-name' => $sortName,
        'data-sort-order' => $sortOrder,
        'data-maintain-selected' => 'true',
        'data-click-to-select' => $selectable ? 'true' : 'false',
    ];
    if ($url !== null) {
        $options['data-url'] = $url;
        $options['data-side-pagination'] = 'server';
    }
    if ($queryParams) {
        $options['data-query-params'] = $queryParams;
    }
    if ($toolbar) {
        $options['data-toolbar'] = $toolbar;
    }
    if ($export) {
        $options['data-show-export'] = 'true';
        $options['data-export-types'] = json_encode($exportTypes);
        $options['data-export-options'] = json_encode([
            'fileName' => ($exportName ?? $id) . '-' . date('d-m-y'),
            'ignoreColumn' => ['operate'],
        ]);
    }
@endphp
<table id="{{ $id }}" data-m3-table {{ $attributes->merge($options) }}>
    <thead>
        <tr>
            {{ $slot }}
        </tr>
    </thead>
</table>
