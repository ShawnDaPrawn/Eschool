@extends('layouts.master')
@section('title', __('Emergency management'))
@php
    $can = $page['abilities'];
    $zone = $page['timezone'];
    $time = fn (?string $at) => $at === null ? '' : \Carbon\CarbonImmutable::parse($at)->setTimezone($zone)->format('Y-m-d H:i');
    $kinds = ['lockdown' => __('Lockdown'), 'evacuation' => __('Evacuation'), 'shelter' => __('Shelter in place'), 'critical' => __('Emergency alert')];
    $drillTypes = ['fire' => __('Fire'), 'lockdown' => __('Lockdown'), 'evacuation' => __('Evacuation'), 'shelter' => __('Shelter in place'), 'other' => __('Other')];
    $statuses = ['accounted' => __('Accounted for'), 'unaccounted' => __('Not yet accounted for'), 'released' => __('Released')];
@endphp
@section('content')<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Emergency management')" :subtitle="__('Alerts and lockdowns, roll call, reunification, visitors on site and the drill log.')" />
@if($messages)<div role="alert" class="m3-alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ __('Times use the school timezone.') }} {{ $zone }}</p>

<section class="page-section" aria-labelledby="emergency-current"><h2 id="emergency-current">{{ __('Current alert') }}</h2>
@if($page['current'])
<article class="m3-card m3-card--outlined" data-emergency-alert="{{ $page['current']['id'] }}"><div class="m3-card__content">
<h3>{{ $page['current']['shown']['title'] }}</h3>
@if($page['current']['drill'])<p><strong>{{ __('This is a drill. There is no emergency.') }}</strong></p>@endif
<p>{{ $page['current']['shown']['message'] }}</p>
<p>{{ $page['current']['status'] === 'active' ? __('Active since') : __('All clear sent') }} {{ $time($page['current']['status'] === 'active' ? $page['current']['activated_at'] : $page['current']['all_clear_at']) }} · {{ __('People told') }}: {{ $page['current']['recipient_count'] }}</p>
@if($can['can_alert'] && $can['writable'] && $page['current']['status'] === 'active')
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'alert-all-clear']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $page['current']['id'] }}"><input type="hidden" name="revision" value="{{ $page['current']['revision'] }}">
<x-page.field id="emergency-all-clear-message" name="message" type="textarea" :label="__('All-clear message (optional)')" maxlength="1000" :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Send the all-clear') }}</button></form>
@endif
</div></article>
@else<p>{{ __('There is no alert now.') }}</p>@endif
</section>

@if($can['can_alert'] && $can['writable'])
@foreach($page['pending'] as $pending)
<section class="page-section" aria-labelledby="emergency-confirm-{{ $pending['id'] }}"><h2 id="emergency-confirm-{{ $pending['id'] }}">{{ __('Confirm and send') }}</h2>
<article class="m3-card m3-card--outlined" data-emergency-pending="{{ $pending['id'] }}"><div class="m3-card__content">
<h3>{{ $pending['drill'] ? __('DRILL: :title', ['title' => $pending['title']]) : $pending['title'] }}</h3><p>{{ $pending['message'] }}</p>
<p>{{ $pending['audience'] === 'staff' ? __('To all staff only') : __('To all staff and every family') }}</p>
<p>{{ __('Nothing has been sent yet. Type :word to send it now, before :time.', ['word' => $pending['confirmation_word'], 'time' => $time($pending['confirm_by'])]) }}</p>
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'alert-confirm']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $pending['id'] }}"><input type="hidden" name="revision" value="{{ $pending['revision'] }}">
<x-page.field id="emergency-confirm-word-{{ $loop->index }}" name="confirmation" :label="__('Confirmation word')" :required="true" maxlength="20" autocomplete="off" :restore-old-input="false" />
<button class="m3-button m3-button--filled m3-button--error" type="submit">{{ __('Send the alert now') }}</button></form>
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'alert-cancel']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $pending['id'] }}"><input type="hidden" name="revision" value="{{ $pending['revision'] }}">
<button class="m3-button m3-button--outlined" type="submit">{{ __('Cancel without sending') }}</button></form>
</div></article></section>
@endforeach

<section class="page-section" aria-labelledby="emergency-prepare"><h2 id="emergency-prepare">{{ __('Send an emergency alert') }}</h2>
<p>{{ __('Step one prepares the alert. Nothing is sent until you confirm it in step two. Learners are never sent alerts.') }}</p>
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'alert-prepare']) }}">@include('emergency.scope')
<x-page.field id="emergency-kind" name="kind" type="select" :label="__('Kind of alert')" :options="$kinds" :required="true" :restore-old-input="false" />
<x-page.field id="emergency-audience" name="audience" type="select" :label="__('Who is told')" :options="['everyone' => __('All staff and every family'), 'staff' => __('All staff only')]" :required="true" :restore-old-input="false" />
<input type="hidden" name="drill" value="0"><x-m3.checkbox name="drill" id="emergency-drill" :label="__('This is a drill: families and staff are told clearly that it is a drill')" />
@if($page['drills'] !== [])
<x-page.field id="emergency-drill-id" name="drill_id" type="select" :label="__('Drill in the log (optional)')" :placeholder="__('None')" :options="collect($page['drills'])->where('status', 'planned')->whereNull('alert_id')->mapWithKeys(fn ($d) => [$d['id'] => ($drillTypes[$d['type']] ?? $d['type']).' · '.$time($d['planned_at'])])->all()" :restore-old-input="false" />
@endif
<x-page.field id="emergency-title" name="title" :label="__('Title (optional)')" maxlength="100" :restore-old-input="false" />
<x-page.field id="emergency-message" name="message" type="textarea" :label="__('Message (optional except for a general emergency alert)')" maxlength="1000" :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Prepare the alert') }}</button></form>
</section>
@endif

@if($page['roll_call'])
<section class="page-section" aria-labelledby="emergency-roll-call"><h2 id="emergency-roll-call">{{ __('Roll call') }}</h2>
<p>{{ __('Accounted for') }}: {{ $page['roll_call']['totals']['accounted'] }} · {{ __('Not yet accounted for') }}: {{ $page['roll_call']['totals']['unaccounted'] }} · {{ __('Released') }}: {{ $page['roll_call']['totals']['released'] }}@if($page['roll_call']['visitors_on_site'] !== null) · {{ __('Visitors on site') }}: {{ $page['roll_call']['visitors_on_site'] }}@endif</p>
@foreach($page['roll_call']['classes'] as $class)
<article class="m3-card m3-card--outlined" data-roll-call-class="{{ $class['class_section_id'] }}"><div class="m3-card__content">
<h3>{{ $class['name'] }}</h3><p>{{ __('Accounted for') }}: {{ $class['accounted'] }} · {{ __('Not yet accounted for') }}: {{ $class['unaccounted'] }} · {{ __('Released') }}: {{ $class['released'] }}</p>
<div class="page-table__scroll" tabindex="0" aria-label="{{ $class['name'] }}"><table class="page-table__table">
<thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
@foreach($class['learners'] as $learner)
<tr><th scope="row">{{ $learner['name'] }}</th><td>{{ $statuses[$learner['status']] }}</td><td>
@if($can['writable'] && $learner['status'] !== 'released')
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'roll-call-mark']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $page['roll_call']['alert_id'] }}"><input type="hidden" name="learner_ids[]" value="{{ $learner['learner_id'] }}">
<input type="hidden" name="status" value="{{ $learner['status'] === 'accounted' ? 'unaccounted' : 'accounted' }}">
<button class="m3-button m3-button--text" type="submit">{{ $learner['status'] === 'accounted' ? __('Mark not yet accounted for') : __('Mark accounted for') }}</button></form>
@endif
</td></tr>
@endforeach
</tbody></table></div></div></article>
@endforeach
</section>

@if($can['can_respond'] && $can['writable'] && ! $page['roll_call']['drill'])
<section class="page-section" aria-labelledby="emergency-release"><h2 id="emergency-release">{{ __('Release a child') }}</h2>
<p>{{ __('Release a child only to one of their guardians or to the named collector on an early collection request approved for today. Check who they are first. Anyone else is refused.') }}</p>
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'release']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $page['roll_call']['alert_id'] }}">
<x-page.field id="emergency-release-learner" name="learner_id" type="select" :label="__('Learner')" :required="true" :restore-old-input="false"
    :options="collect($page['roll_call']['classes'])->flatMap(fn ($c) => collect($c['learners'])->where('status', '!=', 'released')->mapWithKeys(fn ($l) => [$l['learner_id'] => $l['name'].' · '.$c['name']]))->all()" />
<x-page.field id="emergency-release-to" name="released_to" type="select" :label="__('Released to')" :options="['guardian' => __('A guardian'), 'collector' => __('An authorised collector')]" :required="true" :restore-old-input="false" />
<x-page.field id="emergency-release-guardian" name="guardian_user_id" type="select" :label="__('Guardian (for a guardian)')" :placeholder="__('None')" :options="$page['release_options']['guardians'] ?? []" :restore-old-input="false" />
<x-page.field id="emergency-release-pass" name="gate_pass_id" type="select" :label="__('Collector approved for today (for a collector)')" :placeholder="__('None')" :options="$page['release_options']['passes'] ?? []" :restore-old-input="false" />
<x-page.field id="emergency-release-collector" name="collector_name" :label="__('Collector\'s name, as on their identity document')" maxlength="191" :restore-old-input="false" />
<x-page.field id="emergency-release-check" name="verification" type="select" :label="__('How you checked who they are')" :options="$page['cards'] ? ['id_document' => __('Identity document'), 'card_scan' => __('Guardian card at the gate reader')] : ['id_document' => __('Identity document')]" :required="true" :restore-old-input="false" />
@if($page['cards'])<x-page.field id="emergency-release-scan" name="scan_id" :label="__('Card scan reference (for a guardian card)')" maxlength="36" :restore-old-input="false" />@endif
<x-page.field id="emergency-release-notes" name="notes" type="textarea" :label="__('Notes (optional)')" maxlength="500" :restore-old-input="false" />
<x-m3.checkbox name="identity_checked" id="emergency-release-confirm" :label="__('I checked this person\'s identity myself')" :required="true" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Record the release') }}</button></form>
</section>
@endif
@endif

@if($can['can_respond'])
<section class="page-section" aria-labelledby="emergency-visitors"><h2 id="emergency-visitors">{{ __('Visitors on site') }}</h2>
<p>{{ __('Everyone signed in today and not yet signed out. Visitor details are erased :days days after they sign in.', ['days' => $page['settings']['visitor_retention_days']]) }}</p>
@forelse($page['visitors'] as $visitor)
<article class="m3-card m3-card--outlined" data-visitor="{{ $visitor['id'] }}"><div class="m3-card__content">
<h3>{{ $visitor['name'] }}</h3><p>{{ $visitor['reason'] }}@if($visitor['host']) · {{ __('Visiting') }} {{ $visitor['host'] }}@endif @if($visitor['organisation']) · {{ $visitor['organisation'] }}@endif</p>
<p>{{ __('Signed in') }} {{ $time($visitor['signed_in_at']) }} · {{ $visitor['method'] === 'card' ? __('Guardian card') : __('Manual entry') }}</p>
@if($can['writable'])<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'visitor-sign-out']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $visitor['id'] }}"><input type="hidden" name="revision" value="{{ $visitor['revision'] }}">
<button class="m3-button m3-button--outlined" type="submit">{{ __('Sign out') }}</button></form>@endif
</div></article>
@empty<p>{{ __('No visitors are signed in.') }}</p>@endforelse
@if($can['writable'])
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'visitor-sign-in']) }}">@include('emergency.scope')
<h3>{{ __('Sign in a visitor') }}</h3>
<x-page.field id="emergency-visitor-method" name="method" type="select" :label="__('How they sign in')" :options="$page['cards'] ? ['manual' => __('Manual entry'), 'card' => __('Guardian card at the gate reader')] : ['manual' => __('Manual entry')]" :required="true" :restore-old-input="false" />
@if($page['cards'])<x-page.field id="emergency-visitor-scan" name="scan_id" :label="__('Card scan reference (for a guardian card)')" maxlength="36" :restore-old-input="false" />@endif
<x-page.field id="emergency-visitor-name" name="name" :label="__('Full name (for manual entry)')" maxlength="120" :restore-old-input="false" />
<x-page.field id="emergency-visitor-phone" name="phone" type="tel" :label="__('Phone number (optional)')" maxlength="30" :restore-old-input="false" />
<x-page.field id="emergency-visitor-organisation" name="organisation" :label="__('Organisation (optional)')" maxlength="120" :restore-old-input="false" />
<x-page.field id="emergency-visitor-reason" name="reason" :label="__('Reason for the visit')" :required="true" maxlength="300" :restore-old-input="false" />
<x-page.field id="emergency-visitor-host" name="host" :label="__('Who they are visiting (optional)')" maxlength="120" :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Sign in') }}</button></form>
@endif
</section>
@endif

@if($can['can_manage'] || $can['can_alert'])
<section class="page-section" aria-labelledby="emergency-drills"><h2 id="emergency-drills">{{ __('Drill log') }}</h2>
<div class="page-table__scroll" tabindex="0" aria-label="{{ __('Drill log') }}"><table class="page-table__table">
<thead><tr><th scope="col">{{ __('Drill') }}</th><th scope="col">{{ __('Planned') }}</th><th scope="col">{{ __('Started') }}</th><th scope="col">{{ __('Ended') }}</th><th scope="col">{{ __('Notes') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
@forelse($page['drills'] as $drill)
<tr data-drill="{{ $drill['id'] }}"><th scope="row">{{ $drillTypes[$drill['type']] ?? $drill['type'] }}@if($drill['status'] === 'cancelled') · {{ __('Cancelled') }}@endif</th><td>{{ $time($drill['planned_at']) }}</td><td>{{ $time($drill['started_at']) }}</td><td>{{ $time($drill['ended_at']) }}</td><td>{{ $drill['notes'] }}</td><td>
@if($can['can_manage'] && $can['writable'] && $drill['status'] !== 'cancelled')
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'drill-record']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $drill['id'] }}"><input type="hidden" name="revision" value="{{ $drill['revision'] }}">
<x-page.field id="emergency-drill-start-{{ $loop->index }}" name="started_at" type="datetime-local" :label="__('Actual start')" :restore-old-input="false" />
<x-page.field id="emergency-drill-end-{{ $loop->index }}" name="ended_at" type="datetime-local" :label="__('Actual end')" :restore-old-input="false" />
<x-page.field id="emergency-drill-notes-{{ $loop->index }}" name="notes" type="textarea" :label="__('Notes')" maxlength="2000" :restore-old-input="false" />
<button class="m3-button m3-button--text" type="submit">{{ __('Record what happened') }}</button></form>
@if($drill['status'] === 'planned' && $drill['alert_id'] === null)
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'drill-cancel']) }}">@include('emergency.scope')
<input type="hidden" name="source_id" value="{{ $drill['id'] }}"><input type="hidden" name="revision" value="{{ $drill['revision'] }}">
<button class="m3-button m3-button--text" type="submit">{{ __('Cancel this drill') }}</button></form>
@endif
@endif
</td></tr>
@empty<tr><td colspan="6">{{ __('No drills in the log yet.') }}</td></tr>@endforelse
</tbody></table></div>
@if($can['can_manage'] && $can['writable'])
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'drill-plan']) }}">@include('emergency.scope')
<h3>{{ __('Plan a drill') }}</h3>
<x-page.field id="emergency-drill-type" name="type" type="select" :label="__('Kind of drill')" :options="$drillTypes" :required="true" :restore-old-input="false" />
<x-page.field id="emergency-drill-planned" name="planned_at" type="datetime-local" :label="__('Planned for')" :required="true" :restore-old-input="false" />
<x-page.field id="emergency-drill-plan-notes" name="notes" type="textarea" :label="__('Notes')" maxlength="2000" :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Add to the drill log') }}</button></form>
<form method="POST" action="{{ route('emergency-panel.write', ['operation' => 'retention']) }}">@include('emergency.scope')
<h3>{{ __('How long visitor details are kept') }}</h3><input type="hidden" name="revision" value="{{ $page['settings']['revision'] }}">
<x-page.field id="emergency-retention" name="visitor_retention_days" type="number" :label="__('Days after sign-in')" :value="$page['settings']['visitor_retention_days']" min="1" max="90" :required="true" :restore-old-input="false" />
<button class="m3-button m3-button--outlined" type="submit">{{ __('Save') }}</button></form>
@endif
</section>
@endif

<section class="page-section" aria-labelledby="emergency-history"><h2 id="emergency-history">{{ __('Recent alerts') }}</h2>
<div class="page-table__scroll" tabindex="0" aria-label="{{ __('Recent alerts') }}"><table class="page-table__table">
<thead><tr><th scope="col">{{ __('Alert') }}</th><th scope="col">{{ __('Prepared by') }}</th><th scope="col">{{ __('Sent by') }}</th><th scope="col">{{ __('All clear by') }}</th></tr></thead><tbody>
@forelse($page['history'] as $row)
<tr><th scope="row">{{ $row['drill'] ? __('DRILL: :title', ['title' => $row['title']]) : $row['title'] }} · {{ $time($row['activated_at'] ?? $row['prepared_at']) }}@if($row['status'] === 'cancelled') · {{ __('Cancelled') }}@endif</th>
<td>{{ $row['prepared_by_name'] }}</td><td>{{ $row['confirmed_by_name'] }}</td><td>{{ $row['all_clear_by_name'] }}</td></tr>
@empty<tr><td colspan="4">{{ __('No alerts yet.') }}</td></tr>@endforelse
</tbody></table></div>
</section>
</div>@endsection
