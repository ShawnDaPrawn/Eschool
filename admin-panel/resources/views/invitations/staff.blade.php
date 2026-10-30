<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('Choose your password') }} · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/saas.css') }}">
</head>
<body class="saas">
<main class="saas-main" id="main">
    <h1>{{ __('Welcome to :school', ['school' => $invitation['school']]) }}</h1>
    <p class="saas-lead">{{ __('Hello :name. Choose the password you will use to sign in as :email.', ['name' => $invitation['first_name'], 'email' => $invitation['email']]) }}</p>
    @if ($errors->any())
        <div class="saas-error" role="alert">
            <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('staff-invitation.accept', ['token' => $token]) }}" class="saas-card" aria-label="{{ __('Choose your password') }}">
        @csrf
        <div class="saas-field">
            <label for="password">{{ __('New password') }}</label>
            <input type="password" id="password" name="password" aria-describedby="password-rules" autocomplete="new-password" required minlength="{{ $minimum }}" maxlength="191">
            <small id="password-rules">{{ __('password_rules_hint') }}</small>
            <label for="password_confirmation">{{ __('Confirm new password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required minlength="{{ $minimum }}" maxlength="191">
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Set password') }}</button></div>
    </form>
    <p>{{ __('This link works once and expires on :date.', ['date' => $invitation['expires_at']->timezone('Africa/Johannesburg')->format('j M Y H:i')]) }}</p>
</main>
</body>
</html>
