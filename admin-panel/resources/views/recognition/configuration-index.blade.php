@if($canConfigure)
<section class="m3-card m3-card--outlined"><div class="m3-card__content">
<h2>{{ __('Recognition configuration') }}</h2>
<p>{{ __('Set up definitions, dated authority and school rules here. Daily nominations and presentations remain in their own workspaces.') }}</p>
<div class="panel-actions">
@if($config['access']['manager'])<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'settings','session_year_id'=>$year]) }}">{{ __('School recognition rules') }}</a>@endif
@if($module==='awards')
<a class="m3-button m3-button--filled" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'category','session_year_id'=>$year]) }}">{{ __('Create award category') }}</a>
<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'definition','session_year_id'=>$year]) }}">{{ __('Create award') }}</a>
@if($config['access']['manager'])
<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'administrator','session_year_id'=>$year]) }}">{{ __('Appoint category administrator') }}</a>
<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'badges','session_year_id'=>$year]) }}">{{ __('Persistence badges') }}</a>
@endif
@else
<a class="m3-button m3-button--filled" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'house','session_year_id'=>$year]) }}">{{ __('Create house') }}</a>
<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'point-category','session_year_id'=>$year]) }}">{{ __('Create point category') }}</a>
@endif
</div></div></section>
@if($module==='awards')
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Categories and nomination windows') }}</h2>
@forelse(collect($config['award_categories'])->where('can_manage',true) as $category)
@php($window=collect($config['award_windows'])->first(fn($w)=>$w['award_category_id']===$category['id']&&$w['session_year_id']===$year))
<article class="recognition-entry"><h3>{{ $category['name'] }}</h3><p>{{ __(['school'=>'Whole school','grade'=>'Grade and optional subject','group'=>'Activity group'][$category['scope_kind']]) }} · {{ $category['active'] ? __('Active') : __('Archived') }}</p>
<p>{{ __('Nomination window') }}: @if($window){{ $window['opens_on'] }} – {{ $window['closes_on'] }} · {{ __(ucfirst($window['status'])) }}@else{{ __('Not configured for the selected year') }}@endif</p>
<div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'category','edit_id'=>$category['id'],'session_year_id'=>$year]) }}">{{ __('Edit category') }}</a><a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'window','edit_id'=>$category['id'],'session_year_id'=>$year]) }}">{{ __('Configure nomination window') }}</a></div></article>
@empty<p>{{ __('No categories are available within your management scope.') }}</p>@endforelse
</div></section>
@if($config['access']['manager'])<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Dated category administrators') }}</h2>
@forelse($administrators as $grant)<article class="recognition-entry"><h3>{{ $grant['name'] }}</h3><p>{{ collect($config['award_categories'])->firstWhere('id',$grant['award_category_id'])['name'] ?? __('Category') }} · {{ $grant['starts_on'] }} – {{ $grant['ends_on'] }} · {{ $grant['active'] ? __('Active') : __('Revoked') }}</p><a href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'administrator','edit_id'=>$grant['id'],'session_year_id'=>$year]) }}">{{ __('Review or revoke appointment') }}</a></article>@empty<p>{{ __('No separate category appointments have been recorded.') }}</p>@endforelse
</div></section>@endif
@else
@foreach(['houses'=>['Houses','house'],'point_categories'=>['Point categories','point-category']] as $key=>$catalogue)
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __($catalogue[0]) }}</h2>@forelse($config[$key] as $item)<article class="recognition-entry"><h3>{{ $item['name'] }}</h3><p>{{ $item['active'] ? __('Active') : __('Archived') }}</p><a href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>$catalogue[1],'edit_id'=>$item['id'],'session_year_id'=>$year]) }}">{{ __('Edit or archive') }}</a></article>@empty<p>{{ __('Nothing configured yet.') }}</p>@endforelse</div></section>
@endforeach
@endif
@else<x-page.empty-state :title="__('Configuration access required')" :message="__('Ask a school manager or the appointed category administrator to update recognition configuration.')" />@endif
