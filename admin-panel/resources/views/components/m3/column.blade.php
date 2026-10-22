{{--
    One column of an <x-m3.data-table>. The slot is the header label.

        <x-m3.column field="name" sortable>{{ __('name') }}</x-m3.column>
        <x-m3.column field="status" formatter="statusFormatter">{{ __('status') }}</x-m3.column>
        <x-m3.column field="created_at" sortable :visible="false">{{ __('created_at') }}</x-m3.column>
        <x-m3.column field="operate" actions events="actionEvents">{{ __('action') }}</x-m3.column>
        <x-m3.column field="state" checkbox />

    actions     the row-actions column: HTML from the server, never sorted, exported or hidden.
    formatter   a global function (value, row, index, field) returning HTML.
    events      a global object of 'click .selector' => function (e, value, row, index).
    escape      false to let a column render HTML (actions and formatters do already).
    align       start, center or end (numbers).   width: in px.   switchable: false keeps it
                out of the columns menu.
    Every other attribute (data-*) goes on the <th>.
--}}
@props([
    'field',
    'sortable' => false,
    'visible' => true,
    'formatter' => null,
    'events' => null,
    'escape' => null,
    'align' => null,
    'width' => null,
    'switchable' => null,
    'checkbox' => false,
    'actions' => false,
])
@php
    $options = ['data-field' => $field];
    if ($checkbox) {
        $options['data-checkbox'] = 'true';
    }
    $options['data-sortable'] = $sortable && ! $actions ? 'true' : 'false';
    if (! $visible) {
        $options['data-visible'] = 'false';
    }
    if ($formatter) {
        $options['data-formatter'] = $formatter;
    }
    if ($events) {
        $options['data-events'] = $events;
    }
    if ($escape !== null || $actions) {
        $options['data-escape'] = $escape && ! $actions ? 'true' : 'false';
    }
    if ($align) {
        $options['data-align'] = ['start' => 'left', 'end' => 'right'][$align] ?? $align;
    }
    if ($width) {
        $options['data-width'] = $width;
    }
    if ($switchable === false || $actions) {
        $options['data-switchable'] = 'false';
    }
@endphp
<th scope="col" {{ $attributes->merge($options) }}>{{ $slot }}</th>
