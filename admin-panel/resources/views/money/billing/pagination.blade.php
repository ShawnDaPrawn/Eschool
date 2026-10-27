@if($paginator->hasPages())
<nav class="m3-row m3-gap-3" aria-label="{{ __('Page navigation') }}">
    @if(!$paginator->onFirstPage())<x-m3.button variant="outlined" :href="$paginator->previousPageUrl()">{{ __('Previous page') }}</x-m3.button>@endif
    <span>{{ __('Page :number of :total', ['number'=>$paginator->currentPage(),'total'=>$paginator->lastPage()]) }}</span>
    @if($paginator->hasMorePages())<x-m3.button variant="outlined" :href="$paginator->nextPageUrl()">{{ __('Next page') }}</x-m3.button>@endif
</nav>
@endif
