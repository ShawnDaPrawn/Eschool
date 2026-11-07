<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/m3/m3.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/school-groups.css') }}">
</head>
{{--
    The school group pages (Action Plan 5.7): a group's administrators work
    for the group, not inside a school, so this shell has no school menus,
    year switcher or school search. Material 3 library only.
--}}
<body class="m3 group-shell">
<a class="m3-skip-link" href="#main">{{ __('skip_to_content') }}</a>
<header class="group-shell__bar">
    <a href="{{ route('group.index') }}" class="group-shell__home">{{ __('School group') }}</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="m3-button m3-button--text" type="submit">{{ __('Sign out') }}</button>
    </form>
</header>
<main id="main" class="group-shell__main">
    @yield('content')
</main>
</body>
</html>
