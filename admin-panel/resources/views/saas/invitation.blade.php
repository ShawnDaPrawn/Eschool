@extends('saas.layout')

@section('title', __('Choose your password'))

@section('content')
<h1>{{ __('Choose your password') }}</h1>
<p class="saas-lead">{{ __('Welcome, :name. This sets the password for :email in the NavigateEd platform area. It gives no access to any school.', ['name' => $invitation['first_name'], 'email' => $invitation['email']]) }}</p>
<form method="POST" action="{{ route('platform-invitation.accept', $token) }}" class="saas-card">
    @csrf
    <div class="saas-field">
        <label for="password">{{ __('New password') }}</label>
        <input type="password" id="password" name="password" aria-describedby="password-rules" autocomplete="new-password" required minlength="{{ \App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH }}">
        <small id="password-rules">{{ __('password_rules_hint') }}</small>
        <label for="password_confirmation">{{ __('Confirm new password') }}</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
    </div>
    <p>{{ __('Next you sign in and set up an authenticator app on your phone (such as Google Authenticator, Microsoft Authenticator or 1Password). The platform area always asks for a code from it.') }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Set password') }}</button></div>
</form>
@endsection
