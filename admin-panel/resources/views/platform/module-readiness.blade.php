@extends('layouts.master')
@section('title', __('Module setup readiness'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Module setup readiness')" :subtitle="__('Availability and actual setup are separate. Baseline configured means only the listed checks passed; review the module rules before operating it.')"><x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('school-modules.index') }}">{{ __('Module availability') }}</a></x-slot:actions></x-page.header>
<p>{{ __('Selected school year') }}: {{ $year?->name }}. {{ __('The foundational school checklist reports the current school year.') }}</p>
<div class="panel-workspace__grid">
@foreach($modules as $module)<section class="m3-card m3-card--outlined" data-module-readiness="{{ $module['key'] }}"><div class="m3-card__content"><h2>{{ __($module['name']) }}</h2><p><strong>{{ __($module['status']) }}</strong> · {{ __($module['availability']['enabled'] ? ($module['availability']['read_only'] ? 'Read-only' : 'On') : 'Off') }}</p>
@if(!$module['availability']['enabled'])<p>{{ __('Enable this module within the school package before configuring it.') }}</p>
@elseif(!$module['checked'])<p>{{ __('Review configuration with an authorised module manager. This module has no automated setup conclusion for your authority.') }}</p>
@else<ul>@foreach($module['tasks'] as $task)<li><strong>{{ __($task['label']) }}</strong> · {{ __($task['done']?'Configured':'Needs attention') }}<p>{{ $task['detail'] }}</p>@if($task['url'])<a class="m3-button m3-button--text" href="{{ $task['url'] }}">{{ __('Review configuration') }}</a>@else<p>{{ __('An authorised colleague must complete this step.') }}</p>@endif</li>@endforeach</ul>@endif
@if($module['availability']['enabled'] && !$module['checked'])
<ul>@foreach($module['links'] as $link)<li><a class="m3-button m3-button--text" href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>@endforeach</ul>
@endif
</div></section>@endforeach
</div>
</div>
@endsection
