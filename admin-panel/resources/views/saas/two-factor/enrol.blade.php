@extends('saas.layout')

@section('title', __('Set up two-factor sign-in'))

@section('content')
<h1>{{ __('Set up two-factor sign-in') }}</h1>
<p class="saas-lead">{{ __('The platform area needs a code from an authenticator app on your phone as well as your password. Set it up once; after that, each time you sign in you type the code the app shows.') }}</p>

<div class="saas-card">
    <h2>{{ __('1. Scan this code with your authenticator app') }}</h2>
    <p>{{ __('Use an app such as Google Authenticator, Microsoft Authenticator or 1Password, and choose to add an account with a QR code.') }}</p>
    <div class="saas-qr" role="img" aria-label="{{ __('QR code for your authenticator app') }}">{!! $qr !!}</div>
    <p>{{ __('Cannot scan it? Choose to enter a setup key instead, and type this key (time-based):') }}</p>
    <p class="saas-secret" aria-label="{{ __('Setup key') }}">{{ trim(chunk_split($secret, 4, ' ')) }}</p>
    <p><small>{{ __('Keep this key to yourself. Nobody at Navigate Ed will ever ask you for it.') }}</small></p>
</div>

<form method="POST" action="{{ route('saas.two-factor.confirm') }}" class="saas-card" aria-labelledby="confirm-heading">
    @csrf
    <h2 id="confirm-heading">{{ __('2. Confirm with your password and the code your app shows') }}</h2>
    <div class="saas-field">
        <label for="current_password">{{ __('Your current password') }}</label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        <label for="two_factor_code">{{ __('6-digit code') }}</label>
        <input id="two_factor_code" name="two_factor_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Turn on two-factor sign-in') }}</button></div>
</form>
@endsection
