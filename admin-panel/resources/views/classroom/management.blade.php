@extends('layouts.master')
@section('title', __('Foundation classrooms'))
@section('content')
<div class="content-wrapper m3 page-content" data-foundation-management data-return-url="{{ route('foundation-panel.index') }}">
<x-page.header :title="__('Foundation classrooms')" :subtitle="__('Approve classroom groups, pair school tablets and authorize a short lesson.')" />
<p>{{ __('A classroom card selects one approved learner for practice. It never opens an ordinary account or records a gate scan.') }}</p>
<p><a href="{{ route('foundation-classroom.device') }}" target="_blank" rel="noopener noreferrer">{{ __('Open classroom tablet page') }}</a></p>
@unless($data['writable'])<p role="status">{{ __('Classroom changes are read-only. Existing authorizations can still be revoked.') }}</p>@endunless
<section class="page-section" aria-labelledby="foundation-groups-heading"><h2 id="foundation-groups-heading">{{ __('Current classroom groups') }}</h2>
@forelse($data['groups'] as $group)
<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $group['name'] }}</h3>
<p>{{ $group['eligible'] ? __('Approved for Foundation practice') : __('Not approved for Foundation practice') }}</p>
@if($data['can_manage'])
<form method="POST" action="{{ route('foundation-panel.eligibility',['group'=>$group['id']]) }}">@include('classroom.hidden')
<input type="hidden" name="revision" value="{{ $group['revision'] }}">
<x-page.field :id="'foundation-eligibility-'.$group['id']" name="eligible" type="select" :label="__('Classroom eligibility')" :options="[0=>__('Not approved'),1=>__('Approved')]" :value="(int)$group['eligible']" :restore-old-input="false" required />
<x-page.field :id="'foundation-reason-'.$group['id']" name="reason" :label="__('Reason for eligibility decision')" maxlength="1000" :restore-old-input="false" required />
<button class="m3-button m3-button--outlined" type="submit" @disabled(!$data['writable'])>{{ __('Save classroom eligibility') }}</button></form>
@endif
@if($group['eligible'] && $group['can_authorize'])
<form method="POST" action="{{ route('foundation-panel.grant') }}">@include('classroom.hidden')
<input type="hidden" name="teaching_group_id" value="{{ $group['id'] }}">
<x-page.field :id="'foundation-device-'.$group['id']" name="device_id" type="select" :label="__('Classroom tablet')" :options="collect($data['devices'])->where('active',true)->pluck('name','id')->all()" :placeholder="__('Choose a registered tablet')" :restore-old-input="false" required />
<x-page.field :id="'foundation-minutes-'.$group['id']" name="minutes" type="number" :label="__('Lesson authorization in minutes')" min="1" max="60" value="30" :restore-old-input="false" required />
<button class="m3-button m3-button--filled" type="submit" @disabled(!$data['writable'])>{{ __('Authorize my classroom lesson') }}</button></form>
@endif
</div></article>
@empty<p>{{ __('No current allocated classroom groups are available.') }}</p>@endforelse
@if($data['next_page'])<a href="{{ route('foundation-panel.index',['page'=>$data['next_page']]) }}">{{ __('More classroom groups') }}</a>@endif
</section>
@if($data['can_manage'])
<section class="page-section" aria-labelledby="foundation-register-heading"><h2 id="foundation-register-heading">{{ __('Register a classroom tablet') }}</h2>
<p>{{ __('Open the classroom page on the school tablet and copy its Tablet setup code here. This does not grant a Teacher or learner session.') }}</p>
<form method="POST" action="{{ route('foundation-panel.register') }}">@include('classroom.hidden')
<x-page.field id="foundation-tablet-name" name="name" :label="__('Tablet name')" maxlength="100" :restore-old-input="false" required />
<x-page.field id="foundation-tablet-setup" name="setup_code" :label="__('Tablet setup code')" maxlength="50" autocomplete="off" spellcheck="false" :restore-old-input="false" required />
<x-page.field id="foundation-tablet-days" name="days" type="number" :label="__('Registration valid for days')" min="1" max="30" value="1" :restore-old-input="false" required />
<button class="m3-button m3-button--filled" type="submit" @disabled(!$data['writable'])>{{ __('Register classroom tablet') }}</button></form>
</section>
@endif
<section class="page-section" aria-labelledby="foundation-devices-heading"><h2 id="foundation-devices-heading">{{ __('Classroom tablets') }}</h2>
@forelse($data['devices'] as $device)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $device['name'] }}</h3><p>{{ $device['active'] ? __('Active') : __('Inactive') }} · {{ __('Expires') }} {{ $device['expires_at'] }}</p>
@if($data['can_manage'] && $device['active'])<form method="POST" action="{{ route('foundation-panel.device-revoke',['device'=>$device['id']]) }}">@include('classroom.hidden', ['operationRequired'=>false])<button class="m3-button m3-button--outlined" type="submit">{{ __('Revoke classroom tablet') }}</button></form>@endif
</div></article>@empty<p>{{ __('No classroom tablets have been registered.') }}</p>@endforelse
</section>
<section class="page-section" aria-labelledby="foundation-authorizations-heading"><h2 id="foundation-authorizations-heading">{{ __('Lesson authorizations') }}</h2>
@forelse($data['leases'] as $lease)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ __('Classroom lesson') }} · {{ substr($lease['id'],-8) }}</h3><p>{{ $lease['active'] ? __('Active') : __('Inactive') }} · {{ __('Expires') }} {{ $lease['expires_at'] }}</p>
@if($lease['active'])<form method="POST" action="{{ route('foundation-panel.revoke',['lease'=>$lease['id']]) }}">@include('classroom.hidden', ['operationRequired'=>false])<button class="m3-button m3-button--outlined" type="submit">{{ __('Revoke lesson authorization') }}</button></form>@endif
</div></article>@empty<p>{{ __('No lesson authorizations are available.') }}</p>@endforelse
</section></div>
@endsection
@section('script')<script src="{{ asset('assets/js/foundation-management.js') }}" defer></script>@endsection
