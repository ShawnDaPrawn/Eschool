@extends('layouts.master')
@section('title', __('Alumni and volunteers'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Alumni and volunteers')" :subtitle="__('Invite former learners and volunteers, review consent and vetting, and arrange supervised group sessions.')" :primary-label="__('Invite a member')" :primary-href="route('people-pool-panel.create')" primary-permission="people-pool-manage" />
    @include('people.pool.navigation')
    <div class="panel-record-list">
    @forelse($listing['items'] as $member)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content">
            <h2><a href="{{ route('people-pool-panel.detail', $member['id']) }}">{{ $member['name'] }}</a></h2>
            <p>{{ $member['kind'] === 'alumnus' ? __('Alumnus') : __('Volunteer') }} · {{ $member['occupation'] }} @if($member['organisation']) · {{ $member['organisation'] }}@endif</p>
            <dl><dt>{{ __('Consent') }}</dt><dd>{{ __(ucfirst($member['consent_status'])) }}</dd><dt>{{ __('Vetting') }}</dt><dd>{{ __(ucfirst($member['vetting_status'])) }} @if($member['vetting_expires_on']) · {{ __('Expires') }} {{ $member['vetting_expires_on'] }}@endif</dd></dl>
            <x-m3.chip :status="$member['bookable_today'] ? 'success' : 'neutral'">{{ $member['bookable_today'] ? __('Eligible for a booking today') : __('Consent or vetting needs attention') }}</x-m3.chip>
            <div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('people-pool-panel.detail', $member['id']) }}">{{ __('Open member') }}</a></div>
        </div></article>
    @empty<x-page.empty-state :title="__('No alumni or volunteers invited yet')" :message="__('Start with an invitation. The person gives their own consent before the school can book a session.')" :action-label="__('Invite a member')" :action-href="route('people-pool-panel.create')" />@endforelse
    </div>
    <nav class="panel-actions" aria-label="{{ __('Member pages') }}">
        @if($listing['page'] > 1)<a class="m3-button m3-button--outlined" href="{{ route('people-pool-panel.index', ['page' => $listing['page'] - 1]) }}">{{ __('Previous') }}</a>@endif
        <span>{{ __('Page') }} {{ $listing['page'] }} / {{ $listing['last_page'] }}</span>
        @if($listing['page'] < $listing['last_page'])<a class="m3-button m3-button--outlined" href="{{ route('people-pool-panel.index', ['page' => $listing['page'] + 1]) }}">{{ __('Next') }}</a>@endif
    </nav>
</div>
@endsection
