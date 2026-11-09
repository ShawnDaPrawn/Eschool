@extends('saas.auth.layout')
@section('title', __('Business administration sign in'))
@section('content')
<p>{{ __('Sign in with your NavigateEd business account to manage the platform.') }}</p>
<form method="POST" action="{{ route('saas.login.store') }}" class="saas-card">
    @csrf
    <div class="saas-field">
        <label for="email">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
        <label for="password">{{ __('Password') }}</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <div class="saas-actions"><button type="submit" class="saas-btn">{{ __('Sign in') }}</button></div>
</form>
<p><a href="{{ route('saas.password.request') }}">{{ __('Forgot your password?') }}</a></p>
@endsection
