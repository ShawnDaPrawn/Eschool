<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    {{-- These pages carry a one-time token: don't let search engines keep them. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('learner_setup_title') }} || {{ config('app.name') }}</title>
    @include('layouts.include')
</head>

<body>
    <div class="container-scroller">
        <div class="content-wrapper d-flex align-items-start justify-content-center auth learner-setup">
            <div class="row flex-grow w-100 m-0">
                <div class="col-lg-5 col-md-8 mx-auto">
                    <main class="auth-form-light text-left p-5">
                        <div class="brand-logo text-center">
                            <img src="{{ schoolLogoUrl('logo1') }}" alt="{{ config('app.name') }}">
                        </div>
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
