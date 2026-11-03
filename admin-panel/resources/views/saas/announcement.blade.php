@extends('saas.layout')

@section('title', $announcement['title'].' · '.__('Announcements'))

@use('App\Services\Platform\Saas\PlatformAnnouncements')
@php
    $now = \Carbon\CarbonImmutable::now(PlatformAnnouncements::TIMEZONE)->format('Y-m-d\TH:i');
    $showing = ['upcoming' => __('Upcoming'), 'showing' => __('Showing now'), 'ended' => __('Ended'), 'withdrawn' => __('Withdrawn'), 'draft' => __('Draft')];
    $draft = $announcement['status'] === 'draft';
    $audienceNames = match ($announcement['audience']) {
        'schools' => array_values(array_intersect_key($choices['schools'], array_flip(array_map('intval', $announcement['audience_values'])))),
        'stages' => array_map(fn ($stage) => __(ucfirst(str_replace('_', ' ', (string) $stage))), $announcement['audience_values']),
        default => array_map('strval', $announcement['audience_values']),
    };
@endphp

@section('content')
<p><a href="{{ route('saas.announcements.index') }}">{{ __('All announcements') }}</a></p>
<h1>{{ $announcement['title'] }}</h1>
<p class="saas-lead">
    <span class="saas-badge">{{ $showing[$announcement['showing']] }}</span>
    <span class="saas-badge @if ($announcement['severity'] === 'warning') saas-badge--warn @endif">{{ __(PlatformAnnouncements::SEVERITIES[$announcement['severity']]) }}</span>
    · {{ __(PlatformAnnouncements::KINDS[$announcement['kind']]) }}
    · {{ __('Shows from :from', ['from' => $announcement['shows_from']]) }}@if ($announcement['shows_until']) {{ __('to :until', ['until' => $announcement['shows_until']]) }}@endif
    @if ($announcement['window_starts_at']) · {{ __('Maintenance :from to :to', ['from' => $announcement['window_starts_at'], 'to' => $announcement['window_ends_at']]) }}@endif
</p>
<p>
    @if ($announcement['created_by']){{ __('Drafted by :name.', ['name' => $announcement['created_by']]) }}@endif
    @if ($announcement['published_at']) {{ __('Published :at', ['at' => $announcement['published_at']]) }}@if ($announcement['published_by']) {{ __('by :name', ['name' => $announcement['published_by']]) }}@endif.@endif
    @if ($announcement['withdrawn_at']) {{ __('Withdrawn :at', ['at' => $announcement['withdrawn_at']]) }}@if ($announcement['withdrawn_by']) {{ __('by :name', ['name' => $announcement['withdrawn_by']]) }}@endif: {{ $announcement['withdrawn_reason'] }}@endif
</p>

<h2>{{ __('As schools see it') }}</h2>
<div class="saas-card" role="region" aria-label="{{ __('As schools see it') }}">
    <h3>{{ $announcement['title'] }}</h3>
    {{ $announcement['html'] }}
</div>

<h2>{{ __('Who it reaches') }}</h2>
<div class="saas-card">
    <p>{{ __(PlatformAnnouncements::AUDIENCES[$announcement['audience']]) }}@if ($audienceNames !== []): {{ implode(', ', $audienceNames) }}@endif</p>
    @if ($draft)
        <p><strong>{{ __('Publishing now would reach :schools active school(s) and notify :admins Super Admin(s).', ['schools' => $announcement['schools'], 'admins' => $announcement['recipients']]) }}</strong></p>
        <p>{{ __('The schools are fixed when you publish: a school that joins or changes package or stage later is not added.') }}</p>
    @else
        <p><strong>{{ __('Reached :schools school(s); :read of them have read it.', ['schools' => $announcement['schools'], 'read' => $announcement['schools_read']]) }}</strong>
            {{ __(':read of :total Super Admins read', ['read' => $announcement['reads'], 'total' => $announcement['recipients']]) }}</p>
    @endif
</div>

@if ($draft)
<h2>{{ __('Publish') }}</h2>
<form method="POST" action="{{ route('saas.announcements.publish', $announcement['id']) }}" class="saas-card" aria-label="{{ __('Publish') }}">
    @csrf
    <input type="hidden" name="revision" value="{{ $announcement['revision'] }}">
    <p>{{ __('Publishing sends the notice now and cannot be undone; the text can no longer change. Check the preview first.') }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Publish to these schools') }}</button></div>
</form>

<h2 id="edit">{{ __('Change the draft') }}</h2>
<form method="POST" action="{{ route('saas.announcements.update', $announcement['id']) }}" class="saas-card" aria-label="{{ __('Change the draft') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="revision" value="{{ $announcement['revision'] }}">
    @include('saas.announcement-form', ['prefix' => 'edit', 'values' => $announcement])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the draft') }}</button></div>
</form>
@endif

@if ($announcement['status'] === 'published')
<h2>{{ __('Withdraw') }}</h2>
<form method="POST" action="{{ route('saas.announcements.withdraw', $announcement['id']) }}" class="saas-card" aria-label="{{ __('Withdraw') }}">
    @csrf
    <p>{{ __('The banner goes at once and schools no longer see it. Notices already sent stay in their inboxes. To correct it, withdraw it and publish a new one.') }}</p>
    <div class="saas-field">
        <label for="withdraw-reason">{{ __('Why') }}</label>
        <input id="withdraw-reason" name="reason" required minlength="5" maxlength="500">
    </div>
    <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Withdraw it') }}</button></div>
</form>
@endif

@if ($schools !== [])
<h2>{{ __('Read by school') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Read by school') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Super Admins notified') }}</th><th scope="col">{{ __('Read') }}</th></tr></thead>
        <tbody>
        @foreach ($schools as $school)
            <tr><th scope="row">{{ $school['name'] }}</th><td>{{ $school['recipients'] }}</td><td>{{ $school['reads'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
