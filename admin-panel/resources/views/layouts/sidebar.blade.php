{{--
    Revision 3 school navigation: twelve modules, with People and Community
    grouped dropdowns. Detailed actions live in their module workspaces. Built from config/navigation.php by
    App\Services\Panel\Navigation, so an item shows only when its page exists and
    the person has one of its permissions. A Material 3 navigation drawer (the
    Dark Navy --inverse variant): each section is a disclosure button over
    groups of links, and the section holding the current page starts open.
    Behaviour: public/assets/js/custom/sidebar.js; styles: sidebar.css.
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
    $navSections = app(\App\Services\Panel\Navigation::class)->sections(Auth::user());
    $sidebarYears = \App\Models\SessionYear::query()->select('id', 'name')->orderBy('start_date')->orderBy('id')->get();
@endphp
<nav class="sidebar sidebar-offcanvas m3 m3-navigation-drawer m3-navigation-drawer--inverse panel-nav" id="sidebar"
    aria-label="{{ __('nav_main') }}" data-native-scroll>
    {{-- Small screens: the year and school switchers and the close button live at the top of the drawer. --}}
    <div class="panel-nav__mobile">
        <label class="m3-text-field m3-text-field--select panel-field--compact panel-nav__year">
            <select id="filter_session_year_global_mobile" data-scope="global" data-selected="{{ $panelYear->selectedId() }}"
                class="m3-text-field__input js-year-switcher" aria-labelledby="panel-nav-year-label">
                @foreach ($sidebarYears as $year)
                    <option value="{{ $year->id }}" @selected($year->id === $panelYear->selectedId())>
                        {{ $year->name }}@if ($year->id === $panelYear->currentId()) ({{ __('nav_current_year') }})@endif
                    </option>
                @endforeach
            </select>
            <span class="m3-text-field__label" id="panel-nav-year-label">{{ __('nav_school_year') }}</span>
        </label>
        @include('layouts.partials.school-switcher', ['suffix' => '-mobile', 'class' => 'panel-nav__school'])
        @canany(['session-year-create','session-year-edit'])
            <a class="m3-button m3-button--text panel-nav__year-management" href="{{ route('new-year.index') }}">{{ __('Prepare school year') }}</a>
        @endcanany
        <div class="panel-nav__utilities">
            @if(Route::has('search'))
                <a class="m3-navigation-drawer__item panel-nav__search" href="{{ route('search') }}" data-panel-nav-search>
                    <x-icon name="search" :size="24" />
                    <span>{{ __('search_title') }}</span>
                </a>
            @endif
            <label class="m3-text-field m3-text-field--select panel-field--compact panel-nav__language" for="panel-nav-language">
                <select id="panel-nav-language" class="m3-text-field__input" data-panel-language-switch>
                    @foreach(get_language() as $language)
                        <option value="{{ url('set-language').'/'.$language->code }}" lang="{{ $language->code }}" @selected(app()->getLocale() === $language->code)>{{ $language->name }}</option>
                    @endforeach
                </select>
                <span class="m3-text-field__label">{{ __('nav_change_language') }}</span>
            </label>
        </div>
        <button type="button" id="sidebar-close" class="m3-icon-button panel-nav__close" aria-label="{{ __('close') }}">
            <x-icon name="close" :size="24" />
        </button>
    </div>

    <ul class="panel-nav__list">
        @foreach ($navSections as $section)
            @if ($section['single'])
                <li class="panel-nav__section {{ $section['active'] ? 'is-active' : '' }}" data-nav-section="{{ $section['key'] }}">
                    <a class="m3-navigation-drawer__item panel-nav__link" href="{{ $section['url'] }}" @if ($section['active']) aria-current="page" @endif>
                        <x-icon :name="$section['icon']" :size="24" class="panel-nav__icon" />
                        <span class="panel-nav__label">{{ $section['label'] }}</span>
                    </a>
                </li>
            @else
                <li class="panel-nav__section {{ $section['active'] ? 'is-active' : '' }}" data-nav-section="{{ $section['key'] }}">
                    <button type="button" class="m3-navigation-drawer__item panel-nav__toggle" data-panel-nav-section
                        aria-expanded="{{ $section['active'] ? 'true' : 'false' }}"
                        aria-controls="nav-section-{{ $section['key'] }}">
                        <x-icon :name="$section['icon']" :size="24" class="panel-nav__icon" />
                        <span class="panel-nav__label">{{ $section['label'] }}</span>
                        <x-icon name="chevron-down" :size="20" class="panel-nav__chevron" />
                    </button>
                    <div class="panel-nav__panel" id="nav-section-{{ $section['key'] }}" @unless ($section['active']) hidden @endunless>
                        <ul class="panel-nav__items">
                            @foreach ($section['groups'] as $group)
                                <li data-nav-item="{{ $section['key'] }}-{{ $group['key'] }}">
                                    <a class="panel-nav__item" href="{{ $group['url'] }}"
                                        @if ($group['active']) aria-current="page" @endif>{{ $group['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
