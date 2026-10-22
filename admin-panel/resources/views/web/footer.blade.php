{{-- Site footer. Uses $settings (controller) and the section flags set in web.master. --}}
<footer class="site-footer">
    <div class="m3-page">
        <div class="m3-grid site-footer__grid">
            <div class="m3-col-12 m3-col-medium-6 m3-col-expanded-3 m3-stack m3-gap-4">
                <a class="site-logo" href="{{ url('/') }}">
                    <img src="{{ schoolSetting('logo1') ? schoolLogoUrl('logo1') : asset('assets/navigateed/reversed.svg') }}" height="50" width="150" alt="NavigateEd">
                </a>
                <p class="site-footer__text">
                    {{ isset($settings['school_address']) ? $settings['school_address'] : ' Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                    incididunt ut labore et dolore magna.' }}
                </p>
                <div class="m3-row m3-gap-2">
                    <a class="m3-icon-button m3-icon-button--tonal site-footer__social" href="{{ isset($settings['facebook']) ? $settings['facebook'] : '' }}"
                        target="_blank" rel="noopener" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-square" aria-hidden="true"></i>
                    </a>
                    <a class="m3-icon-button m3-icon-button--tonal site-footer__social" href="{{ isset($settings['instagram']) ? $settings['instagram'] : '' }}"
                        target="_blank" rel="noopener" aria-label="Instagram">
                        <i class="fa-brands fa-instagram-square" aria-hidden="true"></i>
                    </a>
                    <a class="m3-icon-button m3-icon-button--tonal site-footer__social" href="{{ isset($settings['linkedin']) ? $settings['linkedin'] : '' }}"
                        target="_blank" rel="noopener" aria-label="LinkedIn">
                        <i class="fa-brands fa-linkedin" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <nav class="m3-col-12 m3-col-medium-6 m3-col-expanded-3" aria-labelledby="site-footer-useful">
                <h2 class="site-footer__title" id="site-footer-useful">{{ __('useful_links') }}</h2>
                <ul class="site-footer__links">
                    <li><a href="{{ url('/') }}">{{ __('home') }}</a></li>
                    @if ($about || $whoweare || $teacher)
                        <li><a href="{{ route('about.us') }}">{{ __('about-us') }}</a></li>
                    @endif
                    @if ($photo)
                        <li><a href="{{ route('photo') }}">{{ __('photos') }}</a></li>
                    @endif
                    @if ($video)
                        <li><a href="{{ route('video') }}">{{ __('videos') }}</a></li>
                    @endif
                    @if ($question)
                        <li><a href="{{ route('contact.us') }}">{{ __('contact_us') }}</a></li>
                    @endif
                </ul>
            </nav>

            <nav class="m3-col-12 m3-col-medium-6 m3-col-expanded-3" aria-labelledby="site-footer-quick">
                <h2 class="site-footer__title" id="site-footer-quick">{{ __('quick_links') }}</h2>
                <ul class="site-footer__links">
                    <li><a href="{{ url('login') }}">{{ __('admin_login') }}</a></li>
                    <li><a href="{{ url('terms-conditions') }}">{{ __('terms_condition') }}</a></li>
                    <li><a href="{{ url('privacy-policy') }}">{{ __('privacy_policy') }}</a></li>
                </ul>
            </nav>

            <div class="m3-col-12 m3-col-medium-6 m3-col-expanded-3">
                <h2 class="site-footer__title">{{ __('contact_info') }}</h2>
                <ul class="site-footer__links site-footer__contact">
                    <li>
                        <x-icon name="phone" class="m3-icon" />
                        <a href="tel:{{ $settings['school_phone'] }}" dir="ltr">{{ isset($settings['school_phone']) ? $settings['school_phone'] : '+27 12 345 6789' }}</a>
                    </li>
                    <li>
                        <x-icon name="mail" class="m3-icon" />
                        <a href="mailto:{{ $settings['school_email'] }}">{{ isset($settings['school_email']) ? $settings['school_email'] : 'info@tokensoft.tech' }}</a>
                    </li>
                    <li>
                        <x-icon name="map-pin" class="m3-icon" />
                        <span>{{ isset($settings['school_address']) ? $settings['school_address'] : 'Pretoria, South Africa' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="site-footer__copyright">
        <p class="m3-page">
            {{ __('copyright') }} © <?= date('Y') ?> <a href="{{ config('app.url') }}" target="_blank" rel="noopener">{{ config('app.name') }}</a>. {{ __('all_rights_reserved') }}. Tokensoft, Pretoria.
        </p>
    </div>
</footer>
