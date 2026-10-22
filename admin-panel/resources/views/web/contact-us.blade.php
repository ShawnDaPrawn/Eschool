@extends('web.master')

@section('title')
    {{ __('contact_us') }}
@endsection
@section('content')
    @include('web.partials.banner', [
        'title' => __('contact_us'),
        'crumbs' => [[__('home'), url('/')], [__('contact_us'), null]],
    ])

    @if ($question)
        <section class="m3-section site-contact">
            <div class="m3-page m3-stack m3-gap-12">
                @include('web.partials.section-head', [
                    'tag' => isset($question->tag) ? $question->tag : 'Got a Question?',
                    'heading' => isset($question->heading) ? $question->heading : 'Admissions, Academics, Support:Find Your Answer Here!',
                ])

                <ul class="m3-grid site-contact__cards">
                    <li class="m3-col-12 m3-col-medium-6 m3-col-expanded-4">
                        <div class="m3-card site-contact-card">
                            <img src="{{ url('assets/images/contactIcon.png') }}" alt="" width="70" height="70">
                            <h3 class="site-contact-card__title">{{ __('phone_number') }}</h3>
                            <p class="site-contact-card__value">
                                <a href="tel:{{ $settings['school_phone'] }}">{{ __('phone') }}: <bdi dir="ltr">{{ isset($settings['school_phone']) ? $settings['school_phone'] : '+27 12 345 6789' }}</bdi></a>
                            </p>
                        </div>
                    </li>
                    <li class="m3-col-12 m3-col-medium-6 m3-col-expanded-4">
                        <div class="m3-card site-contact-card">
                            <img src="{{ url('assets/images/mailIcon.png') }}" alt="" width="70" height="70">
                            <h3 class="site-contact-card__title">{{ __('email') }}</h3>
                            <p class="site-contact-card__value">
                                <a href="mailto:{{ $settings['school_email'] }}">{{ __('mail_us') }}: {{ isset($settings['school_email']) ? $settings['school_email'] : 'info@tokensoft.tech' }}</a>
                            </p>
                        </div>
                    </li>
                    <li class="m3-col-12 m3-col-medium-6 m3-col-expanded-4">
                        <div class="m3-card site-contact-card">
                            <img src="{{ url('assets/images/locationIcon.png') }}" alt="" width="70" height="70">
                            <h3 class="site-contact-card__title">{{ __('address') }}</h3>
                            <p class="site-contact-card__value">{{ __('address') }}: {{ isset($settings['school_address']) ? $settings['school_address'] : 'Pretoria, South Africa' }}</p>
                        </div>
                    </li>
                </ul>

                <div class="m3-card m3-card--outlined site-form-card site-contact__form">
                    <div class="m3-card__content site-form-card__body">
                        <div class="site-form-card__intro">
                            <h3 class="site-form-card__title">{{ __('get_in_touch') }}</h3>
                            <p class="m3-card__supporting">{{ __('contact_form_subtitle') }}</p>
                        </div>
                        {{-- script.js sends it with AJAX (#create-form) after jQuery Validate passes. --}}
                        <form id="create-form" name="create-form" method="POST" action="{{ route('contact_us.store') }}" class="m3-grid site-form-grid">
                            <x-m3.text-field name="first_name" id="first_name" :label="__('first_name')" required class="m3-col-12 m3-col-medium-6" autocomplete="given-name" />
                            <x-m3.text-field name="last_name" id="last_name" :label="__('last_name')" required class="m3-col-12 m3-col-medium-6" autocomplete="family-name" />
                            <x-m3.text-field name="email" id="email" type="email" :label="__('email')" required class="m3-col-12 m3-col-medium-6" autocomplete="email" />
                            <x-m3.text-field name="phone" id="phone" type="tel" :label="__('phone')" required class="m3-col-12 m3-col-medium-6" autocomplete="tel" />
                            <x-m3.text-field name="message" id="message" type="textarea" :label="__('message')" rows="5" required class="m3-col-12" />
                            @php
                                $site_key = DB::table('settings')->where('type', 'recaptcha_site_key')->pluck('message')->first();
                                $status = DB::table('settings')->where('type', 'recaptcha_status')->pluck('message')->first();
                            @endphp
                            @if ($status == 1)
                                <div class="m3-col-12">
                                    <div class="g-recaptcha" data-sitekey="{{ $site_key }}"></div>
                                </div>
                            @endif
                            <div class="m3-col-12">
                                <button type="submit" class="m3-button m3-button--filled m3-button--icon-trailing" name="contactbtn">
                                    {{ __('submit') }}
                                    <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if (! empty($settings['maplink']))
                    <div class="site-contact__map">
                        <iframe src="{{ $settings['maplink'] }}" title="{{ __('address') }}" width="600" height="450"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                @endif
            </div>
        </section>
    @endif
@endsection
