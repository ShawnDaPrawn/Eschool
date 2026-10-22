{{--
    An admin page built on the M3 library: the page's headline, optional actions and
    supporting text, then the page content in a vertical stack.

        @section('content')
            <x-m3.page :title="__('manage_medium')">
                <x-slot:actions><x-m3.button icon="plus" :href="…">{{ __('add') }}</x-m3.button></x-slot:actions>
                <div class="m3-grid m3-gap-6">
                    <x-m3.card class="m3-col-12 m3-col-expanded-5" :title="__('create_medium')">…</x-m3.card>
                    <x-m3.card class="m3-col-12 m3-col-expanded-7" :title="__('list_medium')">…</x-m3.card>
                </div>
            </x-m3.page>
        @endsection

    It carries the `m3` class, so only migrated pages get the library's base rules while the
    rest of the admin still renders Bootstrap markup.
--}}
@props([
    'title',
    'subtitle' => null,
])
<div {{ $attributes->class(['content-wrapper', 'm3', 'm3-admin-page']) }}>
    <header class="m3-page-header">
        <div class="m3-page-header__text">
            <h1 class="m3-page-header__title">{{ $title }}</h1>
            @if ($subtitle !== null)
                <p class="m3-page-header__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="m3-page-header__actions">{{ $actions }}</div>
        @endisset
    </header>
    <div class="m3-stack m3-gap-6">
        {{ $slot }}
    </div>
</div>
