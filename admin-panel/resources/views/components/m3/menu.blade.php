{{--
    M3 menu: a popover list opened by any element with data-m3-menu="its id".

        <x-m3.button variant="outlined" trailing-icon="chevron-down" data-m3-menu="export-menu"
            aria-haspopup="menu" aria-expanded="false">{{ __('export') }}</x-m3.button>
        <x-m3.menu id="export-menu">
            <x-m3.menu-item :href="route('export', 'csv')">CSV</x-m3.menu-item>
            <x-m3.menu-item icon="trash" class="delete-all">{{ __('delete') }}</x-m3.menu-item>
        </x-m3.menu>
--}}
@props(['id'])
<div id="{{ $id }}" popover role="menu" {{ $attributes->class(['m3-menu']) }}>
    {{ $slot }}
</div>
