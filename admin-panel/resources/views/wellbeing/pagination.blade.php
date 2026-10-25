@if($lastPage>1)<nav aria-label="{{ __('Wellbeing pages') }}">
@if($page>1)<a class="m3-button m3-button--outlined" href="{{ route('wellbeing.index',$query+['page'=>$page-1]) }}">{{ __('Previous page') }}</a>@endif
<span>{{ __('Page') }} {{ $page }} / {{ $lastPage }}</span>
@if($page<$lastPage)<a class="m3-button m3-button--outlined" href="{{ route('wellbeing.index',$query+['page'=>$page+1]) }}">{{ __('Next page') }}</a>@endif
</nav>@endif
