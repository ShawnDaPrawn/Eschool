@if($lastPage > 1)
<nav class="panel-actions" aria-label="{{ __('Pagination') }}">
    @if($page > 1)<a class="m3-button m3-button--outlined" href="{{ route('reenrolment-panel.show', ['contract'=>$contract->id, 'view'=>$view, 'page'=>$page-1]) }}">{{ __('Previous') }}</a>@endif
    <span>{{ __('Page :page of :pages', ['page'=>$page,'pages'=>$lastPage]) }}</span>
    @if($page < $lastPage)<a class="m3-button m3-button--outlined" href="{{ route('reenrolment-panel.show', ['contract'=>$contract->id, 'view'=>$view, 'page'=>$page+1]) }}">{{ __('Next') }}</a>@endif
</nav>
@endif
