@extends('saas.layout')

@section('title', __('Two-factor sign-in'))

@section('content')
<h1>{{ __('Two-factor sign-in') }}</h1>
<p class="saas-lead">{{ __('Open your authenticator app and type the code it shows for NavigateEd HQ.') }}</p>

<form method="POST" action="{{ route('saas.two-factor.verify') }}" class="saas-card" aria-label="{{ __('Two-factor sign-in') }}">
    @csrf
    <div class="saas-field">
        <label for="two_factor_code">{{ __('Code from your app, or a recovery code') }}</label>
        <input id="two_factor_code" name="two_factor_code" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" maxlength="64" aria-describedby="two-factor-help" required autofocus>
        <small id="two-factor-help">{{ __('A code from the app has 6 digits. A recovery code looks like ABCD-EFGH-IJKL-MNOP and works once.') }}</small>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Continue') }}</button></div>
</form>
<p>{{ __('Lost your phone and your recovery codes? Ask an Owner to reset your two-factor sign-in.') }}</p>
@endsection
