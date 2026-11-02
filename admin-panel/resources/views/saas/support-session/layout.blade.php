<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · {{ __('Support session') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/saas.css') }}">
</head>
<body class="saas">
<header class="saas-bar" role="banner">
    <a href="{{ route('support-session.show', $session['grant']) }}">{{ __('Support session: :school', ['school' => $session['school']]) }}</a>
    <nav aria-label="{{ __('Support session') }}">
        <a href="{{ route('support-session.show', $session['grant']) }}">{{ __('Overview') }}</a>
        <a href="{{ route('support-session.people', $session['grant']) }}">{{ __('People') }}</a>
        <a href="{{ route('saas.support.show', $session['grant']) }}">{{ __('Back to HQ') }}</a>
    </nav>
    {{-- Ending is a request to HQ, never a write inside the session. --}}
    <form method="POST" action="{{ route('saas.support.end', $session['grant']) }}">
        @csrf
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('End the session now') }}</button>
    </form>
</header>
<main class="saas-main" id="main">
    <p class="saas-error" role="status">{{ __('Read-only support session. Every page you open is logged and shown to the school. It ends at :time (South African time).', ['time' => $session['expires_at']]) }}@if ($session['emergency']) {{ __('Opened by emergency override.') }}@endif</p>
    @yield('content')
</main>
</body>
</html>
