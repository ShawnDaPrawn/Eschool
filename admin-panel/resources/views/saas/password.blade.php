@extends('saas.layout')
@section('title', __('Change your platform password'))
@section('content')
<h1>{{ __('Change your platform password') }}</h1>
<p>{{ __('This updates only your own account. It does not grant access to any school.') }}</p>
<form method="POST" action="{{ route('saas.password.store') }}" class="saas-card" data-platform-password data-success-url="{{ route('saas.index') }}">
    @csrf
    <div class="saas-field">
        <label for="old_password">{{ __('Current password') }}</label>
        <input type="password" id="old_password" name="old_password" autocomplete="current-password" required>
        <label for="new_password">{{ __('New password') }}</label>
        <input type="password" id="new_password" name="new_password" aria-describedby="password-rules" autocomplete="new-password" required minlength="{{ \App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH }}">
        <small id="password-rules">{{ __('password_rules_hint') }}</small>
        <label for="confirm_password">{{ __('Confirm new password') }}</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
    </div>
    <p role="alert" data-password-error hidden>{{ __('Could not change your password. Check the current password and password rules, then try again.') }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Change password') }}</button></div>
</form>
<p data-password-retired hidden>{{ __('This password form closed while the page was hidden.') }} <a href="{{ route('saas.password') }}">{{ __('Open a fresh password form') }}</a></p>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/saas-password.js') }}" defer></script>
@endsection
