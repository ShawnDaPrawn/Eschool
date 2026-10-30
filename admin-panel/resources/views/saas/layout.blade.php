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
@php
    $platformCan = fn (string $ability): bool => auth()->check() && app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), $ability);
@endphp
<header class="saas-bar">
    <a href="{{ auth()->check() ? route('saas.index') : route('login') }}">{{ __('NavigateEd platform') }}</a>
    @auth
    <nav aria-label="{{ __('Platform') }}">
        @if ($platformCan('schools.create'))<a href="{{ route('saas.schools.create') }}">{{ __('Set up a school') }}</a>@endif
        @if ($platformCan('packages.view'))<a href="{{ route('saas.packages') }}">{{ __('Product modules and packages') }}</a>@endif
        @if ($platformCan('health.view'))<a href="{{ route('saas.health') }}">{{ __('School health') }}</a>@endif
        @if ($platformCan('audit.view'))<a href="{{ route('saas.audit') }}">{{ __('Audit log') }}</a>@endif
        @if ($platformCan('identity.review'))<a href="{{ route('saas.lurits') }}">{{ __('LURITS claims') }}</a>@endif
        @if ($platformCan('staff.manage'))<a href="{{ route('saas.staff.index') }}">{{ __('Platform staff') }}</a>@endif
    </nav>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Sign out') }}</button>
    </form>
    @endauth
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
