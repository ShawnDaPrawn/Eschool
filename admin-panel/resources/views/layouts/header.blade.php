{{--
    Panel header: menu toggle, logo, global search, the always-visible school
    year switcher, cache clear (Super Admin), language and the account menu.
    Styles: public/assets/css/header.css and panel.css; behaviour: panel.js.
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
    $headerYears = \App\Models\SessionYear::query()->select('id', 'name', 'start_date', 'end_date')->orderBy('start_date')->orderBy('id')->get();
@endphp
<header class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand header-logo brand-logo" href="{{ route('home') }}">
            <img src="{{ schoolLogoUrl('logo1') }}" alt="{{ config('app.name') }}">
        </a>
        <a class="navbar-brand brand-logo-mini" href="{{ route('home') }}">
            <img src="{{ schoolSetting('favicon') ? asset('storage/' . schoolSetting('favicon')) : asset('assets/logo.svg') }}" alt="{{ config('app.name') }}">
        </a>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler align-self-center d-none d-lg-inline-flex" type="button" data-toggle="minimize"
            aria-label="{{ __('nav_toggle_menu') }}">
            <x-icon name="menu" :size="22" />
        </button>

        @includeWhen(Route::has('search'), 'layouts.partials.search-box')

        <div class="navbar-nav navbar-nav-right panel-header-actions">
            <div class="panel-year d-none d-md-flex">
                <label class="panel-year-label" for="filter_session_year_global">{{ __('nav_school_year') }}</label>
                <select id="filter_session_year_global" data-scope="global" data-selected="{{ $panelYear->selectedId() }}"
                    class="session-year-dropdown filter_session_year js-year-switcher">
                    @foreach ($headerYears as $year)
                        <option value="{{ $year->id }}"
                            data-start-date="{{ date('d-m-Y', strtotime($year->start_date)) }}"
                            data-end-date="{{ date('d-m-Y', strtotime($year->end_date)) }}"
                            @selected($year->id === $panelYear->selectedId())>
                            {{ $year->name }}@if ($year->id === $panelYear->currentId()) ({{ __('nav_current_year') }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            @if (Auth::user()->hasRole('Super Admin'))
                <form action="{{ url('clear') }}" method="POST" class="m-0 d-none d-lg-flex align-items-center" data-year-safe>
                    @csrf
                    <button type="submit" class="topbar-action-btn" title="{{ __('cache_clear') }}">
                        <x-icon name="refresh" :size="16" />
                        <span class="topbar-action-label">{{ __('cache_clear') }}</span>
                    </button>
                </form>
            @endif

            <div class="nav-item dropdown d-none d-md-flex">
                <button type="button" class="nav-link count-indicator dropdown-toggle panel-icon-button" id="languageDropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('nav_change_language') }}">
                    <x-icon name="language" :size="20" />
                </button>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown language-dropdown"
                    aria-labelledby="languageDropdown">
                    @foreach (get_language() as $language)
                        <a class="dropdown-item language-item {{ app()->getLocale() == $language->code ? 'active' : '' }}"
                            href="{{ url('set-language') . '/' . $language->code }}"
                            @if (app()->getLocale() == $language->code) aria-current="true" @endif>
                            <span class="language-name">{{ $language->name }}</span>
                            @if (app()->getLocale() == $language->code)
                                <x-icon name="check" :size="16" class="language-check" />
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <ul class="navbar-nav m-0">
                @include('layouts.partials.profile-dropdown', ['id' => 'profileDropdown'])
            </ul>
        </div>

        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas" aria-label="{{ __('nav_toggle_menu') }}" aria-controls="sidebar">
            <x-icon name="menu" :size="22" />
        </button>
    </div>
</header>
