<!DOCTYPE html>
@php
    $lang = Session::get('language');
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
@endphp
<html lang="{{ $lang->code ?? app()->getLocale() }}" @if ($lang && $lang->is_rtl) dir="rtl" @endif>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') || {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.include')
    @yield('css')
</head>

{{--
    The admin panel shell: header (search, year switcher, account), the
    navigation by job (config/navigation.php) and the main area. Every panel
    page extends this layout. data-panel-layout marks panel pages for the
    Playwright checks; data-read-only-year switches page.js's read-only mode.
--}}
<body class="sidebar-fixed" data-panel-layout
    @if ($panelYear->isReadOnly()) data-read-only-year="{{ $panelYear->selectedId() }}" @endif>
    <a class="skip-link" href="#main-content">{{ __('skip_to_content') }}</a>
    <div class="container-scroller">

        @include('layouts.header')

        <div class="container-fluid page-body-wrapper">

            @include('layouts.sidebar')

            <main class="main-panel d-flex flex-column min-vh-100" id="main-content" tabindex="-1">

                @include('layouts.partials.read-only-banner')
                @include('layouts.partials.notices')

                @yield('content')

                <div class="mt-auto">
                    @include('layouts.footer')
                </div>

            </main>

        </div>

    </div>

    @include('layouts.footer_js')

    {{-- After Update Notes Modal --}}
    @include('after-update-note-modal')

    @php
        $panelShell = [
            'readOnly' => $panelYear->isReadOnly(),
            'readOnlyMessage' => $panelYear->isReadOnly()
                ? __('read_only_year_refused', ['year' => $panelYear->selected()?->name, 'current' => $panelYear->current()?->name])
                : null,
            'readOnlyAllow' => config('panel.read_only_years.allow', []),
            'setYearUrl' => route('set.session.year'),
            'baseUrl' => url('/'),
        ];
    @endphp
    <script>
        window.panelShell = @json($panelShell);
    </script>
    <script src="{{ asset('/assets/js/custom/panel.js') }}?v={{ filemtime(public_path('assets/js/custom/panel.js')) }}"></script>

    @yield('js')

    @yield('script')
</body>

</html>
