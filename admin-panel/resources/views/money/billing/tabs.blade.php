<nav class="workspace-tabs" aria-label="{{ $tabsLabel ?? __('Record sections') }}">
@foreach($tabs as $key => $tab)
    <a class="m3-button {{ $section === $key ? 'm3-button--tonal' : 'm3-button--text' }}" href="{{ $tab['url'] }}" @if($section === $key) aria-current="page" @endif>{{ $tab['label'] }}</a>
@endforeach
</nav>
