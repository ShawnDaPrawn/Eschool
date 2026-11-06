@extends('saas.layout')

@section('title', $group['name'])

@use('App\Services\Platform\Groups\SchoolGroupDashboard')
@section('content')
@php
    $states = ['invited' => __('Invited: waiting for the school'), 'active' => __('In the group'), 'declined' => __('Declined by the school'),
        'withdrawn' => __('Invitation withdrawn'), 'left' => __('Left the group'), 'removed' => __('Removed by HQ')];
    $show = fn (array $cell): string => SchoolGroupDashboard::display($cell, $dashboard['k']);
@endphp
<p><a href="{{ route('saas.groups.index') }}">{{ __('School groups') }}</a>@if ($canSeeBilling) · <a href="{{ route('saas.groups.invoices', $group['id']) }}">{{ __('Group invoices') }}</a>@endif</p>
<h1>{{ $group['name'] }}</h1>
@if ($group['notes'])<p class="saas-lead">{{ $group['notes'] }}</p>@endif

<h2>{{ __('Figures across the group') }}</h2>
<p>{{ __('As the group\'s administrators see them, computed now from the schools in the group. Counts and shares only; numbers from 1 to :max are hidden, and a group total is hidden when it would reveal one.', ['max' => $dashboard['k'] - 1]) }}</p>
@include('saas.partials.group-figures', ['dashboard' => $dashboard, 'show' => $show])

<h2>{{ __('Schools') }}</h2>
@if ($members === [])
    <p class="saas-card">{{ __('No school has been invited yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Schools') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Agreement') }}</th><th scope="col">{{ __('Billed') }}</th><th scope="col">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($members as $member)
            <tr data-member="{{ $member['id'] }}">
                <th scope="row">{{ $member['school'] }}</th>
                <td>{{ $states[$member['status']] ?? $member['status'] }}<br><small>{{ __('Invited :date', ['date' => $member['invited_at']]) }}</small>@if ($member['ended_at'])<br><small>{{ __('Ended :date', ['date' => $member['ended_at']]) }}@if ($member['end_reason']): {{ $member['end_reason'] }}@endif</small>@endif</td>
                <td>@if ($member['decided_at']){{ __(':name, :date', ['name' => $member['decided_by'] ?? '', 'date' => $member['decided_at']]) }}@if ($member['agreement_version'])<br><small>{{ __('Wording :version', ['version' => $member['agreement_version']]) }}</small>@endif @else – @endif</td>
                <td>
                    {{ $member['billing'] === 'group' ? __('Through the group') : __('On its own') }}
                    @if ($canBill && in_array($member['status'], ['invited', 'active'], true))
                    <form method="POST" action="{{ route('saas.groups.schools.billing', [$group['id'], $member['id']]) }}" class="saas-inline" aria-label="{{ __('How :school is billed', ['school' => $member['school']]) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="billing" value="{{ $member['billing'] === 'group' ? 'school' : 'group' }}">
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ $member['billing'] === 'group' ? __('Bill on its own') : __('Bill through the group') }}</button>
                    </form>
                    @endif
                </td>
                <td>
                    @if ($canManage && in_array($member['status'], ['invited', 'active'], true))
                    <form method="POST" action="{{ route('saas.groups.schools.end', [$group['id'], $member['id']]) }}" aria-label="{{ $member['status'] === 'invited' ? __('Withdraw the invitation to :school', ['school' => $member['school']]) : __('Remove :school', ['school' => $member['school']]) }}">
                        @csrf
                        <div class="saas-field"><label for="end-reason-{{ $member['id'] }}">{{ __('Reason') }}</label><input id="end-reason-{{ $member['id'] }}" name="reason" required minlength="10" maxlength="500"></div>
                        <button class="saas-btn saas-btn--danger" type="submit">{{ $member['status'] === 'invited' ? __('Withdraw the invitation') : __('Remove from the group') }}</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($canManage)
<form method="POST" action="{{ route('saas.groups.schools.store', $group['id']) }}" class="saas-card" aria-label="{{ __('Invite a school') }}">
    @csrf
    <h2>{{ __('Invite a school') }}</h2>
    <p>{{ __('The school\'s Super Admin sees the invitation on the School groups page of their panel, with exactly what the group will see. Nothing is shared until they agree. A school can be in one group at a time.') }}</p>
    @if ($invitable === [])
        <p>{{ __('Every active school is already in a group or invited to one.') }}</p>
    @else
    <div class="saas-grid">
        <div class="saas-field">
            <label for="invite-school">{{ __('School') }}</label>
            <select id="invite-school" name="school_id" required>@foreach ($invitable as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
        </div>
        <div class="saas-field">
            <label for="invite-note">{{ __('Note to the school (optional)') }}</label>
            <textarea id="invite-note" name="note" maxlength="500" rows="2">{{ old('note') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Invite the school') }}</button></div>
    @endif
</form>
@endif

<h2>{{ __('Group administrators') }}</h2>
<p>{{ __('They see the figures above for the schools in the group, and nothing else. Being a group administrator gives no access inside any school: a person who also works at one of the schools reaches it only through their own role there.') }}</p>
@if ($admins === [])
    <p class="saas-card">{{ __('No group administrators yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Group administrators') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Person') }}</th><th scope="col">{{ __('Reason') }}</th><th scope="col">{{ __('State') }}</th></tr></thead>
        <tbody>
        @foreach ($admins as $admin)
            <tr data-admin="{{ $admin['id'] }}">
                <th scope="row">{{ $admin['name'] }}<br><small>{{ $admin['email'] }}</small></th>
                <td>{{ $admin['reason'] }}</td>
                <td>
                    @if ($admin['standing'])
                        {{ __('Active') }}
                        @if ($canManage)
                        <form method="POST" action="{{ route('saas.groups.admins.revoke', [$group['id'], $admin['id']]) }}" aria-label="{{ __('Revoke :name', ['name' => $admin['name']]) }}">
                            @csrf
                            <div class="saas-field"><label for="revoke-reason-{{ $admin['id'] }}">{{ __('Reason') }}</label><input id="revoke-reason-{{ $admin['id'] }}" name="reason" required minlength="10" maxlength="500"></div>
                            <button class="saas-btn saas-btn--danger" type="submit">{{ __('Revoke') }}</button>
                        </form>
                        @endif
                    @else
                        {{ __('Revoked :date', ['date' => $admin['revoked_at']]) }}: {{ $admin['revoke_reason'] }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($canManage)
<form method="POST" action="{{ route('saas.groups.admins.store', $group['id']) }}" class="saas-card" aria-label="{{ __('Add a group administrator') }}">
    @csrf
    <h2>{{ __('Add a group administrator') }}</h2>
    <p>{{ __('The person needs a NavigateEd account already. They sign in as usual and open the group\'s page from the address /group.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="admin-email">{{ __('Email address of their account') }}</label>
            <input id="admin-email" name="email" type="email" required maxlength="191" value="{{ old('email') }}">
        </div>
        <div class="saas-field">
            <label for="admin-reason">{{ __('Reason') }}</label>
            <textarea id="admin-reason" name="reason" required minlength="10" maxlength="500" rows="2">{{ old('reason') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the administrator') }}</button></div>
</form>
@endif
@endsection
