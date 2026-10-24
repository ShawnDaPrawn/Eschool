<nav aria-label="{{ __('Assignment pages') }}">
    @if ($page->previousPageUrl())<a class="m3-button m3-button--text" href="{{ $page->previousPageUrl() }}">{{ __('Previous') }}</a>@endif
    @if ($page->nextPageUrl())<a class="m3-button m3-button--text" href="{{ $page->nextPageUrl() }}">{{ __('Next') }}</a>@endif
</nav>
