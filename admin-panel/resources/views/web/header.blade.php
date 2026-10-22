{{--
    Site header: a contact bar, then a Material 3 top app bar. From 1024px wide the
    navigation sits in the bar (Gallery and Language open M3 menus); below that it
    moves into a modal navigation drawer opened by the menu button.
    Uses $settings (controller) and the section flags set in web.master.
--}}
@php
    $schoolEmail = isset($settings['school_email']) ? $settings['school_email'] : 'info@tokensoft.tech';
    $schoolPhone = isset($settings['school_phone']) ? $settings['school_phone'] : '+27 12 345 6789';
    $currentLanguage = Session::has('language') ? Session::get('language')->name : __('language');
    $languages = get_language();
@endphp

<div class="site-topbar">
    <div class="m3-page site-topbar__inner">
        <ul class="site-topbar__contact">
            <li>
                <a href="mailto:{{ $settings['school_email'] }}">
                    <x-icon name="mail" class="m3-icon" />{{ $schoolEmail }}
                </a>
            </li>
            <li>
                <a href="tel:{{ $settings['school_phone'] }}">
                    <x-icon name="phone" class="m3-icon" /><bdi dir="ltr">{{ $schoolPhone }}</bdi>
                </a>
            </li>
        </ul>
        <div class="site-topbar__social">
            <span class="site-topbar__follow">{{ __('follow_us') }}:</span>
            <a class="m3-icon-button m3-icon-button--small" href="{{ isset($settings['facebook']) ? $settings['facebook'] : '' }}"
                target="_blank" rel="noopener" aria-label="Facebook">
                <i class="fa-brands fa-facebook-square" aria-hidden="true"></i>
            </a>
            <a class="m3-icon-button m3-icon-button--small" href="{{ isset($settings['linkedin']) ? $settings['linkedin'] : '' }}"
                target="_blank" rel="noopener" aria-label="LinkedIn">
                <i class="fa-brands fa-linkedin" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

<header class="m3-top-app-bar m3-top-app-bar--sticky site-app-bar" data-m3-scroll>
    <div class="m3-page site-app-bar__inner">
        <a class="site-logo" href="{{ url('/') }}">
            <img src="{{ schoolLogoUrl('logo1') }}" height="50" width="150" alt="logo">
        </a>

        <nav class="site-nav" aria-label="{{ __('menu') }}">
            <ul class="site-nav__list">
                <li>
                    <a class="m3-button m3-button--text site-nav__link" href="{{ url('/') }}"
                        @if (request()->is('/')) aria-current="page" @endif>{{ __('home') }}</a>
                </li>
                @if ($about || $whoweare || $teacher)
                    <li>
                        <a class="m3-button m3-button--text site-nav__link" href="{{ route('about.us') }}"
                            @if (request()->routeIs('about.us')) aria-current="page" @endif>{{ __('about-us') }}</a>
                    </li>
                @endif
                @if ($photo || $video)
                    <li>
                        <button type="button" class="m3-button m3-button--text site-nav__link m3-button--icon-trailing"
                            data-m3-menu="site-gallery-menu" aria-haspopup="menu" aria-expanded="false"
                            @if (request()->routeIs('photo', 'photo.gallery', 'video')) data-current="true" @endif>
                            {{ __('gallery') }}
                            <x-icon name="chevron-down" class="m3-icon" />
                        </button>
                        <div class="m3-menu" id="site-gallery-menu" popover role="menu">
                            @if ($photo)
                                <a class="m3-menu__item" role="menuitem" href="{{ route('photo') }}"
                                    @if (request()->routeIs('photo', 'photo.gallery')) aria-current="true" @endif>{{ __('photos') }}</a>
                            @endif
                            @if ($photo && $video)
                                <hr class="m3-menu__divider">
                            @endif
                            @if ($video)
                                <a class="m3-menu__item" role="menuitem" href="{{ route('video') }}"
                                    @if (request()->routeIs('video')) aria-current="true" @endif>{{ __('videos') }}</a>
                            @endif
                        </div>
                    </li>
                @endif
                @if ($question)
                    <li>
                        <a class="m3-button m3-button--text site-nav__link" href="{{ route('contact.us') }}"
                            @if (request()->routeIs('contact.us')) aria-current="page" @endif>{{ __('contact_us') }}</a>
                    </li>
                @endif
                @if ($registration)
                    <li>
                        <a class="m3-button m3-button--text site-nav__link" href="{{ route('student-registration.index') }}"
                            @if (request()->routeIs('student-registration.index')) aria-current="page" @endif>{{ __('registration') }}</a>
                    </li>
                @endif
                <li>
                    <button type="button" class="m3-button m3-button--text site-nav__link m3-button--icon-leading m3-button--icon-trailing"
                        data-m3-menu="site-language-menu" aria-haspopup="menu" aria-expanded="false">
                        <x-icon name="globe" class="m3-icon" />
                        <span class="site-nav__language">{{ $currentLanguage }}</span>
                        <x-icon name="chevron-down" class="m3-icon" />
                    </button>
                    <div class="m3-menu" id="site-language-menu" popover role="menu">
                        @foreach ($languages as $language)
                            <a class="m3-menu__item" role="menuitem" href="{{ route('web.set.language', $language->code) }}"
                                @if (app()->getLocale() == $language->code) aria-current="true" @endif>{{ $language->name }}</a>
                        @endforeach
                    </div>
                </li>
                <li>
                    <a class="m3-button m3-button--filled site-nav__login" href="{{ url('login') }}">{{ __('login') }}</a>
                </li>
            </ul>
        </nav>

        <button type="button" class="m3-icon-button site-app-bar__menu" data-m3-dialog-open="site-drawer"
            aria-haspopup="dialog" aria-label="{{ __('menu') }}">
            <x-icon name="menu" class="m3-icon" />
        </button>
    </div>
</header>

{{-- The phone and tablet menu: a modal navigation drawer. --}}
<dialog class="m3-navigation-drawer site-drawer" id="site-drawer" aria-label="{{ __('menu') }}">
    <div class="site-drawer__header">
        <img src="{{ schoolLogoUrl('logo1') }}" height="50" width="150" alt="logo">
        <button type="button" class="m3-icon-button" data-m3-dialog-close aria-label="{{ __('close') }}">
            <x-icon name="close" class="m3-icon" />
        </button>
    </div>
    <nav aria-label="{{ __('menu') }}">
        <a class="m3-navigation-drawer__item" href="{{ url('/') }}"
            @if (request()->is('/')) aria-current="page" @endif>{{ __('home') }}</a>
        @if ($about || $whoweare || $teacher)
            <a class="m3-navigation-drawer__item" href="{{ route('about.us') }}"
                @if (request()->routeIs('about.us')) aria-current="page" @endif>{{ __('about-us') }}</a>
        @endif
        @if ($photo || $video)
            <details class="site-drawer__group" @if (request()->routeIs('photo', 'photo.gallery', 'video')) open @endif>
                <summary class="m3-navigation-drawer__item">{{ __('gallery') }}</summary>
                @if ($photo)
                    <a class="m3-navigation-drawer__item site-drawer__subitem" href="{{ route('photo') }}"
                        @if (request()->routeIs('photo', 'photo.gallery')) aria-current="page" @endif>{{ __('photos') }}</a>
                @endif
                @if ($video)
                    <a class="m3-navigation-drawer__item site-drawer__subitem" href="{{ route('video') }}"
                        @if (request()->routeIs('video')) aria-current="page" @endif>{{ __('videos') }}</a>
                @endif
            </details>
        @endif
        @if ($registration)
            <a class="m3-navigation-drawer__item" href="{{ route('student-registration.index') }}"
                @if (request()->routeIs('student-registration.index')) aria-current="page" @endif>{{ __('registration') }}</a>
        @endif
        @if ($question)
            <a class="m3-navigation-drawer__item" href="{{ route('contact.us') }}"
                @if (request()->routeIs('contact.us')) aria-current="page" @endif>{{ __('contact_us') }}</a>
        @endif
        <hr class="m3-navigation-drawer__divider">
        <details class="site-drawer__group">
            <summary class="m3-navigation-drawer__item">
                <x-icon name="globe" class="m3-icon" />{{ $currentLanguage }}
            </summary>
            @foreach ($languages as $language)
                <a class="m3-navigation-drawer__item site-drawer__subitem" href="{{ route('web.set.language', $language->code) }}"
                    @if (app()->getLocale() == $language->code) aria-current="true" @endif>{{ $language->name }}</a>
            @endforeach
        </details>
    </nav>
    <div class="site-drawer__actions">
        <a class="m3-button m3-button--filled m3-button--block" href="{{ url('login') }}">{{ __('login') }}</a>
    </div>
</dialog>
