@extends('saas.auth.layout')
@section('title', __('Reset your business account password'))
@section('content')
<form method="POST" action="{{ route('saas.password.update') }}" class="saas-card">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="saas-field">
        <label for="email">{{ __('Email') }}</label><input id="email" name="email" type="email" autocomplete="username" value="{{ old('email', $email) }}" required>
        <label for="password">{{ __('New password') }}</label><input id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-rules" required>
        <small id="password-rules">{{ __('password_rules_hint') }}</small>
        <label for="password_confirmation">{{ __('Confirm new password') }}</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
    </div>
    <div class="saas-actions"><button type="submit" class="saas-btn">{{ __('Reset password') }}</button></div>
</form>
<p><a href="{{ route('saas.login') }}">{{ __('Back to business sign in') }}</a></p>
@endsection
