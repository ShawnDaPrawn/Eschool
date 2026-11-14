@props(['items' => [], 'current' => '', 'label' => 'Page sections'])
{{-- Real GET destinations keep each workspace task bookmarkable and usable without JavaScript. --}}
<nav class="panel-section-nav" aria-label="{{ __($label) }}">
    @foreach ($items as $key => $item)
        @if (($item['visible'] ?? true) && !empty($item['href']))
            <a class="m3-button {{ $current === (string) $key ? 'm3-button--tonal' : 'm3-button--text' }}"
                href="{{ $item['href'] }}" @if ($current === (string) $key) aria-current="page" @endif>{{ __($item['label']) }}</a>
        @endif
    @endforeach
</nav>
