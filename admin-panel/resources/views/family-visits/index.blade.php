@extends('layouts.master')
@section('title', __('Meetings and early collection'))
@section('content')<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Meetings and early collection')" :subtitle="__('Bookable parent appointments and staff-approved collection requests.')" />
@if($messages)<div role="alert" class="m3-alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<p>{{ __('Times use the school timezone.') }} {{ $access['timezone'] }}</p>
@if($access['can_manage'] && $access['writable'])
<section class="page-section" aria-labelledby="visit-create"><h2 id="visit-create">{{ __('Publish a parent meeting slot') }}</h2>
<form method="POST" action="{{ route('family-visits-panel.write',['operation'=>'meeting-create']) }}">@include('family-visits.scope')
<x-page.field id="visit-host" name="host_id" type="select" :label="__('Meeting host')" :options="collect($access['hosts'])->pluck('name','id')->all()" :required="true" :restore-old-input="false" />
<x-page.field id="visit-title" name="title" :label="__('Meeting title')" :required="true" maxlength="191" :restore-old-input="false" />
<x-page.field id="visit-venue" name="venue" :label="__('Meeting venue')" :required="true" maxlength="500" :restore-old-input="false" />
<x-page.field id="visit-start" name="starts_at" type="datetime-local" :label="__('Meeting starts')" :required="true" :restore-old-input="false" />
<x-page.field id="visit-end" name="ends_at" type="datetime-local" :label="__('Meeting ends')" :required="true" :restore-old-input="false" />
<x-page.field id="visit-close" name="booking_closes_at" type="datetime-local" :label="__('Booking deadline')" :required="true" :restore-old-input="false" />
<button class="m3-button m3-button--filled" type="submit">{{ __('Publish meeting slot') }}</button></form></section>
@endif
<section class="page-section" aria-labelledby="visit-meetings"><h2 id="visit-meetings">{{ __('Meeting appointments') }}</h2>
@forelse($meetings['items'] as $row)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $row['title'] }}</h3>
<p>{{ $row['host_name'] }} · {{ $row['venue'] }}</p><p>{{ \Carbon\CarbonImmutable::parse($row['starts_at'])->setTimezone($access['timezone'])->format('Y-m-d H:i') }}–{{ \Carbon\CarbonImmutable::parse($row['ends_at'])->setTimezone($access['timezone'])->format('H:i') }}</p>
<p>{{ __(ucfirst($row['status'])) }}@if($row['learner_name'] ?? null) · {{ $row['learner_name'] }}@endif</p>
@if($access['can_manage'] && $row['status'] !== 'cancelled')<form method="POST" action="{{ route('family-visits-panel.write',['operation'=>'meeting-cancel']) }}">@include('family-visits.scope')<input type="hidden" name="source_id" value="{{ $row['id'] }}"><input type="hidden" name="revision" value="{{ $row['revision'] }}"><button class="m3-button m3-button--outlined" type="submit">{{ __('Cancel meeting slot') }}</button></form>@endif
</div></article>@empty<p>{{ __('No meeting appointments in this scope.') }}</p>@endforelse
@if($meetings['next_page'])<a href="{{ route('family-visits-panel.index',['page'=>$meetings['next_page']]) }}">{{ __('More meeting slots') }}</a>@endif
</section>
@if($access['can_manage'])
<section class="page-section" aria-labelledby="visit-passes"><h2 id="visit-passes">{{ __('Early collection requests') }}</h2><p>{{ __('A pending request does not authorise collection. Gate staff must check the named collector in person.') }}</p>
@forelse($passes['items'] as $row)<article class="m3-card m3-card--outlined" data-gate-pass="{{ $row['id'] }}"><div class="m3-card__content"><h3>{{ $row['learner_name'] }}</h3><p>{{ __('Named collector') }}: {{ $row['collector'] }}</p><p>{{ $row['reason'] }}</p><p>{{ \Carbon\CarbonImmutable::parse($row['starts_at'])->setTimezone($access['timezone'])->format('Y-m-d H:i') }}–{{ \Carbon\CarbonImmutable::parse($row['ends_at'])->setTimezone($access['timezone'])->format('H:i') }} · {{ __(ucfirst($row['status'])) }}</p>
@if($access['writable'] && $row['status'] === 'pending')<form method="POST" action="{{ route('family-visits-panel.write',['operation'=>'pass-approve']) }}">@include('family-visits.scope')<input type="hidden" name="source_id" value="{{ $row['id'] }}"><input type="hidden" name="revision" value="{{ $row['revision'] }}"><button class="m3-button m3-button--filled" type="submit">{{ __('Approve this collection request') }}</button></form>@endif
@if(in_array($row['status'],['pending','approved'],true))<form method="POST" action="{{ route('family-visits-panel.write',['operation'=>'pass-cancel']) }}">@include('family-visits.scope')<input type="hidden" name="source_id" value="{{ $row['id'] }}"><input type="hidden" name="revision" value="{{ $row['revision'] }}"><button class="m3-button m3-button--outlined" type="submit">{{ __('Revoke collection request') }}</button></form>@endif
</div></article>@empty<p>{{ __('No current collection requests.') }}</p>@endforelse
@if($passes['next_page'])<a href="{{ route('family-visits-panel.index',['pass_page'=>$passes['next_page']]) }}">{{ __('More collection requests') }}</a>@endif
</section>
@if($access['writable'])<section class="page-section" aria-labelledby="visit-rules"><h2 id="visit-rules">{{ __('School collection rules') }}</h2>
<form method="POST" action="{{ route('family-visits-panel.write',['operation'=>'rules']) }}">@include('family-visits.scope')<input type="hidden" name="revision" value="{{ $access['gate_rules']['revision'] }}">
<x-page.field id="visit-window" name="max_window_minutes" type="number" :label="__('Maximum collection window in minutes')" :value="$access['gate_rules']['max_window_minutes']" min="15" max="240" :required="true" :restore-old-input="false" />
<x-page.field id="visit-days" name="max_future_days" type="number" :label="__('Maximum days ahead')" :value="$access['gate_rules']['max_future_days']" min="1" max="30" :required="true" :restore-old-input="false" />
<x-page.field id="visit-scan-age" name="scan_max_age_seconds" type="number" :label="__('Maximum online scan age in seconds')" :value="$access['gate_rules']['scan_max_age_seconds']" min="15" max="120" :required="true" :restore-old-input="false" />
<p>{{ __('Staff approval and the gate collector check are required.') }}</p><button class="m3-button m3-button--filled" type="submit">{{ __('Save collection rules') }}</button></form></section>@endif
@endif
</div>@endsection
