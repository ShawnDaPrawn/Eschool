<!DOCTYPE html>
@php
    $lang = Session::get('language');
@endphp
{{-- Public pages where a learner's password is set (LearnerSetupController), on the Material 3 library. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="m3" @if ($lang && $lang->is_rtl) dir="rtl" @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- These pages carry a one-time token: search engines must not keep them. --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('learner_setup_title') }} | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}?v={{ filemtime(public_path('assets/css/m3-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/m3/m3.css') }}?v={{ filemtime(public_path('assets/m3/m3.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/learner-setup.css') }}?v={{ filemtime(public_path('assets/css/learner-setup.css')) }}">
</head>

<body class="m3">
    <main class="m3-page learner-setup">
        <section class="m3-card m3-card--outlined learner-setup__card">
            <div class="m3-card__content m3-stack m3-gap-4">
                <img class="learner-setup__logo" src="{{ schoolLogoUrl('logo1') }}" alt="{{ config('app.name') }}">
                @yield('content')
            </div>
        </section>
    </main>
    @yield('script')
</body>

</html>
