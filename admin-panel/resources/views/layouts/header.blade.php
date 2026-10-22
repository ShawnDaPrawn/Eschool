{{--
    Panel header, a Material 3 top app bar: the menu toggle, logo, global
    search, the always-visible school year switcher, cache clear (Super Admin),
    language and the account menu. Built with the Material 3 library
    (public/assets/m3); menus open with data-m3-menu (m3.js). Styles:
    public/assets/css/panel.css ("Header"); behaviour: panel.js, sidebar.js.
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
    $headerYears = \App\Models\SessionYear::query()->select('id', 'name', 'start_date', 'end_date')->orderBy('start_date')->orderBy('id')->get();
    $locale = app()->getLocale();
@endphp
<header class="panel-topbar m3">
    <div class="panel-topbar__brand">
        <a class="panel-topbar__logo panel-topbar__logo--full" href="{{ route('home') }}">
            <img src="{{ schoolLogoUrl('logo1') }}" alt="{{ config('app.name') }}">
        </a>
        <a class="panel-topbar__logo panel-topbar__logo--mini" href="{{ route('home') }}">
            <img src="{{ schoolSetting('favicon') ? asset('storage/' . schoolSetting('favicon')) : asset('assets/logo.svg') }}" alt="{{ config('app.name') }}">
        </a>
    </div>
    <div class="panel-topbar__bar">
        {{-- Wide screens: shrink the navigation to its icons. Small screens: open it as a drawer. --}}
        <button type="button" class="m3-icon-button panel-topbar__menu panel-topbar__menu--minimize" data-panel-nav-minimize
            aria-controls="sidebar" aria-expanded="true" aria-label="{{ __('nav_toggle_menu') }}">
            <x-icon name="menu" :size="24" />
        </button>
        <button type="button" class="m3-icon-button panel-topbar__menu panel-topbar__menu--drawer" data-panel-nav-open
            aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('nav_toggle_menu') }}">
            <x-icon name="menu" :size="24" />
        </button>

        @includeWhen(Route::has('search'), 'layouts.partials.search-box')

        <div class="panel-topbar__actions">
            <div class="panel-year">
                <label class="m3-text-field m3-text-field--select panel-field--compact panel-year__field">
                    <select id="filter_session_year_global" data-scope="global" data-selected="{{ $panelYear->selectedId() }}"
                        class="m3-text-field__input filter_session_year js-year-switcher" aria-labelledby="panel-year-label">
                        @foreach ($headerYears as $year)
                            <option value="{{ $year->id }}"
                                data-start-date="{{ date('d-m-Y', strtotime($year->start_date)) }}"
                                data-end-date="{{ date('d-m-Y', strtotime($year->end_date)) }}"
                                @selected($year->id === $panelYear->selectedId())>
                                {{ $year->name }}@if ($year->id === $panelYear->currentId()) ({{ __('nav_current_year') }})@endif
                            </option>
                        @endforeach
                    </select>
                    <span class="m3-text-field__label" id="panel-year-label">{{ __('nav_school_year') }}</span>
                </label>
            </div>

            @if (Auth::user()->hasRole('Super Admin'))
                <form action="{{ url('clear') }}" method="POST" class="panel-topbar__clear" data-year-safe>
                    @csrf
                    <button type="submit" class="m3-button m3-button--outlined m3-button--icon-leading panel-topbar__clear-button"
                        data-m3-tooltip="{{ __('cache_clear') }}">
                        <x-icon name="refresh" :size="18" />
                        <span class="panel-topbar__clear-label">{{ __('cache_clear') }}</span>
                    </button>
                </form>
            @endif

            <button type="button" class="m3-icon-button panel-topbar__language" data-m3-menu="panel-language-menu"
                aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('nav_change_language') }}">
                <x-icon name="language" :size="24" />
            </button>
            <div class="m3-menu panel-menu" id="panel-language-menu" popover role="menu" aria-label="{{ __('nav_change_language') }}">
                @foreach (get_language() as $language)
                    <a class="m3-menu__item panel-menu__item" role="menuitem" href="{{ url('set-language') . '/' . $language->code }}"
                        @if ($locale == $language->code) aria-current="true" @endif lang="{{ $language->code }}">
                        <span class="panel-menu__text">{{ $language->name }}</span>
                        @if ($locale == $language->code)
                            <x-icon name="check" :size="20" />
                        @endif
                    </a>
                @endforeach
            </div>

            @include('layouts.partials.profile-dropdown', ['id' => 'profileDropdown'])
        </div>
    </div>
</header>
