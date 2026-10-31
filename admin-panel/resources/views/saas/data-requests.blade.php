@extends('saas.layout')

@section('title', __('Data requests'))

@use('App\Services\Platform\Privacy\DataRequests')
@php
    $today = \Carbon\CarbonImmutable::now(DataRequests::TIMEZONE)->toDateString();
@endphp

@section('content')
<h1>{{ __('POPIA data requests') }}</h1>
<p class="saas-lead">{{ __('Requests from people who want to see, correct or delete their personal information, or object to how it is used, that reach Navigate Ed. Each school is the responsible party and decides; Navigate Ed, as its operator, logs the request, passes it to the school straight away and follows it to its deadline.') }}</p>

<h2>{{ __('Open requests') }}</h2>
@if ($open === [])
    <p class="saas-card">{{ __('No open requests.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Open requests') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Request') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Received') }}</th><th scope="col">{{ __('Answer due') }}</th></tr></thead>
        <tbody>
        @foreach ($open as $request)
            <tr>
                <td><a href="{{ route('saas.data-requests.show', $request['id']) }}">{{ $request['reference'] }}</a></td>
                <th scope="row">{{ $request['school'] }}</th>
                <td>{{ __(DataRequests::KINDS[$request['kind']]) }}</td>
                <td>{{ __(DataRequests::STATUSES[$request['status']]) }}</td>
                <td>{{ $request['received_on'] }}</td>
                <td>
                    {{ $request['due_on'] }}
                    @if ($request['overdue'])<span class="saas-badge">{{ __('Overdue') }}</span>
                    @elseif ($request['days_left'] !== null) · {{ trans_choice('{0} due today|{1} 1 day left|[2,*] :count days left', $request['days_left'], ['count' => $request['days_left']]) }}@endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="log">{{ __('Log a request') }}</h2>
<form method="POST" action="{{ route('saas.data-requests.store') }}" class="saas-card" aria-label="{{ __('Log a request') }}">
    @csrf
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach
    <p>{{ __('Record who asked, how to reach them and what they asked for, in their words where you can. Do not copy any of the school\'s records here.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="request-school">{{ __('School the request is about') }}</label>
            <select id="request-school" name="school_id" required>
                <option value="">{{ __('Choose a school') }}</option>
                @foreach ($schoolChoices as $id => $name)<option value="{{ $id }}" @selected((string) old('school_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="request-kind">{{ __('What they ask for') }}</label>
            <select id="request-kind" name="kind" required>
                @foreach (DataRequests::KINDS as $value => $label)<option value="{{ $value }}" @selected(old('kind') === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="request-channel">{{ __('How it reached us') }}</label>
            <select id="request-channel" name="channel" required>
                @foreach (DataRequests::CHANNELS as $value => $label)<option value="{{ $value }}" @selected(old('channel') === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="request-role">{{ __('Who is asking') }}</label>
            <select id="request-role" name="requester_role" required>
                @foreach (DataRequests::REQUESTER_ROLES as $value => $label)<option value="{{ $value }}" @selected(old('requester_role') === $value)>{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="request-name">{{ __('Their name') }}</label>
            <input id="request-name" name="requester_name" required minlength="2" maxlength="191" autocomplete="off" value="{{ old('requester_name') }}">
        </div>
        <div class="saas-field">
            <label for="request-contact">{{ __('How to reach them (email or phone)') }}</label>
            <input id="request-contact" name="requester_contact" required minlength="3" maxlength="191" autocomplete="off" value="{{ old('requester_contact') }}">
        </div>
        <div class="saas-field">
            <label for="request-received">{{ __('Received on') }}</label>
            <input id="request-received" name="received_on" type="date" required max="{{ $today }}" value="{{ old('received_on', $today) }}">
        </div>
        <div class="saas-field">
            <label for="request-due">{{ __('Answer due (leave empty for 30 days after it was received)') }}</label>
            <input id="request-due" name="due_on" type="date" value="{{ old('due_on') }}">
        </div>
        <div class="saas-field">
            <label for="request-details">{{ __('What they asked for') }}</label>
            <textarea id="request-details" name="details" required minlength="10" maxlength="2000" rows="5">{{ old('details') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Log the request') }}</button></div>
</form>

@if ($closed !== [])
<h2>{{ __('Recently closed') }}</h2>
<ul>
    @foreach ($closed as $request)
        <li><a href="{{ route('saas.data-requests.show', $request['id']) }}">{{ $request['reference'] }}</a> · {{ $request['school'] }} · {{ __(DataRequests::KINDS[$request['kind']]) }} · {{ __(DataRequests::STATUSES[$request['status']]) }}</li>
    @endforeach
</ul>
@endif
@endsection
