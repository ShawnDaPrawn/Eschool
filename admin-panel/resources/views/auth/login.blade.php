@extends('layouts.school-auth')
@section('title', __('Sign in'))
@section('description', __('Use your school account to open your authorised workspace.'))
@section('content')
<form action="{{ route('login') }}" id="frmLogin" method="POST" class="school-auth__form">
    @csrf
    <x-page.field id="email" type="email" name="email" :label="__('email')" :value="old('email')" required autocomplete="email" autofocus />
    <div class="school-auth__password">
        <x-page.field id="password" type="password" name="password" :label="__('password')" :restore-old-input="false" required autocomplete="current-password" />
        <button id="togglePassword" type="button" class="m3-button m3-button--text" data-auth-password-toggle aria-controls="password" aria-pressed="false" data-show-label="{{ __('Show') }}" data-hide-label="{{ __('Hide') }}">{{ __('Show') }}</button>
    </div>
    <div class="school-auth__links">
        @if(Route::has('password.request'))<a href="{{ route('password.request') }}">{{ __('forgot_password') }}</a>@endif
    </div>
    <button type="submit" name="btnlogin" id="login_btn" class="m3-button m3-button--filled">{{ __('login') }}</button>
</form>
@include('auth.provider-buttons')
@if(config('app.demo_mode'))
    <details class="m3-expansion"><summary class="m3-expansion__header">{{ __('Demo accounts') }}</summary>
        <div class="school-auth__links">
            <button type="button" class="m3-button m3-button--outlined" id="superadmin_btn" data-demo-account data-demo-email="superadmin@gmail.com" data-demo-password="superadmin">{{ __('Super Admin') }}</button>
            <button type="button" class="m3-button m3-button--outlined" id="teacher_btn" data-demo-account data-demo-email="teacher@gmail.com" data-demo-password="teacher123">{{ __('Teacher') }}</button>
            <button type="button" class="m3-button m3-button--outlined" id="staff_btn" data-demo-account data-demo-email="staff@gmail.com" data-demo-password="14081980">{{ __('Staff') }}</button>
        </div>
    </details>
@endif
@endsection
