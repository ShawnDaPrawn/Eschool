@extends('saas.layout')

@section('title', __('Announcements'))

@use('App\Services\Platform\Saas\PlatformAnnouncements')
@php
    $now = \Carbon\CarbonImmutable::now(PlatformAnnouncements::TIMEZONE)->format('Y-m-d\TH:i');
    $showing = ['upcoming' => __('Upcoming'), 'showing' => __('Showing now'), 'ended' => __('Ended'), 'withdrawn' => __('Withdrawn'), 'draft' => __('Draft')];
@endphp

@section('content')
<h1>{{ __('Announcements to schools') }}</h1>
<p class="saas-lead">{{ __('Release notes, maintenance windows and policy changes for the Super Admins of the schools you choose. Publishing sends each of them a notice and shows a banner in their school panel between the dates you set. A published announcement is never changed: withdraw it and publish a new one.') }}</p>

<h2>{{ __('Drafts') }}</h2>
@if ($drafts === [])
    <p class="saas-card">{{ __('No drafts.') }}</p>
@else
<ul>
    @foreach ($drafts as $announcement)
        <li><a href="{{ route('saas.announcements.show', $announcement['id']) }}">{{ $announcement['title'] }}</a> · {{ __(PlatformAnnouncements::KINDS[$announcement['kind']]) }} · {{ __(PlatformAnnouncements::AUDIENCES[$announcement['audience']]) }}</li>
    @endforeach
</ul>
@endif

<h2>{{ __('Published') }}</h2>
@if ($live === [])
    <p class="saas-card">{{ __('Nothing is showing or about to show.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Published') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Announcement') }}</th><th scope="col">{{ __('Kind') }}</th><th scope="col">{{ __('Shows') }}</th><th scope="col">{{ __('Schools read') }}</th><th scope="col">{{ __('Super Admins read') }}</th></tr></thead>
        <tbody>
        @foreach ($live as $announcement)
            <tr>
                <th scope="row"><a href="{{ route('saas.announcements.show', $announcement['id']) }}">{{ $announcement['title'] }}</a>@if ($announcement['severity'] === 'warning') <span class="saas-badge saas-badge--warn">{{ __('Warning') }}</span>@endif</th>
                <td>{{ __(PlatformAnnouncements::KINDS[$announcement['kind']]) }}</td>
                <td>{{ $showing[$announcement['showing']] }} · {{ $announcement['shows_from'] }}@if ($announcement['shows_until']) {{ __('to :until', ['until' => $announcement['shows_until']]) }}@endif</td>
                <td>{{ __(':read of :total', ['read' => $announcement['schools_read'], 'total' => $announcement['schools']]) }}</td>
                <td>{{ __(':read of :total', ['read' => $announcement['reads'], 'total' => $announcement['recipients']]) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="draft">{{ __('Draft an announcement') }}</h2>
<form method="POST" action="{{ route('saas.announcements.store') }}" class="saas-card" aria-label="{{ __('Draft an announcement') }}">
    @csrf
    @include('saas.announcement-form', ['prefix' => 'new', 'values' => []])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the draft') }}</button></div>
</form>

@if ($past !== [])
<h2>{{ __('Ended or withdrawn') }}</h2>
<ul>
    @foreach ($past as $announcement)
        <li><a href="{{ route('saas.announcements.show', $announcement['id']) }}">{{ $announcement['title'] }}</a> · {{ $showing[$announcement['showing']] }} · {{ __(':read of :total Super Admins read', ['read' => $announcement['reads'], 'total' => $announcement['recipients']]) }}</li>
    @endforeach
</ul>
@endif
@endsection
