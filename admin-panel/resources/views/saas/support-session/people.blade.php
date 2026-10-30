@extends('saas.support-session.layout')

@section('title', __('People'))

@section('content')
<h1>{{ __('People') }}</h1>
<form method="GET" action="{{ route('support-session.people', $session['grant']) }}" class="saas-card" role="search" aria-label="{{ __('Find an account') }}">
    <div class="saas-field">
        <label for="people-q">{{ __('Name or email (at least three characters). Your search is not logged; the page is.') }}</label>
        <input id="people-q" name="q" type="search" minlength="3" maxlength="100" value="{{ $query }}">
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Find') }}</button></div>
</form>

<h2>{{ $searched ? __('Accounts found') : __('Staff accounts') }}</h2>
@if ($people === [])
    <p class="saas-card">{{ $searched ? __('No account at this school matches.') : __('This school has no staff accounts.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Accounts') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Email') }}</th><th scope="col">{{ __('Roles here') }}</th><th scope="col">{{ __('Account') }}</th></tr></thead>
        <tbody>
        @foreach ($people as $person)
            <tr>
                <th scope="row"><a href="{{ route('support-session.person', [$session['grant'], $person['id']]) }}">{{ $person['name'] }}</a></th>
                <td>{{ $person['email'] }}</td>
                <td>{{ implode(', ', $person['roles']) }}</td>
                <td>{{ ucfirst($person['account']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
