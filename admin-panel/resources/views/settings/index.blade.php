@extends('layouts.master')

@section('title')
    {{ __('general_settings') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('/assets/css/settings.css') }}?v={{ filemtime(public_path('assets/css/settings.css')) }}">
@endsection

@section('content')
    @php
        $onlinePayment = isset($settings['online_payment']) && !$settings['online_payment'] ? '0' : '1';
    @endphp
    <div class="content-wrapper settings-page">
        <div class="page-header">
            <div>
                <h3 class="page-title">{{ __('general_settings') }}</h3>
                <p class="page-subtitle">{{ __('school_information') }}, {{ __('branding_appearance') }} &amp; {{ __('social') }} {{ __('links') }}</p>
            </div>
        </div>

        <form id="frmData" class="setting-general" action="{{ url('settings') }}" novalidate="novalidate" enctype="multipart/form-data">
            @csrf

            {{-- ── School information ─────────────────────────────────── --}}
            <div class="card settings-card">
                <div class="card-body">
                    <div class="settings-section-head">
                        <span class="settings-section-head__icon"><x-icon name="settings" :size="20" /></span>
                        <div class="settings-section-head__text">
                            <h4 class="settings-section-head__title">{{ __('school_information') }}</h4>
                            <p class="settings-section-head__subtitle">{{ __('school_name') }}, {{ __('school_email') }} &amp; {{ __('school_address') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6 col-sm-12">
                            <label>{{ __('school_name') }} <span class="text-danger">*</span></label>
                            <input name="school_name" value="{{ $settings['school_name'] ?? '' }}" type="text" required placeholder="{{ __('school_name') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-6 col-sm-12">
                            <label>{{ __('school_email') }} <span class="text-danger">*</span></label>
                            <input name="school_email" value="{{ $settings['school_email'] ?? '' }}" type="email" required placeholder="{{ __('school_email') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-6 col-sm-12">
                            <label>{{ __('school_phone') }} <span class="text-danger">*</span></label>
                            <input name="school_phone" value="{{ $settings['school_phone'] ?? '' }}" type="text" required placeholder="{{ __('school_phone') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-6 col-sm-12">
                            <label>{{ __('school_tagline') }} <span class="text-danger">*</span></label>
                            <textarea name="school_tagline" required placeholder="{{ __('school_tagline') }}" rows="3" class="form-control">{{ $settings['school_tagline'] ?? '' }}</textarea>
                        </div>
                        <div class="form-group col-md-12 col-sm-12">
                            <label>{{ __('school_address') }} <span class="text-danger">*</span></label>
                            <textarea name="school_address" required placeholder="{{ __('school_address') }}" rows="3" class="form-control">{{ $settings['school_address'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Branding & appearance ──────────────────────────────── --}}
            <div class="card settings-card">
                <div class="card-body">
                    <div class="settings-section-head">
                        <span class="settings-section-head__icon"><x-icon name="sliders" :size="20" /></span>
                        <div class="settings-section-head__text">
                            <h4 class="settings-section-head__title">{{ __('branding_appearance') }}</h4>
                            <p class="settings-section-head__subtitle">{{ __('favicon') }}, {{ __('vertical_logo') }} &amp; {{ __('theme') }} {{ __('color') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-4 col-sm-12 settings-upload">
                            <label>{{ __('favicon') }} <span class="text-danger">*</span></label>
                            <input type="file" name="favicon" class="file-upload-default" accept="image/*" />
                            <div class="input-group">
                                <input type="text" class="form-control file-upload-info" disabled placeholder="{{ __('favicon') }}" />
                                <span class="input-group-append">
                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                </span>
                            </div>
                            @if (isset($settings['favicon']))
                                <div class="settings-media-preview">
                                    <img src="{{ url(Storage::url($settings['favicon'])) }}" alt="{{ __('favicon') }}">
                                </div>
                            @endif
                        </div>

                        <div class="form-group col-md-4 col-sm-12 settings-upload">
                            <label>{{ __('horizontal_logo') }} <span class="text-danger">*</span></label>
                            <input type="file" name="logo1" class="file-upload-default" accept="image/*" />
                            <div class="input-group">
                                <input type="text" class="form-control file-upload-info" disabled placeholder="{{ __('horizontal_logo') }}" />
                                <span class="input-group-append">
                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                </span>
                            </div>
                            @if (isset($settings['logo1']))
                                <div class="settings-media-preview">
                                    <img src="{{ url(Storage::url($settings['logo1'])) }}" alt="{{ __('horizontal_logo') }}">
                                </div>
                            @endif
                        </div>

                        <div class="form-group col-md-4 col-sm-12 settings-upload">
                            <label>{{ __('vertical_logo') }} <span class="text-danger">*</span></label>
                            <input type="file" name="logo2" class="file-upload-default" accept="image/*" />
                            <div class="input-group">
                                <input type="text" class="form-control file-upload-info" disabled placeholder="{{ __('vertical_logo') }}" />
                                <span class="input-group-append">
                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                </span>
                            </div>
                            @if (isset($settings['logo2']))
                                <div class="settings-media-preview">
                                    <img src="{{ url(Storage::url($settings['logo2'])) }}" alt="{{ __('vertical_logo') }}">
                                </div>
                            @endif
                        </div>

                        <div class="form-group col-md-4 col-sm-12 settings-upload">
                            <label>{{ __('login_image') }}</label>
                            <input type="file" name="login_image" class="file-upload-default" accept="image/*" />
                            <div class="input-group">
                                <input type="text" class="form-control file-upload-info" disabled placeholder="{{ __('login_image') }}" />
                                <span class="input-group-append">
                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                </span>
                            </div>
                            <div class="settings-media-preview">
                                <img src="{{ isset($settings['login_image']) ? url(Storage::url($settings['login_image'])) : asset('assets/eschool.jpg') }}" alt="{{ __('login_image') }}">
                            </div>
                        </div>

                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('theme') }} {{ __('color') }} <span class="text-danger">*</span></label>
                            <input name="theme_color" value="{{ $settings['theme_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker" />
                        </div>

                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('secondary') }} {{ __('color') }} <span class="text-danger">*</span></label>
                            <input name="secondary_color" value="{{ $settings['secondary_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Localization & preferences ─────────────────────────── --}}
            <div class="card settings-card">
                <div class="card-body">
                    <div class="settings-section-head">
                        <span class="settings-section-head__icon"><x-icon name="language" :size="20" /></span>
                        <div class="settings-section-head__text">
                            <h4 class="settings-section-head__title">{{ __('localization_preferences') }}</h4>
                            <p class="settings-section-head__subtitle">{{ __('time_zone') }}, {{ __('date_formate') }} &amp; {{ __('session_year') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('time_zone') }} <span class="text-danger">*</span></label>
                            <select name="time_zone" required class="form-control">
                                @foreach ($getTimezoneList as $timezone)
                                    <option value="@php echo $timezone[2]; @endphp" {{ isset($settings['time_zone']) ? ($settings['time_zone'] == $timezone[2] ? 'selected' : '') : '' }}>
                                        @php echo $timezone[2] . ' - GMT ' . $timezone[1] . ' - ' . $timezone[0] @endphp
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('date_formate') }} <span class="text-danger">*</span></label>
                            <select name="date_formate" required class="form-control">
                                @foreach ($getDateFormat as $key => $dateformate)
                                    <option value="{{ $key }}" {{ isset($settings['date_formate']) ? ($settings['date_formate'] == $key ? 'selected' : '') : '' }}>{{ $dateformate }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('time_formate') }} <span class="text-danger">*</span></label>
                            <select name="time_formate" required class="form-control">
                                @foreach ($getTimeFormat as $key => $timeformate)
                                    <option value="{{ $key }}" {{ isset($settings['time_formate']) ? ($settings['time_formate'] == $key ? 'selected' : '') : '' }}>{{ $timeformate }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('session_years') }} <span class="text-danger">*</span></label>
                            <select name="session_year" id="session_year" required class="form-control" data-original-value="{{ $settings['session_year'] ?? '' }}">
                                @foreach ($session_year as $key => $year)
                                    <option value="{{ $year->id }}" {{ isset($settings['session_year']) ? ($settings['session_year'] == $year->id ? 'selected' : '') : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('online_payment_mode') }} <span class="text-danger">*</span></label>
                            <div class="settings-toggle-group">
                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" name="online_payment" class="online_payment_toggle" value="1" {{ $onlinePayment == '1' ? 'checked' : '' }}>
                                        {{ __('enable') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" name="online_payment" class="online_payment_toggle" value="0" {{ $onlinePayment == '0' ? 'checked' : '' }}>
                                        {{ __('disable') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Social links ───────────────────────────────────────── --}}
            <div class="card settings-card">
                <div class="card-body">
                    <div class="settings-section-head">
                        <span class="settings-section-head__icon"><x-icon name="link" :size="20" /></span>
                        <div class="settings-section-head__text">
                            <h4 class="settings-section-head__title">{{ __('social') }} {{ __('links') }}</h4>
                            <p class="settings-section-head__subtitle">{{ __('facebook') }}, {{ __('instagram') }} &amp; {{ __('google_map_link') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('facebook') }}</label>
                            <input name="facebook" value="{{ $settings['facebook'] ?? '' }}" type="text" placeholder="{{ __('facebook') }} {{ __('url') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('instagram') }}</label>
                            <input name="instagram" value="{{ $settings['instagram'] ?? '' }}" type="text" placeholder="{{ __('instagram') }} {{ __('url') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('linkedin') }}</label>
                            <input name="linkedin" value="{{ $settings['linkedin'] ?? '' }}" type="text" placeholder="{{ __('linkedin') }} {{ __('url') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-12 col-sm-12">
                            <label>{{ __('google_map_link') }}</label>
                            <input name="maplink" value="{{ $settings['maplink'] ?? '' }}" type="text" placeholder="{{ __('google_map_link') }}" class="form-control" />
                            <div class="settings-note">
                                <x-icon name="info" :size="16" />
                                <span><strong>{{ __('Note') }} :- </strong>{{ __('get_the_link_from_google_map_with_embed_url_and_paste_only_src_from_it') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── reCAPTCHA ──────────────────────────────────────────── --}}
            <div class="card settings-card">
                <div class="card-body">
                    <div class="settings-section-head">
                        <span class="settings-section-head__icon"><x-icon name="alert" :size="20" /></span>
                        <div class="settings-section-head__text">
                            <h4 class="settings-section-head__title">{{ __('recaptcha') }}</h4>
                            <p class="settings-section-head__subtitle">{{ __('site_key') }} &amp; {{ __('secret_key') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('site_key') }}</label>
                            <input name="recaptcha_site_key" value="{{ $settings['recaptcha_site_key'] ?? '' }}" type="text" placeholder="{{ __('site_key') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('secret_key') }}</label>
                            <input name="recaptcha_secret_key" value="{{ $settings['recaptcha_secret_key'] ?? '' }}" type="text" placeholder="{{ __('secret_key') }}" class="form-control" />
                        </div>
                        <div class="form-group col-md-4 col-sm-12">
                            <label>{{ __('status') }}</label>
                            <div class="settings-toggle-group">
                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" name="recaptcha_status" class="online_payment_toggle" value="1" {{ isset($settings['recaptcha_status']) && $settings['recaptcha_status'] == 1 ? 'checked' : '' }}>
                                        {{ __('enable') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" name="recaptcha_status" class="online_payment_toggle" value="0" {{ !isset($settings['recaptcha_status']) || $settings['recaptcha_status'] == 0 ? 'checked' : '' }}>
                                        {{ __('disable') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn btn-theme">{{ __('submit') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script type='text/javascript'>
        if ($(".color-picker").length) {
            $('.color-picker').asColorPicker();
        }

        function submitSettingsForm() {
            let formElement = $('#frmData');
            let submitButtonElement = formElement.find(':submit');
            let url = formElement.attr('action');
            let data = new FormData(formElement[0]);

            function successCallback() {
                setTimeout(function () {
                    location.reload();
                }, 3000);
            }

            formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
        }

        $('#frmData').on('submit', function (e) {
            e.preventDefault();

            let sessionYearSelect = $('#session_year');
            let originalValue = sessionYearSelect.data('original-value').toString();
            let currentValue = sessionYearSelect.val();

            if (originalValue && currentValue !== originalValue) {
                Swal.fire({
                    title: trans('Are you sure?'),
                    html: `<p>${trans('changing_session_year_reset_semester')}</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    cancelButtonText: trans('Cancel'),
                    confirmButtonText: trans('yes_change_it')
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitSettingsForm();
                    }
                });
            } else {
                submitSettingsForm();
            }
        });

        $("#frmData").validate({
            rules: {
                username: "required",
                password: "required",
                recaptcha_site_key: {
                    required: function () {
                        return $('input[name="recaptcha_status"]:checked').val() === '1';
                    }
                },
                recaptcha_secret_key: {
                    required: function () {
                        return $('input[name="recaptcha_status"]:checked').val() === '1';
                    }
                },
            },
            errorPlacement: function (label, element) {
                label.addClass('mt-2 text-danger');
                label.insertAfter(element);
            },
            highlight: function (element, errorClass) {
                $(element).parent().addClass('has-danger')
                $(element).addClass('form-control-danger')
            }
        });
    </script>
@endsection
