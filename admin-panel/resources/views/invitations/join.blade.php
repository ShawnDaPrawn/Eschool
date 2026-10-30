<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('Your invitation') }} · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/saas.css') }}">
</head>
<body class="saas">
<main class="saas-main" id="main">
@if ($invitation === null)
    <h1>{{ __('This invitation is for another account') }}</h1>
    <div class="saas-error" role="alert">
        <p>{{ __('This invitation is for another account. Sign out, then open the link again and sign in as the person it was sent to.') }}</p>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="saas-actions">
        @csrf
        <button class="saas-btn" type="submit">{{ __('Sign out') }}</button>
    </form>
@else
    <h1>{{ $invitation['first_administrator'] ? __('Be the first administrator of :school', ['school' => $invitation['school']]) : __('Join :school', ['school' => $invitation['school']]) }}</h1>
    <p class="saas-lead">{{ __(':school has invited you to join it on NavigateEd as :role.', ['school' => $invitation['school'], 'role' => $invitation['role']]) }}</p>
    <div class="saas-card">
        <p>{{ __('If you accept, :school can see your name, email address and the other details on your NavigateEd account, and you can work there with this role. Accepting does not change your password. :school cannot change your account details or your place at your other schools; it can only end your role at :school.', ['school' => $invitation['school']]) }}</p>
        <div class="saas-actions">
            <form method="POST" action="{{ route('join-invitation.accept', ['token' => $token]) }}">
                @csrf
                <button class="saas-btn" type="submit">{{ __('Accept and join') }}</button>
            </form>
            <form method="POST" action="{{ route('join-invitation.decline', ['token' => $token]) }}">
                @csrf
                <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Decline') }}</button>
            </form>
        </div>
    </div>
    <p>{{ __('This invitation expires on :date.', ['date' => $invitation['expires_at']->timezone('Africa/Johannesburg')->format('j M Y H:i')]) }}</p>
@endif
</main>
</body>
</html>
