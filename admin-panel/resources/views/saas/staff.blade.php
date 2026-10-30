@extends('saas.layout')

@section('title', __('Platform staff'))

@section('content')
<h1>{{ __('Platform staff') }}</h1>
<p class="saas-lead">{{ __('Who can use the platform area, in which role, and until when. Platform staff hold no role at any school. Every change is kept in the audit log with its reason.') }}</p>

<div class="saas-card">
    <table class="saas-table">
        <caption>{{ __('Platform staff and their access') }}</caption>
        <thead>
        <tr>
            <th scope="col">{{ __('Name') }}</th>
            <th scope="col">{{ __('Role') }}</th>
            <th scope="col">{{ __('Access') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($staff as $person)
            <tr>
                <th scope="row">{{ $person['name'] }}<br><small>{{ $person['email'] }}</small></th>
                <td>{{ $person['role_label'] ?? __('None') }}</td>
                <td>
                    @if ($person['current'])
                        {{ __('Until :date', ['date' => $person['expires_at']->setTimezone('Africa/Johannesburg')->format('Y-m-d')]) }}
                    @else
                        <span class="saas-badge saas-badge--warn">{{ __('No access') }}</span>
                    @endif
                    @if ($person['invitation_pending'])
                        <br><small>{{ __('Invitation not accepted yet') }}</small>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@foreach ($staff as $person)
    <h2 id="staff-{{ $person['id'] }}">{{ $person['name'] }}</h2>
    <form method="POST" action="{{ route('saas.staff.update', $person['id']) }}" class="saas-card" aria-label="{{ __('Change access for :name', ['name' => $person['name']]) }}">
        @csrf
        @method('PUT')
        <div class="saas-grid">
            <div class="saas-field">
                <label for="role-{{ $person['id'] }}">{{ __('Role') }}</label>
                <select id="role-{{ $person['id'] }}" name="role">
                    @foreach ($roles as $key => $label)
                        <option value="{{ $key }}" @selected($person['role'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="saas-field">
                <label for="days-{{ $person['id'] }}">{{ __('Access for (days from today)') }}</label>
                <input id="days-{{ $person['id'] }}" name="days" type="number" min="1" max="{{ $maxDays }}" value="90" required>
            </div>
            <div class="saas-field">
                <label for="reason-{{ $person['id'] }}">{{ __('Reason (kept in the audit log)') }}</label>
                <textarea id="reason-{{ $person['id'] }}" name="reason" required minlength="10" maxlength="500" rows="2"></textarea>
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save access') }}</button></div>
    </form>
    @if ($person['current'] && $person['id'] !== $me)
        <form method="POST" action="{{ route('saas.staff.revoke', $person['id']) }}" class="saas-card" aria-label="{{ __('Revoke access for :name', ['name' => $person['name']]) }}">
            @csrf
            <div class="saas-field">
                <label for="revoke-reason-{{ $person['id'] }}">{{ __('Reason for revoking (kept in the audit log)') }}</label>
                <textarea id="revoke-reason-{{ $person['id'] }}" name="reason" required minlength="10" maxlength="500" rows="2"></textarea>
            </div>
            <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Revoke access') }}</button></div>
        </form>
    @endif
    @if ($person['invitation_pending'] || (! $person['invited'] && $person['current']))
        <form method="POST" action="{{ route('saas.staff.invitation', $person['id']) }}" class="saas-inline" aria-label="{{ __('Send a new invitation to :name', ['name' => $person['name']]) }}">
            @csrf
            <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Send a new invitation') }}</button>
        </form>
    @endif
@endforeach

<h2 id="invite">{{ __('Invite a staff member') }}</h2>
<form method="POST" action="{{ route('saas.staff.store') }}" class="saas-card" aria-labelledby="invite">
    @csrf
    <p>{{ __('They are emailed a private link to choose their own password. Use an address that has no school account.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="invite-first">{{ __('First name') }}</label>
            <input id="invite-first" name="first_name" value="{{ old('first_name') }}" maxlength="128" autocomplete="off" required>
        </div>
        <div class="saas-field">
            <label for="invite-last">{{ __('Last name') }}</label>
            <input id="invite-last" name="last_name" value="{{ old('last_name') }}" maxlength="128" autocomplete="off" required>
        </div>
        <div class="saas-field">
            <label for="invite-email">{{ __('Email') }}</label>
            <input id="invite-email" name="email" type="email" value="{{ old('email') }}" maxlength="191" autocomplete="off" required>
        </div>
        <div class="saas-field">
            <label for="invite-role">{{ __('Role') }}</label>
            <select id="invite-role" name="role">
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" @selected(old('role', 'support') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="invite-days">{{ __('Access for (days from today)') }}</label>
            <input id="invite-days" name="days" type="number" min="1" max="{{ $maxDays }}" value="{{ old('days', 90) }}" required>
        </div>
        <div class="saas-field">
            <label for="invite-reason">{{ __('Reason (kept in the audit log)') }}</label>
            <textarea id="invite-reason" name="reason" required minlength="10" maxlength="500" rows="2">{{ old('reason') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Send invitation') }}</button></div>
</form>
@endsection
