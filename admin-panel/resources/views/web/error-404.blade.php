<!DOCTYPE html>
@php
    $lang = Session::get('language');
@endphp
{{-- The website's "page not found" page (WebController::errorPage), on the Material 3 library. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="m3" @if ($lang && $lang->is_rtl) dir="rtl" @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ url(Storage::url(schoolSetting('favicon'))) }}" />
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}?v={{ filemtime(public_path('assets/css/m3-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/m3/m3.css') }}?v={{ filemtime(public_path('assets/m3/m3.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/site.css') }}?v={{ filemtime(public_path('assets/css/site.css')) }}">
</head>

<body class="m3 site-error-page">
    <main class="m3-page site-error">
        <div class="site-error__message">
            <h1 class="site-error__code">404</h1>
            <div class="site-error__text">
                <h2 class="site-error__title">SORRY!</h2>
                <h3 class="site-error__lead">The page you’re looking for was not found.</h3>
            </div>
        </div>
        <a class="m3-button m3-button--inverse" href="{{ url('/') }}">{{ __('back_to_home') }}</a>
        <p class="site-error__copyright">Copyright &copy; <?= date('Y') ?> Tokensoft, Pretoria. All rights reserved.</p>
    </main>
</body>

</html>
