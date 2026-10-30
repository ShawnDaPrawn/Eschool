@extends('saas.layout')

@section('title', $incident['reference'].' · '.__('Incidents'))

@use('App\Services\Platform\Saas\PlatformIncidents')
@php
    $today = \Carbon\CarbonImmutable::now(PlatformIncidents::TIMEZONE)->toDateString();
    $now = \Carbon\CarbonImmutable::now(PlatformIncidents::TIMEZONE)->format('Y-m-d\TH:i');
    $closed = $incident['status'] === 'closed';
    $listed = array_column($schools, 'id');
    $addable = array_diff_key($schoolChoices, array_flip($listed));
@endphp

@section('content')
<p><a href="{{ route('saas.incidents.index') }}">{{ __('All incidents') }}</a></p>
<h1>{{ $incident['reference'] }} · {{ $incident['title'] }}</h1>
<p class="saas-lead">
    <span class="saas-badge">{{ __(PlatformIncidents::STATUSES[$incident['status']]) }}</span>
    <span class="saas-badge">{{ __(PlatformIncidents::SEVERITIES[$incident['severity']]) }}</span>
    · {{ __(PlatformIncidents::CATEGORIES[$incident['category']]) }}
    · {{ __('Detected :at', ['at' => $incident['detected_at']]) }}@if ($incident['opened_by']) {{ __('by :name', ['name' => $incident['opened_by']]) }}@endif
    @if ($closed) · {{ __('Closed :at', ['at' => $incident['closed_at']]) }}@if ($incident['closed_by']) {{ __('by :name', ['name' => $incident['closed_by']]) }}@endif @endif
</p>
<nav aria-label="{{ __('Incident sections') }}" class="saas-actions">
    <a href="#schools">{{ __('Affected schools') }}</a><a href="#details">{{ __('Details, Regulator and closure') }}</a><a href="#timeline">{{ __('Timeline') }}</a>
</nav>

@if (! $closed && $blockers !== [])
<div class="saas-card" role="region" aria-label="{{ __('Before this incident can close') }}">
    <h2>{{ __('Before this incident can close') }}</h2>
    <ul>@foreach ($blockers as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>
</div>
@endif

<h2 id="schools">{{ __('Affected schools') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Affected schools') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Who to tell') }}</th><th scope="col">{{ __('Told') }}</th>@unless ($closed)<th scope="col">{{ __('Record that the school was told') }}</th>@endunless</tr></thead>
        <tbody>
        @forelse ($schools as $school)
            <tr>
                <th scope="row">{{ $school['name'] }}</th>
                <td>
                    @forelse ($school['contacts'] as $contact)
                        <div>{{ $contact['role'] === 'information_officer' ? __('Information Officer') : __('Principal') }}: {{ $contact['name'] }}@if ($contact['email']) · {{ $contact['email'] }}@endif @if ($contact['phone']) · {{ $contact['phone'] }}@endif</div>
                    @empty
                        {{ __('No Information Officer or principal recorded on the school record.') }}
                    @endforelse
                </td>
                <td>
                    @if ($school['notified_on'])
                        {{ $school['notified_on'] }} · {{ __(PlatformIncidents::NOTIFIED_HOW[$school['notified_how']]) }} · {{ $school['notified_contact'] }}@if ($school['notified_by']) · {{ __('recorded by :name', ['name' => $school['notified_by']]) }}@endif
                    @else
                        <strong>{{ __('Not told yet') }}</strong>
                    @endif
                </td>
                @unless ($closed)
                <td>
                    <form method="POST" action="{{ route('saas.incidents.schools.notified', [$incident['id'], $school['id']]) }}" aria-label="{{ __('Record that :school was told', ['school' => $school['name']]) }}">
                        @csrf
                        @method('PUT')
                        <label for="told-on-{{ $school['id'] }}">{{ __('Date') }}</label>
                        <input id="told-on-{{ $school['id'] }}" name="notified_on" type="date" required max="{{ $today }}" value="{{ $school['notified_on'] ?? $today }}">
                        <label for="told-how-{{ $school['id'] }}">{{ __('How') }}</label>
                        <select id="told-how-{{ $school['id'] }}" name="notified_how" required>
                            @foreach (PlatformIncidents::NOTIFIED_HOW as $value => $label)<option value="{{ $value }}" @selected($school['notified_how'] === $value)>{{ __($label) }}</option>@endforeach
                        </select>
                        <label for="told-who-{{ $school['id'] }}">{{ __('Whom (role and name)') }}</label>
                        <input id="told-who-{{ $school['id'] }}" name="notified_contact" required minlength="2" maxlength="191" value="{{ $school['notified_contact'] }}">
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ $school['notified_on'] ? __('Correct') : __('Record') }}</button>
                    </form>
                </td>
                @endunless
            </tr>
        @empty
            <tr><td colspan="{{ $closed ? 3 : 4 }}">{{ __('No affected school recorded yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    @if (! $closed && $addable !== [])
    <form method="POST" action="{{ route('saas.incidents.schools.store', $incident['id']) }}" aria-label="{{ __('Add an affected school') }}">
        @csrf
        <label for="add-school">{{ __('Add an affected school') }}</label>
        <select id="add-school" name="school_id" required>
            @foreach ($addable as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
        </select>
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Add the school') }}</button>
    </form>
    @endif
</div>

<h2 id="details">{{ __('Details, Regulator and closure') }}</h2>
<form method="POST" action="{{ route('saas.incidents.update', $incident['id']) }}" class="saas-card" aria-label="{{ __('Incident details') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="revision" value="{{ $incident['revision'] }}">
    @if ($closed)<p>{{ __('This incident is closed. Set the status back to open or contained to change it.') }}</p>@endif
    <div class="saas-grid">
        <div class="saas-field">
            <label for="incident-title">{{ __('Short title') }}</label>
            <input id="incident-title" name="title" required minlength="5" maxlength="191" value="{{ old('title', $incident['title']) }}">
        </div>
        <div class="saas-field">
            <label for="incident-status">{{ __('Status') }}</label>
            <select id="incident-status" name="status" required>
                @foreach (PlatformIncidents::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $incident['status']) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-category">{{ __('Kind of incident') }}</label>
            <select id="incident-category" name="category" required>
                @foreach (PlatformIncidents::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(old('category', $incident['category']) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-severity">{{ __('Severity') }}</label>
            <select id="incident-severity" name="severity" required>
                @foreach (PlatformIncidents::SEVERITIES as $value => $label)<option value="{{ $value }}" @selected(old('severity', $incident['severity']) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-personal">{{ __('Was personal information, or may it have been, reached by someone not authorised?') }}</label>
            <select id="incident-personal" name="personal_information" required>
                @foreach (PlatformIncidents::PERSONAL as $value => $label)<option value="{{ $value }}" @selected(old('personal_information', $incident['personal_information']) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-occurred">{{ __('Happened, if known') }}</label>
            <input id="incident-occurred" name="occurred_at" type="datetime-local" max="{{ $now }}" value="{{ old('occurred_at', $incident['occurred_at'] ? str_replace(' ', 'T', $incident['occurred_at']) : '') }}">
        </div>
        <div class="saas-field">
            <label for="incident-summary">{{ __('What happened and what is known so far') }}</label>
            <textarea id="incident-summary" name="summary" required minlength="10" maxlength="4000" rows="5">{{ old('summary', $incident['summary']) }}</textarea>
        </div>
    </div>

    <h3>{{ __('Information Regulator') }}</h3>
    <p>{{ __('Each school decides, as the responsible party, whether to notify the Information Regulator and the people affected; record here what was decided and when the Regulator was told.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="incident-regulator">{{ __('Must the Regulator be told?') }}</label>
            <select id="incident-regulator" name="regulator">
                <option value="">{{ __('Not decided yet') }}</option>
                @foreach (PlatformIncidents::REGULATOR as $value => $label)<option value="{{ $value }}" @selected(old('regulator', $incident['regulator']) === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="incident-regulator-reason">{{ __('Why') }}</label>
            <input id="incident-regulator-reason" name="regulator_reason" maxlength="500" value="{{ old('regulator_reason', $incident['regulator_reason']) }}">
        </div>
        <div class="saas-field">
            <label for="incident-regulator-on">{{ __('Date the Regulator was told') }}</label>
            <input id="incident-regulator-on" name="regulator_notified_on" type="date" max="{{ $today }}" value="{{ old('regulator_notified_on', $incident['regulator_notified_on']) }}">
        </div>
        <div class="saas-field">
            <label for="incident-regulator-reference">{{ __('Regulator reference') }}</label>
            <input id="incident-regulator-reference" name="regulator_reference" maxlength="64" value="{{ old('regulator_reference', $incident['regulator_reference']) }}">
        </div>
    </div>

    <h3>{{ __('Closure') }}</h3>
    <div class="saas-field">
        <label for="incident-closure">{{ __('What happened, the impact, the cause, what fixed it and the follow-up work (needed to close)') }}</label>
        <textarea id="incident-closure" name="closure_summary" maxlength="4000" rows="5">{{ old('closure_summary', $incident['closure_summary']) }}</textarea>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the incident') }}</button></div>
</form>

<h2 id="timeline">{{ __('Timeline') }}</h2>
<form method="POST" action="{{ route('saas.incidents.entries.store', $incident['id']) }}" class="saas-card" aria-label="{{ __('Add to the timeline') }}">
    @csrf
    <div class="saas-grid">
        <div class="saas-field">
            <label for="entry-kind">{{ __('Kind') }}</label>
            <select id="entry-kind" name="kind" required>
                @foreach (PlatformIncidents::ENTRY_KINDS as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="entry-body">{{ __('What was seen or done (no names of learners, parents or staff)') }}</label>
            <textarea id="entry-body" name="body" required minlength="3" maxlength="1000" rows="2"></textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn saas-btn--quiet" type="submit">{{ __('Add to the timeline') }}</button></div>
</form>
<ol class="saas-card">
    @foreach ($entries as $entry)
        <li><time>{{ $entry['at'] }}</time>@if ($entry['actor']) · {{ $entry['actor'] }}@endif · {{ $entry['body'] }}</li>
    @endforeach
</ol>
@endsection
