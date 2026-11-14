@extends('layouts.school-auth')
@section('title', __('Choose a new password'))
@section('description', __('Use a new password that you do not use for another account.'))
@section('content')
<form method="POST" action="{{ route('password.update') }}" class="school-auth__form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-page.field id="email" name="email" type="email" :label="__('email')" :value="$email ?? old('email')" required autocomplete="email" />
    <div class="school-auth__password">
        <x-page.field id="password" name="password" type="password" :label="__('new_password')" :restore-old-input="false" required autocomplete="new-password" :minlength="\App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH" :help="__('password_rules_hint')" />
        <button type="button" class="m3-button m3-button--text" data-auth-password-toggle aria-controls="password" aria-pressed="false" data-show-label="{{ __('Show') }}" data-hide-label="{{ __('Hide') }}">{{ __('Show') }}</button>
    </div>
    <x-page.field id="password-confirm" name="password_confirmation" type="password" :label="__('confirm_password')" :restore-old-input="false" required autocomplete="new-password" />
    <button type="submit" name="btnlogin" class="m3-button m3-button--filled">{{ __('Reset Password') }}</button>
    <a class="m3-button m3-button--text" href="{{ route('password.request') }}">{{ __('Request a new reset link') }}</a>
</form>
@endsection
