@extends('layouts.school-auth')
@section('title', __('Reset your password'))
@section('description', __('Enter your account email. If it can receive a reset link, instructions will be sent to it.'))
@section('content')
<form method="POST" action="{{ route('password.email') }}" class="school-auth__form">
    @csrf
    <x-page.field id="email" name="email" type="email" :label="__('email')" :value="old('email')" required autocomplete="email" autofocus />
    <button type="submit" name="btnlogin" class="m3-button m3-button--filled">{{ __('Send Password Reset Link') }}</button>
    <a class="m3-button m3-button--text" href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
</form>
@endsection
