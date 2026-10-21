{{--
    Navigation by job: Home, People, Academics, Communication, Money,
    Activities, Wellbeing, Settings. Built from config/navigation.php by
    App\Services\Panel\Navigation, so an item shows only when its page exists and
    the person has one of its permissions. Each section is a disclosure button
    (Bootstrap collapse) over groups of links; the section holding the current
    page starts open. Styles: public/assets/css/sidebar.css and panel.css.
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
    $navSections = app(\App\Services\Panel\Navigation::class)->sections(Auth::user());
    $sidebarYears = \App\Models\SessionYear::query()->select('id', 'name')->orderBy('start_date')->orderBy('id')->get();
@endphp
<nav class="sidebar sidebar-offcanvas panel-nav" id="sidebar" aria-label="{{ __('nav_main') }}">
    <ul class="nav">
        {{-- Small screens: the year switcher and close button live at the top of the drawer. --}}
        <li class="nav-item d-md-none sidebar-mobile-header">
            <div class="sidebar-mobile-controls">
                <div class="sidebar-mobile-top">
                    <div class="sidebar-mobile-session">
                        <label class="sr-only" for="filter_session_year_global_mobile">{{ __('nav_school_year') }}</label>
                        <select id="filter_session_year_global_mobile" data-scope="global"
                            class="session-year-dropdown sidebar-session-dropdown js-year-switcher">
                            @foreach ($sidebarYears as $year)
                                <option value="{{ $year->id }}" @selected($year->id === $panelYear->selectedId())>
                                    {{ $year->name }}@if ($year->id === $panelYear->currentId()) ({{ __('nav_current_year') }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="sidebar-close" class="sidebar-close-btn" aria-label="{{ __('close') }}">
                        <x-icon name="close" :size="20" />
                    </button>
                </div>
            </div>
        </li>

        @foreach ($navSections as $section)
            @if ($section['single'])
                <li class="nav-item {{ $section['active'] ? 'active' : '' }}" data-nav-section="{{ $section['key'] }}">
                    <a class="nav-link" href="{{ $section['url'] }}" @if ($section['active']) aria-current="page" @endif>
                        <x-icon :name="$section['icon']" class="menu-icon" />
                        <span class="menu-title">{{ $section['label'] }}</span>
                    </a>
                </li>
            @else
                <li class="nav-item nav-section {{ $section['active'] ? 'active' : '' }}" data-nav-section="{{ $section['key'] }}">
                    <button type="button" class="nav-link nav-section-toggle" data-toggle="collapse"
                        data-target="#nav-section-{{ $section['key'] }}"
                        aria-expanded="{{ $section['active'] ? 'true' : 'false' }}"
                        aria-controls="nav-section-{{ $section['key'] }}">
                        <x-icon :name="$section['icon']" class="menu-icon" />
                        <span class="menu-title">{{ $section['label'] }}</span>
                        <x-icon name="chevron-down" class="menu-arrow" />
                    </button>
                    <div class="collapse {{ $section['active'] ? 'show' : '' }}" id="nav-section-{{ $section['key'] }}">
                        @foreach ($section['groups'] as $group)
                            @if (count($section['groups']) > 1)
                                <p class="nav-group-label" id="nav-group-{{ $section['key'] }}-{{ $group['key'] }}">{{ $group['label'] }}</p>
                            @endif
                            <ul class="nav flex-column sub-menu"
                                @if (count($section['groups']) > 1) aria-labelledby="nav-group-{{ $section['key'] }}-{{ $group['key'] }}" @endif>
                                @foreach ($group['items'] as $item)
                                    <li class="nav-item" data-nav-item="{{ $item['key'] }}">
                                        <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}"
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
