@extends('layouts.master')
@section('title', __('Foundation classrooms'))
@section('content')
@php
    $tasks = ['lessons'=>['label'=>'Lesson authorizations','href'=>route('foundation-panel.index')]];
    if ($data['can_grant']) $tasks['authorize'] = ['label'=>'Authorize lesson','href'=>route('foundation-panel.index',['view'=>'authorize'])];
    if ($data['can_manage']) $tasks['eligibility'] = ['label'=>'Classroom eligibility','href'=>route('foundation-panel.index',['view'=>'eligibility'])];
    $tasks['devices'] = ['label'=>'Classroom tablets','href'=>route('foundation-panel.index',['view'=>'devices'])];
    if ($data['can_manage']) $tasks['register'] = ['label'=>'Register a classroom tablet','href'=>route('foundation-panel.index',['view'=>'register'])];
    $title = $view === 'lessons' ? __('Foundation classrooms') : __($tasks[$view]['label']);
@endphp
<div class="content-wrapper m3 page-content" data-foundation-management data-return-url="{{ route('foundation-panel.index',['view'=>$view,'page'=>$data['page']]) }}">
<x-page.header :title="$title" :subtitle="__('Approve classroom groups, pair school tablets and authorize a short lesson.')" />
<x-panel-view-nav :current="$view" :items="$tasks" />
<p>{{ __('A classroom card selects one approved learner for practice. It never opens an ordinary account or records a gate scan.') }}</p>
@unless($data['writable'])<p role="status">{{ __('Classroom changes are read-only. Existing authorizations can still be revoked.') }}</p>@endunless

@if(in_array($view,['eligibility','authorize'],true))
    @if($selectedGroup)
        <a class="m3-button m3-button--text" href="{{ route('foundation-panel.index',['view'=>$view,'page'=>$data['page']]) }}">{{ __('Back to classroom groups') }}</a>
        <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ $selectedGroup['name'] }}</h2>
        <p>{{ $selectedGroup['eligible'] ? __('Approved for Foundation practice') : __('Not approved for Foundation practice') }}</p>
        @if($view==='eligibility')
            <form method="POST" action="{{ route('foundation-panel.eligibility',['group'=>$selectedGroup['id']]) }}">@include('classroom.hidden')
                <input type="hidden" name="revision" value="{{ $selectedGroup['revision'] }}">
                <input type="hidden" name="return_page" value="{{ $data['page'] }}">
                <x-page.field name="eligible" type="select" :label="__('Classroom eligibility')" :options="[0=>__('Not approved'),1=>__('Approved')]" :value="(int)$selectedGroup['eligible']" :restore-old-input="false" required />
                <x-page.field name="reason" :label="__('Reason for eligibility decision')" maxlength="1000" :restore-old-input="false" required />
                <button class="m3-button m3-button--outlined" type="submit" @disabled(!$data['writable'])>{{ __('Save classroom eligibility') }}</button>
            </form>
        @else
            @if(collect($data['devices'])->where('active',true)->isEmpty())
                <p>{{ __('Register an active classroom tablet before authorizing a lesson.') }}</p>
                @if($data['can_manage'])<a class="m3-button m3-button--outlined" href="{{ route('foundation-panel.index',['view'=>'register']) }}">{{ __('Register a classroom tablet') }}</a>@endif
            @else
                <form method="POST" action="{{ route('foundation-panel.grant') }}">@include('classroom.hidden')
                    <input type="hidden" name="teaching_group_id" value="{{ $selectedGroup['id'] }}">
                    <x-page.field name="device_id" type="select" :label="__('Classroom tablet')" :options="collect($data['devices'])->where('active',true)->pluck('name','id')->all()" :placeholder="__('Choose a registered tablet')" :restore-old-input="false" required />
                    <x-page.field name="minutes" type="number" :label="__('Lesson authorization in minutes')" min="1" max="60" value="30" :restore-old-input="false" required />
                    <button class="m3-button m3-button--filled" type="submit" @disabled(!$data['writable'])>{{ __('Authorize my classroom lesson') }}</button>
                </form>
            @endif
        @endif
        </div></article>
    @else
        <section class="page-section" aria-labelledby="foundation-groups-heading"><h2 id="foundation-groups-heading">{{ __('Current classroom groups') }}</h2>
        <div class="panel-workspace__grid">
        @forelse($data['groups'] as $group)
            <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $group['name'] }}</h3><p>{{ $group['eligible'] ? __('Approved for Foundation practice') : __('Not approved for Foundation practice') }}</p>
            @if($view==='eligibility' || ($group['eligible'] && $group['can_authorize']))
                <a class="m3-button m3-button--outlined" href="{{ route('foundation-panel.index',['view'=>$view,'group'=>$group['id'],'page'=>$data['page']]) }}">{{ $view==='eligibility' ? __('Review classroom eligibility') : __('Authorize lesson') }}</a>
            @endif
            </div></article>
        @empty<p>{{ __('No current allocated classroom groups are available.') }}</p>@endforelse
        </div>
        @if($data['next_page'])<a href="{{ route('foundation-panel.index',['view'=>$view,'page'=>$data['next_page']]) }}">{{ __('More classroom groups') }}</a>@endif
        </section>
    @endif
@endif

@if($view==='register')
    <section class="m3-card m3-card--outlined"><div class="m3-card__content">
    <p>{{ __('Open the classroom page on the school tablet and copy its Tablet setup code here. This does not grant a Teacher or learner session.') }}</p>
    <a class="m3-button m3-button--outlined" href="{{ route('foundation-classroom.device') }}" target="_blank" rel="noopener noreferrer">{{ __('Open classroom tablet page') }}</a>
    <form method="POST" action="{{ route('foundation-panel.register') }}">@include('classroom.hidden')
        <x-page.field id="foundation-tablet-name" name="name" :label="__('Tablet name')" maxlength="100" :restore-old-input="false" required />
        <x-page.field id="foundation-tablet-setup" name="setup_code" :label="__('Tablet setup code')" maxlength="50" autocomplete="off" spellcheck="false" :restore-old-input="false" required />
        <x-page.field id="foundation-tablet-days" name="days" type="number" :label="__('Registration valid for days')" min="1" max="30" value="1" :restore-old-input="false" required />
        <button class="m3-button m3-button--filled" type="submit" @disabled(!$data['writable'])>{{ __('Register classroom tablet') }}</button>
    </form>
    </div></section>
@endif

@if($view==='devices')
    <div class="panel-workspace__grid">
    @forelse($data['devices'] as $device)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ $device['name'] }}</h2><p>{{ $device['active'] ? __('Active') : __('Inactive') }} · {{ __('Expires') }} {{ $device['expires_at'] }}</p>
        @if($data['can_manage'] && $device['active'])<form method="POST" action="{{ route('foundation-panel.device-revoke',['device'=>$device['id']]) }}" data-confirm="{{ __('Revoke :name? This tablet will no longer be available for classroom practice.',['name'=>$device['name']]) }}" data-confirm-danger data-year-safe>@include('classroom.hidden', ['operationRequired'=>false])<button class="m3-button m3-button--outlined" type="submit">{{ __('Revoke classroom tablet') }}</button></form>@endif
    </div></article>@empty<p>{{ __('No classroom tablets have been registered.') }}</p>@endforelse
    </div>
@endif

@if($view==='lessons')
    <section class="page-section" aria-labelledby="foundation-authorizations-heading"><h2 id="foundation-authorizations-heading">{{ __('Lesson authorizations') }}</h2>
    <div class="panel-workspace__grid">
    @forelse($data['leases'] as $lease)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ __('Classroom lesson') }} · {{ substr($lease['id'],-8) }}</h3><p>{{ $lease['active'] ? __('Active') : __('Inactive') }} · {{ __('Expires') }} {{ $lease['expires_at'] }}</p><p>{{ __('Classroom group') }}: {{ collect($data['groups'])->firstWhere('id',$lease['teaching_group_id'])['name'] ?? '#'.$lease['teaching_group_id'] }}<br>{{ __('Classroom tablet') }}: {{ collect($data['devices'])->firstWhere('id',$lease['device_id'])['name'] ?? $lease['device_id'] }}</p>
        @if($lease['active'])<form method="POST" action="{{ route('foundation-panel.revoke',['lease'=>$lease['id']]) }}" data-confirm="{{ __('Revoke lesson :lesson? Its restricted classroom authorization will stop working.',['lesson'=>substr($lease['id'],-8)]) }}" data-confirm-danger data-year-safe>@include('classroom.hidden', ['operationRequired'=>false])<button class="m3-button m3-button--outlined" type="submit">{{ __('Revoke lesson authorization') }}</button></form>@endif
    </div></article>@empty<p>{{ __('No lesson authorizations are available.') }}</p>@endforelse
    </div></section>
@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/foundation-management.js') }}" defer></script>@endsection
