<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · {{ __('NavigateEd platform') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/saas.css') }}">
</head>
<body class="saas">
<header class="saas-bar">
    <a href="{{ route('saas.index') }}">{{ __('NavigateEd platform') }}</a>
    <nav aria-label="{{ __('Platform') }}">
        <a href="{{ route('saas.schools.create') }}">{{ __('Set up a school') }}</a>
    </nav>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Sign out') }}</button>
    </form>
</header>
<main class="saas-main" id="main">
    @if ($errors->any())
        <div class="saas-error" role="alert">
            <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif
    @if (session('success'))
        <p class="saas-ok" role="status">{{ session('success') }}</p>
    @endif
    @yield('content')
</main>
@yield('scripts')
</body>
</html>
