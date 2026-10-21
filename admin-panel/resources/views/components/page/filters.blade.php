{{--
    Filters above a list. Put the filter fields in the slot; give the table the
    same id as its toolbar so bootstrap-table places its search box beside them:

    <x-page.filters id="notice-filters">
        <x-page.field type="select" name="filter_class_id" :label="__('grade')" :options="$grades" />
    </x-page.filters>
    <x-page.table id="table_list" :url="url('announcement-list')" toolbar="#notice-filters"> ... </x-page.table>

    Changing a field with data-filter reloads the table (page.js). The table's
    queryParams function sends the values.
--}}
@props(['id', 'label' => null])
<div {{ $attributes->merge(['class' => 'page-filters', 'id' => $id, 'role' => 'group']) }}
    aria-label="{{ $label ?? __('page_filters') }}">
    {{ $slot }}
</div>
