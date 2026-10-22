{{--
    Filters above a list, in an M3 row. Put the filter fields in the slot and
    point the table's toolbar at the same id:

    <x-page.filters id="notice-filters">
        <x-page.field type="select" name="filter_class_id" :label="__('grade')" :options="$grades" data-filter="table_list" />
    </x-page.filters>
    <x-page.table id="table_list" :url="url('announcement-list')" toolbar="#notice-filters"> ... </x-page.table>

    Changing a field with data-filter reloads the table (page.js), and the
    table sends every named field in its toolbar with the request.
--}}
@props(['id', 'label' => null])
<div {{ $attributes->merge(['class' => 'm3 m3-row m3-gap-4 page-filters', 'id' => $id, 'role' => 'group']) }}
    aria-label="{{ $label ?? __('page_filters') }}">
    {{ $slot }}
</div>
