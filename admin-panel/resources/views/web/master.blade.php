<!DOCTYPE html>
@php
    $lang = Session::get('language');
    $about = DB::table('web_settings')->where('name', 'about_us')->where('status', 1)->first();
    $whoweare = DB::table('web_settings')->where('name', 'who_we_are')->where('status', 1)->first();
    $teacher = DB::table('web_settings')->where('name', 'teacher')->where('status', 1)->first();
    $photo = DB::table('web_settings')->where('name', 'photos')->where('status', 1)->first();
    $video = DB::table('web_settings')->where('name', 'videos')->where('status', 1)->first();
    $question = DB::table('web_settings')->where('name', 'question')->where('status', 1)->first();
    $registration = DB::table('web_settings')->where('name', 'registration')->where('status', 1)->first();
@endphp
{{-- The public website, built on the Material 3 library (public/assets/m3, wiki/UI-Standards.md). No Bootstrap. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="m3" @if ($lang && $lang->is_rtl) dir="rtl" @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ url(Storage::url(schoolSetting('favicon'))) }}" />

    {{-- Third-party: brand icons, the carousels and the date picker. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
    <link rel="stylesheet" href="{{ asset('/assets/css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/flatpickr-theme.css') }}">

    {{-- Material 3: the design tokens (generated from the apps' theme), the library, then the site. --}}
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}?v={{ filemtime(public_path('assets/css/m3-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/m3/m3.css') }}?v={{ filemtime(public_path('assets/m3/m3.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/site.css') }}?v={{ filemtime(public_path('assets/css/site.css')) }}">
    @yield('css')

    <script>
        var baseUrl = "{{ URL::to('/') }}";
        const onErrorImage = (e) => {
            e.target.onerror = null;
            e.target.src = "{{ asset('assets/no-image.svg') }}";
        };
        // Labels the site's scripts (script.js) put on buttons and snackbars.
        window.siteLabels = {!! json_encode([
            'close' => __('close'),
            'previous' => __('Previous'),
            'next' => __('Next'),
            'day' => __('Day'),
            'to' => __('To'),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    </script>
</head>

<body class="m3">
    <a class="m3-skip-link" href="#main">{{ __('Skip to content') }}</a>

    @include('web.header')

    <main id="main" class="site-main" tabindex="-1">
        @yield('content')
    </main>

    @include('web.footer')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="{{ asset('/assets/m3/m3.js') }}?v={{ filemtime(public_path('assets/m3/m3.js')) }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('/assets/js/jquery-additional-methods.min.js') }}"></script>
    <script src="{{ asset('/assets/js/flatpickr.min.js') }}"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script src="{{ asset('/assets/js/custom/function.js') }}?v={{ filemtime(public_path('assets/js/custom/function.js')) }}"></script>
    <script src="{{ asset('/assets/js/custom/validate.js') }}"></script>
    <script src="{{ asset('/assets/js/script.js') }}?v={{ filemtime(public_path('assets/js/script.js')) }}"></script>

    {{-- Flash messages from the last request, as snackbars. --}}
    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                M3.snackbar({ message: @json($error), type: 'error', closeLabel: siteLabels.close });
            @endforeach
        </script>
    @endif
    @if (Session::has('success'))
        <script>
            M3.snackbar({ message: @json(Session::get('success')), type: 'success', closeLabel: siteLabels.close });
        </script>
    @endif

    @yield('js')

    @yield('script')
</body>

</html>
