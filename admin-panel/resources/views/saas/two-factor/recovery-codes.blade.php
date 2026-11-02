@extends('saas.layout')

@section('title', __('Your recovery codes'))

@section('content')
<h1>{{ __('Two-factor sign-in is on') }}</h1>
<p class="saas-lead">{{ __('These are your recovery codes. If you lose your phone, each one lets you in once instead of a code from the app.') }}</p>

<div class="saas-card">
    <h2>{{ __('Save them now: they are shown only this once') }}</h2>
    <p>{{ __('Keep them in your password manager or print them and keep them somewhere safe. Navigate Ed keeps only a scrambled copy and cannot show them to you again.') }}</p>
    <ol class="saas-codes" aria-label="{{ __('Recovery codes') }}">
        @foreach ($codes as $code)
            <li><code>{{ $code }}</code></li>
        @endforeach
    </ol>
    <p>{{ __('When the codes run out or your phone is lost, ask an Owner to reset your two-factor sign-in.') }}</p>
    <div class="saas-actions"><a class="saas-btn" href="{{ $next }}">{{ __('I have saved my codes. Continue') }}</a></div>
</div>
@endsection
