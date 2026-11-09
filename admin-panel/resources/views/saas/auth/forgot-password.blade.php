@extends('saas.auth.layout')
@section('title', __('Reset your business account password'))
@section('content')
<form method="POST" action="{{ route('saas.password.email') }}" class="saas-card">
    @csrf
    <div class="saas-field"><label for="email">{{ __('Email') }}</label><input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required></div>
    <div class="saas-actions"><button type="submit" class="saas-btn">{{ __('Send password reset link') }}</button></div>
</form>
<p><a href="{{ route('saas.login') }}">{{ __('Back to business sign in') }}</a></p>
@endsection
