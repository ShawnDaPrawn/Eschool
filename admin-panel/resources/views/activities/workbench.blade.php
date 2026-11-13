@extends('layouts.master')
@section('title', __('Activity groups'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/activity-workbench.css') }}">@endsection
@section('content')
@php($group=$detail['group'] ?? null)
<div class="content-wrapper m3 page-content activity-workbench">
<x-page.header :title="$view==='attendance' ? __('Activity attendance') : ($group['name'] ?? __('Activity groups'))" :subtitle="__('Dated groups, learner and staff allocations, sessions and materials in the selected school year.')">
<x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('activity-groups.index',['view'=>$view==='attendance'?'attendance':'groups','session_year_id'=>$year->id]) }}">{{ __('All groups') }}</a></x-slot:actions>
</x-page.header>
@include('academics.groups-status')
@if($conflict)<p role="alert" class="m3-banner m3-banner--warning">{{ __('This record changed. Compare your draft with the saved values before trying again.') }}</p>@endif
@if($readOnly)<p role="status" class="m3-banner m3-banner--info">{{ __('This school year is read-only. Select the current school year in the global header to make changes.') }}</p>@endif
<p class="panel-record-context">{{ __('School year') }}: {{ $year->name }}@if($group) · {{ $group['season'] }} · {{ $group['starts_on'] }} – {{ $group['ends_on'] }} · {{ $group['active'] ? __('Active') : __('Archived') }}@endif</p>
@if($group)
<nav class="panel-local-nav" aria-label="{{ __('Activity group workspace') }}">
@foreach(['overview'=>'Overview','members'=>'Learners','staff'=>'Staff','sessions'=>'Sessions','materials'=>'Materials','configuration'=>'Configuration'] as $key=>$label)
<a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>$key]) }}" @if($view===$key) aria-current="page" @endif>{{ __($label) }}</a>
@endforeach
<a href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>'attendance']) }}" @if($view==='attendance') aria-current="page" @endif>{{ __('Attendance') }}</a>
</nav>
@endif
@if(!$group && $view!=='group-edit')
<section class="m3-card m3-card--outlined"><div class="m3-card__content">
<div class="panel-actions"><h2>{{ $view==='attendance' ? __('Choose the activity group') : __('Groups and seasons') }}</h2>
@if($view!=='attendance' && !$readOnly)<a class="m3-button m3-button--filled" href="{{ route('activity-groups.index',['session_year_id'=>$year->id,'view'=>'group-edit']) }}">{{ __('Create activity group') }}</a><a class="m3-button m3-button--outlined" href="{{ route('activity-seasons.index') }}">{{ __('Start a season') }}</a>@endif</div>
<div class="activity-record-list">@forelse($groups as $row)<article class="activity-record"><h3><a href="{{ route('activity-groups.index',['group_id'=>$row->id,'view'=>$view==='attendance'?'attendance':'overview']) }}">{{ $row->name }}</a></h3><p>{{ $row->discipline }} · {{ $row->season }} · {{ $row->active ? __('Active') : __('Archived') }}</p><p>{{ $row->starts_on->toDateString() }} – {{ $row->ends_on->toDateString() }}</p></article>@empty<x-page.empty-state :title="__('No activity groups yet')" :message="__('Create a group with its dates and eligibility rules, then allocate members and staff.')" />@endforelse</div>
</div></section>
@elseif($view==='group-edit')
<fieldset class="activity-sections" @disabled($readOnly)>@include('activities.group-editor')</fieldset>
@elseif($group)
@if($view==='overview')
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Group overview') }}</h2>
<dl class="activity-summary"><dt>{{ __('Activity') }}</dt><dd>{{ $group['discipline'] }}</dd><dt>{{ __('Season') }}</dt><dd>{{ $group['season'] }}</dd><dt>{{ __('Eligibility') }}</dt><dd>{{ __(ucfirst($group['gender'])) }} · {{ __('Grades') }} {{ $group['minimum_grade'] ?? '—' }} – {{ $group['maximum_grade'] ?? '—' }} · {{ __('Ages') }} {{ $group['minimum_age'] ?? '—' }} – {{ $group['maximum_age'] ?? '—' }}</dd><dt>{{ __('Learner allocations') }}</dt><dd>{{ count($roster['memberships']) }}</dd><dt>{{ __('Staff allocations') }}</dt><dd>{{ count($roster['staff']) }}</dd></dl>
<p>{{ __('Open a local tab to manage this group. Attendance is captured in the central Attendance workspace.') }}</p>
</div></section>
@include('activities.sessions')
@elseif($view==='configuration')
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Group configuration') }}</h2><p>{{ __('Manage the name, parent group, season, dates, eligibility and availability. Archiving retains allocations, materials and history.') }}</p><div class="panel-actions"><a class="m3-button m3-button--filled" href="{{ route('activity-groups.index',['group_id'=>$group['id'],'view'=>'group-edit']) }}">{{ __('Edit group structure and dates') }}</a><a class="m3-button m3-button--outlined" href="{{ route('activity-seasons.index') }}">{{ __('Start a season') }}</a></div></div></section>
@elseif(in_array($view,['members','staff'],true))
@include('activities.roster')
@elseif(in_array($view,['member-edit','staff-edit'],true))
<fieldset class="activity-sections" @disabled($readOnly)>@include('activities.allocation-editor')</fieldset>
@elseif($view==='session-edit')
<fieldset class="activity-sections" @disabled($readOnly)>@include('activities.session-editor')</fieldset>
@elseif($view==='material-edit')
<fieldset class="activity-sections" @disabled($readOnly)>@include('activities.material-editor')</fieldset>
@elseif($view==='materials')
@include('activities.materials')
@elseif($view==='attendance')
@include('activities.sessions')
@if($session)<fieldset class="activity-sections" @disabled($readOnly)>@include('activities.attendance')</fieldset>@endif
@else
@include('activities.sessions')
@endif
@endif
</div>
@endsection
