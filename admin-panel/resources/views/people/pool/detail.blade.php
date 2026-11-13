@extends('layouts.master')
@section('title', __('Alumni member'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$member['name']" :subtitle="$member['kind'] === 'alumnus' ? __('Alumnus') : __('Volunteer')" />
    @include('people.pool.navigation')
    <div class="workspace-layout"><div class="workspace-main">
    <section class="m3-card m3-card--outlined workspace-section"><div class="m3-card__content"><h2>{{ __('Consent and eligibility') }}</h2>
        <dl><dt>{{ __('Consent') }}</dt><dd>{{ __(ucfirst($member['consent_status'])) }} {{ $member['consented_at'] }}</dd><dt>{{ __('Vetting') }}</dt><dd>{{ __(ucfirst($member['vetting_status'])) }}</dd><dt>{{ __('Vetting expiry') }}</dt><dd>{{ $member['vetting_expires_on'] ?? __('Not cleared') }}</dd><dt>{{ __('Occupation') }}</dt><dd>{{ $member['occupation'] ?? __('Not provided') }}</dd><dt>{{ __('Organisation') }}</dt><dd>{{ $member['organisation'] ?? __('Not provided') }}</dd></dl>
        <p>{{ __('Offers') }}: @if($member['offers_career_talk']){{ __('Career talks') }}@endif @if($member['offers_job_shadow']) · {{ __('Group job shadows') }}@endif</p>
        <p>{{ $member['bookable_today'] ? __('Consent and vetting are current today. Session eligibility is checked again before booking.') : __('This member is not currently eligible for a booking. Review consent and vetting.') }}</p>
        @if($canManage)<a class="m3-button m3-button--outlined" href="{{ route('people-pool-panel.bookings') }}">{{ __('Review group bookings') }}</a>@endif
    </div></section>
    @if($canVet && $member['consent_status'] !== 'withdrawn')
    <section class="m3-card m3-card--outlined workspace-section"><div class="m3-card__content"><h2>{{ __('Record a vetting decision') }}</h2>
    <form method="POST" action="{{ route('people-pool-panel.vet', $member['id']) }}" class="panel-form-grid">@csrf
        <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
        <input type="hidden" name="revision" value="{{ $member['revision'] }}">
        <x-page.field name="outcome" type="select" :label="__('Decision')" :options="['cleared' => __('Cleared'), 'refused' => __('Refused')] + ($member['vetting_status'] === 'cleared' ? ['revoked' => __('Revoke clearance')] : [])" required />
        <fieldset><legend>{{ __('Checks completed') }}</legend>
        @foreach($checks as $check)<label class="m3-checkbox"><input type="checkbox" name="checks[]" value="{{ $check }}" @checked(in_array($check, old('checks', []), true))><span class="m3-checkbox__control"></span><span>{{ __(ucwords(str_replace('_', ' ', $check))) }}</span></label>@endforeach
        </fieldset>
        <x-page.field name="checked_on" type="date" :label="__('Checked on')" :max="app(\App\Services\People\Pools\PoolAccess::class)->today()" required />
        <x-page.field name="expires_on" type="date" :label="__('Clearance expires on')" :help="__('Required only when clearing a member. Leave empty for refusal or revocation.')" />
        <x-page.field name="note" type="textarea" :label="__('Private vetting note')" maxlength="2000" :help="__('Record the decision, not certificate copies or sensitive reference numbers.')" />
        <button type="submit" class="m3-button m3-button--filled" data-confirm="{{ __('Save this vetting decision? Clearance changes apply to future bookings immediately.') }}">{{ __('Save vetting decision') }}</button>
    </form></div></section>@endif
    <section class="m3-card m3-card--outlined workspace-section"><div class="m3-card__content"><h2>{{ __('Vetting history') }}</h2>
        @forelse($member['vettings'] as $decision)<article><h3>{{ __(ucfirst($decision['outcome'])) }} · {{ $decision['checked_on'] }}</h3><p>{{ implode(', ', array_map(fn ($check) => __(ucwords(str_replace('_', ' ', $check))), $decision['checks'])) }}</p>@if($decision['expires_on'])<p>{{ __('Expires') }} {{ $decision['expires_on'] }}</p>@endif @if($canVet && $decision['note'])<p>{{ $decision['note'] }}</p>@endif</article>@empty<p>{{ __('No vetting decisions recorded.') }}</p>@endforelse
    </div></section>
    <section class="m3-card m3-card--outlined workspace-section"><div class="m3-card__content"><h2>{{ __('Member booking history') }}</h2>
        @forelse($member['bookings'] as $booking)<article><h3>{{ $booking['session_name'] }}</h3><p>{{ $booking['group_name'] }} · {{ $booking['on_date'] }} · {{ __(ucfirst($booking['status'])) }}</p></article>@empty<p>{{ __('No sessions booked.') }}</p>@endforelse
    </div></section></div>
    <aside class="workspace-context m3-card m3-card--filled"><div class="m3-card__content"><h2>{{ __('Member access') }}</h2><p>{{ __('The member uses a separate pool sign-in. School staff cannot accept or withdraw consent on their behalf.') }}</p><p>{{ __('Vetting notes are restricted to authorised vetters. A booking always requires a supervised group session with enough learners.') }}</p></div></aside></div>
</div>
@endsection
