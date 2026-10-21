<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Material 3 design tokens, generated from the apps' theme (packages/eschool_core). Load first. --}}
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}?v={{ filemtime(public_path('assets/css/m3-tokens.css')) }}">
    <!-- bootstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('/assets/css/vendor.bundle.base.css') }}" async>
    <link rel="stylesheet" href="{{ asset('/assets/css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/flatpickr-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.css') }}">
    {{-- Typeface (Poppins) and legacy colour variables mapped to the tokens — after the vendor CSS, before the site styles. --}}
    <link rel="stylesheet" href="{{ asset('/assets/css/m3-base.css') }}?v={{ filemtime(public_path('assets/css/m3-base.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/webstyle.css') }}?v={{ filemtime(public_path('assets/css/webstyle.css')) }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/responsive.css') }}">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('/assets/css/ekko-lightbox.css') }}">
</head>

<body>

    <header class="topHeader">
        <div class="container">
            <div class="row divWrapper">
                <div class="col-8 col-sm-8 col-md-8 col-lg-6">
                    <div class="leftDiv">
                        <span class="commonSpan">
                            <i class="fa-solid fa-envelope"></i>
                            <a
                                href="mailto:{{ $settings['school_email'] }}">{{ isset($settings['school_email']) ? $settings['school_email'] : 'info@tokensoft.tech' }}</a>
                        </span>
                        <span class="commonSpan"><i class="fa-solid fa-phone-volume"></i>
                            <a
                                href="tel:{{ $settings['school_phone'] }}">{{ isset($settings['school_phone']) ? $settings['school_phone'] : '+27 12 345 6789' }}</a>
                        </span>
                    </div>
                </div>
                <div class="col-4 col-sm-4 col-md-4 col-lg-6">
                    <div class="rightDiv">
                        <span class="commonSpan">{{ __('follow_us') }}:</span>
                        {{-- <span class="commonSpan">Follow Us:</span> --}}
                        <span>
                            <span class="commonSpan">
                                <a href="{{ isset($settings['facebook']) ? $settings['facebook'] : '' }}"
                                    target="_blank">
                                    <i class="fa-brands fa-facebook-square"></i>
                                </a>
                            </span>
                            <span class="commonSpan">
                                <a href="{{ isset($settings['linkedin']) ? $settings['linkedin'] : '' }}"
                                    target="_blank">
                                    <i class="fa-brands fa-linkedin"></i>
                                </a>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- topHeader ends here  -->

    <header class="navbar">
        <div class="container">
            <div class="navbarWrapper">
                <div class="navLogoWrapper">
                    <div class="navLogo">
                        <a href="{{ url('/') }}">
                            <img src="{{ schoolLogoUrl('logo1') }}"
                                height="50px" width="150px" alt="logo">
                        </a>
                    </div>
                </div>
                <div class="menuListWrapper">
                    <ul class="listItems">
                        <li>
                            <a href="{{ url('/') }}">{{ __('home') }}</a>
                        </li>
                        @if ($about || $whoweare || $teacher)
                            <li>
                                <a href="{{ route('about.us') }}">{{ __('about-us') }}</a>
                            </li>
                        @endif

                        @if ($photo || $video)
                            <li>
                                <div class="dropdown">
                                    <a class="btn btn-secondary dropdown-toggle" href="#" role="button"
                                        id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ __('gallery') }}
                                    </a>

                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                        @if ($photo)
                                            <li><a class="dropdown-item" href="{{ route('photo') }}">
                                                    {{ __('photos') }}</a></li>
                                        @endif
                                        <hr>
                                        @if ($video)
                                            <li><a class="dropdown-item" href="{{ route('video') }}">
                                                    {{ __('videos') }}</a></li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif
                        @if ($question)
                            <li>
                                <a href="{{ route('contact.us') }}"> {{ __('contact_us') }}</a>
                            </li>
                        @endif
                        @if ($registration)
                            <li>
                                <a href="{{ route('student-registration.index') }}"> {{ __('registration') }}</a>
                            </li>
                        @endif
                        <li class="nav-language-switcher">
                            <div class="dropdown">
                                <a class="language-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-globe"></i>
                                    <span class="current-lang-name">{{ Session::has('language') ? Session::get('language')->name : __('language') }}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu language-dropdown-menu" aria-labelledby="langDropdown">
                                    @foreach (get_language() as $language)
                                        <li>
                                            <a class="dropdown-item {{ app()->getLocale() == $language->code ? 'active' : '' }}"
                                                href="{{ route('web.set.language', $language->code) }}">
                                                {{ $language->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                        <li>
                            <button type="submit" class="commonBtn mb-3" name="contactbtn"
                                onclick="window.location='{{ url('login') }}'">
                                {{ __('login') }}
                            </button>
                        </li>
                    </ul>
                    <div class="hamburg">
                        <span data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"
                            aria-controls="offcanvasRight"><i class="fa-solid fa-bars"></i></span>
                    </div>
                </div>
            </div>



            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight"
                aria-labelledby="offcanvasRightLabel">
                <div class="offcanvas-header">
                    <div class="navLogoWrapper">
                        <div class="navLogo">
                            <img src="{{ schoolLogoUrl('logo1') }}"
                                height="50px" width="150px" alt="logo">
                        </div>
                    </div>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="listItems">
                        <li>
                            <a href="{{ url('/') }}">{{ __('home') }}</a>
                        </li>
                        @if ($about || $whoweare || $teacher)
                            <li>
                                <a href="{{ route('about.us') }}">{{ __('about-us') }}</a>
                            </li>
                        @endif
                        @if ($photo || $video)
                            <li>
                                <div class="dropdown">
                                    <a class="btn btn-secondary dropdown-toggle" href="#" role="button"
                                        id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                        Gallery
                                    </a>

                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                        @if ($photo)
                                            <li><a class="dropdown-item"
                                                    href="{{ route('photo') }}">{{ __('photos') }}</a></li>
                                        @endif
                                        <hr>
                                        @if ($video)
                                            <li><a class="dropdown-item"
                                                    href="{{ route('video') }}">{{ __('videos') }}</a></li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif
                        @if ($registration)
                            <li>
                                <a href="{{ route('student-registration.index') }}"> {{ __('registration') }}</a>
                            </li>
                        @endif
                        @if ($question)
                            <li>
                                <a href="{{ route('contact.us') }}">{{ __('contact_us') }}</a>
                            </li>
                        @endif
                        <li class="nav-language-switcher">
                            <div class="dropdown">
                                <a class="language-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-globe"></i>
                                    <span class="current-lang-name">{{ Session::has('language') ? Session::get('language')->name : __('language') }}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </a>
                                <ul class="dropdown-menu language-dropdown-menu">
                                    @foreach (get_language() as $language)
                                        <li>
                                            <a class="dropdown-item {{ app()->getLocale() == $language->code ? 'active' : '' }}"
                                                href="{{ route('web.set.language', $language->code) }}">
                                                {{ $language->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                        <li>
                            <button type="submit" class="commonBtn mb-3" name="contactbtn"
                                onclick="window.location='{{ url('login') }}'">
                                {{ __('login') }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
</body>

</html>
