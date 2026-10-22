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
    page extends this layout. The shell is built with the Material 3 library
    (public/assets/m3); the pages inside it keep their own markup until they
    move. data-panel-layout marks panel pages for the Playwright checks;
    data-read-only-year switches panel.js's read-only mode.
--}}
<body class="sidebar-fixed" data-panel-layout
    @if ($panelYear->isReadOnly()) data-read-only-year="{{ $panelYear->selectedId() }}" @endif>
    <a class="m3-skip-link" href="#main-content">{{ __('skip_to_content') }}</a>
    <div class="container-scroller">

        @include('layouts.header')

        <div class="page-body-wrapper panel-body">

            @include('layouts.sidebar')

            <main class="main-panel panel-main" id="main-content" tabindex="-1">

                @include('layouts.partials.read-only-banner')
                @include('layouts.partials.notices')

                @yield('content')

                <div class="panel-main__footer">
                    @include('layouts.footer')
                </div>

            </main>

        </div>

    </div>

    @include('layouts.footer_js')

    {{-- After Update Notes Modal --}}
    @include('after-update-note-modal')

    @include('components.page.confirm-dialog')

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
    <script src="{{ asset('/assets/m3/m3.js') }}?v={{ filemtime(public_path('assets/m3/m3.js')) }}"></script>
    <script src="{{ asset('/assets/js/custom/panel.js') }}?v={{ filemtime(public_path('assets/js/custom/panel.js')) }}"></script>
    <script src="{{ asset('/assets/js/custom/page.js') }}?v={{ filemtime(public_path('assets/js/custom/page.js')) }}"></script>

    @yield('js')

    @yield('script')
</body>

</html>
