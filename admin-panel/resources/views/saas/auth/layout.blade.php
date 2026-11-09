<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · NavigateEd</title>
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/saas.css') }}">
</head>
<body class="saas">
<header class="saas-bar"><a href="{{ route('saas.login') }}">NavigateEd</a></header>
<main class="saas-main">
    <h1>@yield('title')</h1>
    @if ($errors->any())
        <div class="saas-error" role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @if (session('status'))<p class="saas-ok" role="status">{{ session('status') }}</p>@endif
    @if (session('success'))<p class="saas-ok" role="status">{{ session('success') }}</p>@endif
    @if (session('error'))<p class="saas-error" role="alert">{{ session('error') }}</p>@endif
    @yield('content')
</main>
</body>
</html>
