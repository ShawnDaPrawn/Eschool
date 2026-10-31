@extends('saas.support-session.layout')

@section('title', __('Account'))

@section('content')
<p><a href="{{ route('support-session.people', $session['grant']) }}">{{ __('People') }}</a></p>
<h1>{{ $person['name'] }}</h1>
<dl class="saas-card">
    <dt>{{ __('Email') }}</dt><dd>{{ $person['email'] }}</dd>
    <dt>{{ __('Account') }}</dt><dd>{{ ucfirst($person['account']) }}</dd>
    <dt>{{ __('Email address confirmed') }}</dt><dd>{{ $person['email_verified'] ? __('Yes') : __('No') }}</dd>
    <dt>{{ __('Must choose a new password') }}</dt><dd>{{ $person['password_change_required'] ? __('Yes') : __('No') }}</dd>
    <dt>{{ __('Staff invitation') }}</dt><dd>{{ $person['invitation'] === null ? __('None') : __(':state (sent :date)', ['state' => ucfirst($person['invitation']['state']), 'date' => $person['invitation']['sent']]) }}</dd>
</dl>

<h2>{{ __('Roles at this school') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Roles at this school') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Role') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('From') }}</th><th scope="col">{{ __('Until') }}</th></tr></thead>
        <tbody>
        @foreach ($person['memberships'] as $membership)
            <tr><th scope="row">{{ $membership['role'] }}</th><td>{{ ucfirst($membership['status']) }}</td><td>{{ $membership['starts_on'] ?? '—' }}</td><td>{{ $membership['ends_on'] ?? '—' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<p>{{ __('Profiles, records, messages and anything pastoral, medical or safeguarding are not shown in a support session.') }}</p>
@endsection
