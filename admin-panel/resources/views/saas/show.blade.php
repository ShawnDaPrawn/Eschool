@extends('saas.layout')

@section('title', $school['name'])

@section('content')
@php
    $platformCan = fn (string $ability): bool => app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), $ability);
@endphp
<h1>{{ $school['name'] }}</h1>
<p class="saas-lead">
    <span class="saas-badge {{ $school['status'] === 'active' ? '' : 'saas-badge--warn' }}">{{ __(ucfirst($school['status'])) }}</span>
    · {{ $school['slug'] }} · {{ $school['timezone'] }}
    · {{ $school['people'] }} {{ __('active people') }}
    ({{ $school['roles']['Student'] }} {{ __('learners') }}, {{ $school['roles']['Parent'] }} {{ __('parents') }}, {{ $school['roles']['Teacher'] }} {{ __('teachers') }})
</p>

@if ($pendingAdministrator && $platformCan('schools.create'))
<h2>{{ __('First administrator') }}</h2>
<form method="POST" action="{{ route('saas.schools.invitation', $school['id']) }}" class="saas-card">
    @csrf
    <p>{{ __('The first administrator has not accepted their invitation yet. Sending a new one cancels the old link.') }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Send the invitation again') }}</button></div>
</form>
@endif

<h2>{{ __('Package') }}</h2>
@if ($platformCan('schools.package'))
<form method="POST" action="{{ route('saas.schools.package', $school['id']) }}" class="saas-card">
    @csrf
    @method('PUT')
    <div class="saas-actions">
        <div class="saas-field">
            <label for="package_key">{{ __('Package') }}</label>
            <select id="package_key" name="package_key">
                @foreach ($packages as $package)
                    <option value="{{ $package }}" @selected(($school['package_key'] ?? 'legacy') === $package)>{{ $package }}</option>
                @endforeach
            </select>
        </div>
        <button class="saas-btn" type="submit">{{ __('Change package') }}</button>
    </div>
</form>
@else
<p class="saas-card">{{ $school['package_key'] ?? __('Upgraded seed (legacy)') }}</p>
@endif

<h2>{{ __('Status') }}</h2>
@if ($platformCan('schools.status'))
<form method="POST" action="{{ route('saas.schools.status', $school['id']) }}" class="saas-card">
    @csrf
    @method('PUT')
    @if ($school['is_default'])
        <p>{{ __('This is the install\'s default school. Public pages and sign-in run in it, so it cannot be suspended or archived.') }}</p>
    @else
        <p>{{ __('A suspended or archived school\'s people cannot sign in. Nothing is deleted, and the school can be restored.') }}</p>
        <div class="saas-grid">
            <div class="saas-field">
                <label for="status">{{ __('Status') }}</label>
                <select id="status" name="status">
                    @foreach (['active', 'suspended', 'archived'] as $status)
                        <option value="{{ $status }}" @selected($school['status'] === $status)>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="saas-field">
                <label for="reason">{{ __('Reason (kept in the audit log)') }}</label>
                <textarea id="reason" name="reason" required minlength="10" maxlength="500" rows="2"></textarea>
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Change status') }}</button></div>
    @endif
</form>
@else
<p class="saas-card">{{ __(ucfirst($school['status'])) }}</p>
@endif

<h2>{{ __('Modules') }}</h2>
<div class="saas-card">
    <table class="saas-table">
        <caption>{{ __('Module availability at this school, and what its package includes') }}</caption>
        <thead><tr><th scope="col">{{ __('Module') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Change') }}</th></tr></thead>
        <tbody>
        @foreach ($modules as $key => $module)
            <tr>
                <th scope="row">{{ __($module['name']) }}</th>
                <td>{{ __($module['read_only'] && $module['enabled'] ? 'Read-only' : ($module['enabled'] ? 'On' : 'Off')) }}</td>
                <td>
                    @if ($module['always_on'] ?? false)
                        {{ __('Required') }}
                    @elseif (! $module['in_package'])
                        {{ __('Not in this package') }}
                    @elseif (! $platformCan('schools.modules'))
                        {{ __('Your role cannot change modules') }}
                    @else
                        <form class="saas-inline" method="POST" action="{{ route('saas.schools.modules', [$school['id'], $key]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="enabled" value="{{ $module['enabled'] && ! $module['read_only'] ? '0' : '1' }}">
                            <button class="saas-btn saas-btn--quiet" type="submit" aria-label="{{ __($module['enabled'] && ! $module['read_only'] ? 'Switch off' : 'Enable') }} {{ __($module['name']) }}">{{ __($module['enabled'] && ! $module['read_only'] ? 'Switch off' : 'Enable') }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="saas-actions">
    @if ($platformCan('accounts.manage'))<a class="saas-btn" href="{{ route('saas.schools.account', $school['id']) }}">{{ __('School record, contacts and contracts') }}</a>@endif
    <a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.onboarding', $school['id']) }}">{{ __('Onboarding checklist') }}</a>
    @if ($platformCan('offboarding.view') && ! $school['is_default'])<a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.offboarding', $school['id']) }}">{{ __('Offboarding') }}</a>@endif
    <a class="saas-btn saas-btn--quiet" href="{{ route('saas.index') }}">{{ __('All schools') }}</a>
    @if ($platformCan('audit.view'))<a class="saas-btn saas-btn--quiet" href="{{ route('saas.audit', ['school' => $school['id']]) }}">{{ __('Audit log for this school') }}</a>@endif
</p>
@endsection
