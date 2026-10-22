{{--
    Navigation by job: Home, People, Academics, Communication, Money,
    Activities, Wellbeing, Settings. Built from config/navigation.php by
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
    {{-- Small screens: the year switcher and close button live at the top of the drawer. --}}
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
                        @foreach ($section['groups'] as $group)
                            @if (count($section['groups']) > 1)
                                <p class="panel-nav__group" id="nav-group-{{ $section['key'] }}-{{ $group['key'] }}">{{ $group['label'] }}</p>
                            @endif
                            <ul class="panel-nav__items"
                                @if (count($section['groups']) > 1) aria-labelledby="nav-group-{{ $section['key'] }}-{{ $group['key'] }}" @endif>
                                @foreach ($group['items'] as $item)
                                    <li data-nav-item="{{ $item['key'] }}">
                                        <a class="panel-nav__item" href="{{ $item['url'] }}"
                                            @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
