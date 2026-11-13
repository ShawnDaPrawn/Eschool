<!DOCTYPE html>
@php($authLanguage = Session::get('language'))
<html lang="{{ $authLanguage->code ?? app()->getLocale() }}" @if($authLanguage?->is_rtl) dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('assets/navigateed/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/m3-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/m3/m3.css') }}?v={{ filemtime(public_path('assets/m3/m3.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/panel.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/school-auth.css') }}?v={{ filemtime(public_path('assets/css/school-auth.css')) }}">
</head>
<body class="m3 school-auth">
    <a class="m3-skip-link" href="#auth-main">{{ __('skip_to_content') }}</a>
    <div class="school-auth__layout">
        <aside class="school-auth__brand" aria-label="{{ config('app.name') }}">
            <img src="{{ schoolLogoUrl('logo2') }}" alt="{{ config('app.name') }}" class="school-auth__logo">
            <p class="school-auth__eyebrow">{{ __('School administration') }}</p>
            <p class="school-auth__intro">{{ __('Your school. One connected workspace.') }}</p>
            <p>{{ __('Manage your school with secure access to the people, learning and operations you support.') }}</p>
        </aside>
        <main class="school-auth__main" id="auth-main" tabindex="-1">
            <div class="school-auth__card">
                <h1>@yield('title')</h1>
                <p class="school-auth__description">@yield('description')</p>
                @if(session('success') || session('status'))
                    <div class="m3-banner m3-banner--success" role="status">{{ session('success') ?? session('status') }}</div>
                @endif
                @if($errors->any() || session('error'))
                    <div class="m3-banner m3-banner--danger" role="alert" tabindex="-1">
                        @if(session('error'))<p>{{ session('error') }}</p>@endif
                        @if($errors->any())<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    <script src="{{ asset('assets/js/school-auth.js') }}?v={{ filemtime(public_path('assets/js/school-auth.js')) }}" defer></script>
</body>
</html>
