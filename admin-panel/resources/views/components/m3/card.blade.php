{{--
    M3 card: a titled surface for one part of a page (a form, a list, a summary).

        <x-m3.card :title="__('create_medium')">
            …
        </x-m3.card>

        <x-m3.card :title="__('list_medium')" variant="filled">
            <x-slot:actions><x-m3.button variant="text" icon="plus">{{ __('add') }}</x-m3.button></x-slot:actions>
            …
            <x-slot:footer>…buttons at the bottom…</x-slot:footer>
        </x-m3.card>

    variant: outlined (default), filled, elevated.   subtitle: a line under the title.
    Every other attribute goes on the <section> (class adds grid placement).
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'variant' => 'outlined',
])
<section {{ $attributes->class(['m3-card', 'm3-card--' . $variant => $variant !== 'elevated']) }}>
    <div class="m3-card__content">
        @if ($title !== null || isset($actions))
            <div class="m3-card__header">
                <div class="m3-card__heading">
                    @if ($title !== null)
                        <h2 class="m3-card__headline">{{ $title }}</h2>
                    @endif
                    @if ($subtitle !== null)
                        <p class="m3-card__subhead">{{ $subtitle }}</p>
                    @endif
                </div>
                @isset($actions)
                    <div class="m3-card__header-actions">{{ $actions }}</div>
                @endisset
            </div>
        @endif
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="m3-card__actions">{{ $footer }}</div>
    @endisset
</section>
