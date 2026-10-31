@extends('saas.layout')

@section('title', __('Incidents'))

@use('App\Services\Platform\Saas\PlatformIncidents')
@php
    $now = \Carbon\CarbonImmutable::now(PlatformIncidents::TIMEZONE)->format('Y-m-d\TH:i');
@endphp

@section('content')
<h1>{{ __('Breach and incident register') }}</h1>
<p class="saas-lead">{{ __('Every security incident, the schools it affects and when each was told, whether the Information Regulator must be told, what was done and how it closed. The schools are the responsible parties for their learners\' information; Navigate Ed, as their operator, tells them as soon as there is reason to believe personal information was reached.') }}</p>
<p>{{ __('Work through the incident runbook (engineering/runbooks/incident.md) and record each step here.') }}</p>

<h2>{{ __('Open incidents') }}</h2>
@if ($open === [])
    <p class="saas-card">{{ __('No open incidents.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Open incidents') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Incident') }}</th><th scope="col">{{ __('Severity') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Detected') }}</th><th scope="col">{{ __('Schools') }}</th><th scope="col">{{ __('Needs attention') }}</th></tr></thead>
        <tbody>
        @foreach ($open as $incident)
            <tr>
                <td><a href="{{ route('saas.incidents.show', $incident['id']) }}">{{ $incident['reference'] }}</a></td>
                <th scope="row">{{ $incident['title'] }}</th>
                <td><span class="saas-badge">{{ __(PlatformIncidents::SEVERITIES[$incident['severity']]) }}</span></td>
                <td>{{ __(PlatformIncidents::STATUSES[$incident['status']]) }}</td>
                <td>{{ $incident['detected_at'] }} · {{ trans_choice('{0} today|{1} 1 day ago|[2,*] :count days ago', $incident['days_open'], ['count' => $incident['days_open']]) }}</td>
                <td>{{ $incident['schools'] }}</td>
                <td>{{ $incident['attention'] === [] ? __('Nothing outstanding') : implode('; ', $incident['attention']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="record">{{ __('Record an incident') }}</h2>
<form method="POST" action="{{ route('saas.incidents.store') }}" class="saas-card" aria-label="{{ __('Record an incident') }}">
    @csrf
    <p>{{ __('Describe what happened, not who it happened to: never write a learner\'s, parent\'s or staff member\'s name here. Refer to records by their id.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="incident-title">{{ __('Short title') }}</label>
            <input id="incident-title" name="title" required minlength="5" maxlength="191" value="{{ old('title') }}">
        </div>
        <div class="saas-field">
            <label for="incident-category">{{ __('Kind of incident') }}</label>
            <select id="incident-category" name="category" required>
                @foreach (PlatformIncidents::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-severity">{{ __('Severity') }}</label>
            <select id="incident-severity" name="severity" required>
                @foreach (PlatformIncidents::SEVERITIES as $value => $label)<option value="{{ $value }}" @selected(old('severity', 'medium') === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-personal">{{ __('Was personal information, or may it have been, reached by someone not authorised?') }}</label>
            <select id="incident-personal" name="personal_information" required>
                @foreach (PlatformIncidents::PERSONAL as $value => $label)<option value="{{ $value }}" @selected(old('personal_information', 'unknown') === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-detected">{{ __('Detected (South African time)') }}</label>
            <input id="incident-detected" name="detected_at" type="datetime-local" required max="{{ $now }}" value="{{ old('detected_at', $now) }}">
        </div>
        <div class="saas-field">
            <label for="incident-occurred">{{ __('Happened, if known') }}</label>
            <input id="incident-occurred" name="occurred_at" type="datetime-local" max="{{ $now }}" value="{{ old('occurred_at') }}">
        </div>
        <div class="saas-field">
            <label for="incident-schools">{{ __('Affected schools (choose none if not known yet)') }}</label>
            <select id="incident-schools" name="schools[]" multiple size="6">
                @foreach ($schoolChoices as $id => $name)<option value="{{ $id }}" @selected(in_array((string) $id, array_map('strval', old('schools', [])), true))>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-summary">{{ __('What happened and what is known so far') }}</label>
            <textarea id="incident-summary" name="summary" required minlength="10" maxlength="4000" rows="5">{{ old('summary') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record the incident') }}</button></div>
</form>

@if ($closed !== [])
<h2>{{ __('Recently closed') }}</h2>
<ul>
    @foreach ($closed as $incident)
        <li><a href="{{ route('saas.incidents.show', $incident['id']) }}">{{ $incident['reference'] }}</a> · {{ $incident['title'] }} · {{ __(PlatformIncidents::SEVERITIES[$incident['severity']]) }} · {{ trans_choice('{0} no schools|{1} 1 school|[2,*] :count schools', $incident['schools'], ['count' => $incident['schools']]) }}</li>
    @endforeach
</ul>
@endif
@endsection
