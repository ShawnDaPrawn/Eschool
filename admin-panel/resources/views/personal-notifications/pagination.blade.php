<nav class="panel-actions" aria-label="{{ __('page_table_pages') }}">
    @if($inbox['page'] > 1)<a class="m3-button m3-button--outlined" href="{{ request()->fullUrlWithQuery(['page' => $inbox['page'] - 1]) }}">{{ __('Previous') }}</a>@endif
    <span>{{ __('Page') }} {{ $inbox['page'] }} / {{ max(1, $inbox['last_page']) }}</span>
    @if($inbox['page'] < $inbox['last_page'])<a class="m3-button m3-button--outlined" href="{{ request()->fullUrlWithQuery(['page' => $inbox['page'] + 1]) }}">{{ __('Next') }}</a>@endif
</nav>
