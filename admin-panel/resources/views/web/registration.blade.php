@extends('web.master')

@section('title')
    {{ __('registration') }}
@endsection
@section('content')
    @if ($registration)
        @include('web.partials.banner', [
            'title' => __('registration'),
            'crumbs' => [[__('home'), url('/')], [__('registration'), null]],
        ])

        @php
            $half = 'm3-col-12 m3-col-medium-6';
            $third = 'm3-col-12 m3-col-medium-6 m3-col-expanded-4';
            $fieldLabel = fn ($name) => ucwords(str_replace('_', ' ', $name));
        @endphp

        <section class="m3-section site-registration">
            <div class="m3-page m3-stack m3-gap-8">
                @include('web.partials.section-head', [
                    'tag' => __('student_registration_form'),
                    'content' => __('registration_form_subtitle'),
                ])

                <div class="m3-card m3-card--outlined site-form-card">
                    <h2 class="site-form-card__heading">{{ __('student_registration_form') }}</h2>
                    <hr class="m3-divider">
                    <div class="m3-card__content site-form-card__body">
                        {{-- Sent with AJAX by the script below after jQuery Validate passes. --}}
                        <form class="student-registration-form1 m3-grid site-form-grid" id="student-registration-form1"
                            enctype="multipart/form-data" action="{{ route('student-registration-store') }}"
                            method="POST" novalidate="novalidate">
                            @csrf

                            <x-m3.text-field name="first_name" :label="__('first_name')" required :class="$third" />
                            <x-m3.text-field name="last_name" :label="__('last_name')" required :class="$third" />
                            <x-m3.text-field name="mobile" type="tel" :label="__('mobile')" input-class="mobile" :class="$third" inputmode="numeric" maxlength="16" pattern="[0-9]{7,16}" />

                            <div class="{{ $third }}">
                                <fieldset class="m3-fieldset">
                                    <legend class="m3-fieldset__legend">{{ __('gender') }} <span class="m3-text-field__required" aria-hidden="true">*</span></legend>
                                    <div class="m3-fieldset__options">
                                        <label class="m3-radio">
                                            <input class="m3-radio__input" type="radio" name="gender" value="male" required>
                                            <span class="m3-radio__control"></span>{{ __('male') }}
                                        </label>
                                        <label class="m3-radio">
                                            <input class="m3-radio__input" type="radio" name="gender" value="female" required>
                                            <span class="m3-radio__control"></span>{{ __('female') }}
                                        </label>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="{{ $third }}">
                                <x-m3.file-field name="image" :label="__('image')" required accept="image/*" :hint="__('image_size_hint')" />
                            </div>
                            <x-m3.text-field name="dob" :label="__('dob')" required input-class="datepicker-popup-no-future" :class="$third" />

                            <div class="{{ $half }}">
                                <label class="m3-text-field m3-text-field--select">
                                    <select class="m3-text-field__input" name="class_id" id="class_id" required>
                                        <option value="">{{ __('select') . ' ' . __('class') }}</option>
                                        @foreach ($classSchools as $classSchool)
                                            <option value="{{ $classSchool->id }}">{{ $classSchool->name }} -
                                                {{ $classSchool->medium->name }}
                                                {{ $classSchool->streams->name ?? ' ' }}</option>
                                        @endforeach
                                    </select>
                                    <span class="m3-text-field__label">{{ __('class') }} <span class="m3-text-field__required" aria-hidden="true">*</span></span>
                                </label>
                            </div>
                            <div class="{{ $half }}">
                                <label class="m3-text-field m3-text-field--select">
                                    <select class="m3-text-field__input" name="category_id" required>
                                        <option value="">{{ __('select') . ' ' . __('category') }}</option>
                                        @foreach ($category as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="m3-text-field__label">{{ __('category') }} <span class="m3-text-field__required" aria-hidden="true">*</span></span>
                                </label>
                            </div>

                            <x-m3.text-field name="admission_no" :label="__('gr_number')" :value="$admission_no" required readonly :class="$half" />
                            <x-m3.text-field name="admission_date" :label="__('admission_date')" :value="$admission_date" required readonly input-class="datepicker-popup-no-future" :class="$half" />

                            <x-m3.text-field name="current_address" id="current_address" type="textarea" :label="__('current_address')" rows="2" required :class="$half" />
                            <x-m3.text-field name="permanent_address" id="permanent_address" type="textarea" :label="__('permanent_address')" rows="2" required :class="$half" />

                            {{-- The school's own fields (Student Registration Fields in the admin panel). --}}
                            @foreach ($studentFields as $row)
                                @php
                                    $label = $fieldLabel($row->name);
                                    $required = (bool) $row->is_required;
                                @endphp
                                @if ($row->type === 'text' || $row->type === 'number')
                                    <x-m3.text-field :name="$row->name" :type="$row->type" :label="$label" :required="$required" :class="$half" />
                                @endif
                                @if ($row->type === 'dropdown')
                                    <div class="{{ $half }}">
                                        <label class="m3-text-field m3-text-field--select">
                                            <select class="m3-text-field__input" name="{{ $row->name }}" @if ($required) required @endif>
                                                <option value="">{{ __('please_select') }}</option>
                                                @foreach (json_decode($row->default_values) as $options)
                                                    @if ($options != null)
                                                        <option value="{{ $options }}">{{ ucfirst(str_replace('_', ' ', $options)) }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <span class="m3-text-field__label">{{ $label }}
                                                @if ($row->is_required)
                                                    <span class="m3-text-field__required" aria-hidden="true">*</span>
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                @endif
                                @if ($row->type === 'radio')
                                    <div class="{{ $half }}">
                                        <fieldset class="m3-fieldset">
                                            <legend class="m3-fieldset__legend">{{ $label }}
                                                @if ($row->is_required)
                                                    <span class="m3-text-field__required" aria-hidden="true">*</span>
                                                @endif
                                            </legend>
                                            <div class="m3-fieldset__options">
                                                @foreach (json_decode($row->default_values) as $options)
                                                    @if ($options != null)
                                                        <label class="m3-radio">
                                                            <input class="m3-radio__input" type="radio" name="{{ $row->name }}" value="{{ $options }}" @if ($required) required @endif>
                                                            <span class="m3-radio__control"></span>{{ ucfirst($options) }}
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    </div>
                                @endif
                                @if ($row->type === 'checkbox')
                                    <div class="{{ $half }}">
                                        <fieldset class="m3-fieldset">
                                            <legend class="m3-fieldset__legend">{{ $label }}
                                                @if ($row->is_required)
                                                    <span class="m3-text-field__required" aria-hidden="true">*</span>
                                                @endif
                                            </legend>
                                            <div class="m3-fieldset__options" id="{{ $row->name }}">
                                                @foreach (json_decode($row->default_values) as $options)
                                                    @if ($options != null)
                                                        <label class="m3-checkbox">
                                                            <input class="m3-checkbox__input" type="checkbox" name="{{ 'checkbox[' . $row->name . '][]' }}" value="{{ $options }}" @if ($required) required @endif>
                                                            <span class="m3-checkbox__control"></span>{{ ucfirst(str_replace('_', ' ', $options)) }}
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    </div>
                                @endif
                                @if ($row->type === 'textarea')
                                    <x-m3.text-field :name="$row->name" type="textarea" :label="$label" rows="3" :required="$required" :class="$half" />
                                @endif
                                @if ($row->type === 'file')
                                    <div class="{{ $half }}">
                                        <x-m3.file-field :name="$row->name" :label="$label" :required="$required" />
                                    </div>
                                @endif
                            @endforeach

                            {{-- Parents or a guardian: script.js shows the chosen block and disables the other. --}}
                            <div class="m3-col-12 m3-fieldset__options site-registration__choice">
                                <label class="m3-radio">
                                    <input class="m3-radio__input parent-check" type="radio" name="parent_guardian_type" value="Parent"
                                        id="show-parents-details" checked>
                                    <span class="m3-radio__control"></span>{{ __('parents_details') }}
                                </label>
                                <label class="m3-radio">
                                    <input class="m3-radio__input parent-check" type="radio" name="parent_guardian_type" value="Guardian"
                                        id="show-guardian-details">
                                    <span class="m3-radio__control"></span>{{ __('guardian_details') }}
                                </label>
                            </div>

                            <div class="m3-col-12" id="guardian_div" style="display:none;">
                                <div class="m3-grid site-form-grid">
                                    <x-m3.text-field name="guardian_email" id="email" type="email" :label="__('guardian') . ' ' . __('email')" required :class="$half" />
                                    <x-m3.text-field name="guardian_mobile" id="guardian_mobile" type="number" :label="__('guardian') . ' ' . __('mobile')" required :class="$half" min="0" />
                                    <x-m3.text-field name="guardian_first_name" id="guardian_first_name" :label="__('guardian') . ' ' . __('first_name')" required :class="$half" />
                                    <x-m3.text-field name="guardian_last_name" id="guardian_last_name" :label="__('guardian') . ' ' . __('last_name')" required :class="$half" />
                                    <x-m3.text-field name="guardian_dob" id="guardian_dob" :label="__('guardian') . ' ' . __('dob')" required input-class="datepicker-popup-no-future" :class="$half" />
                                    <div class="{{ $half }}">
                                        <fieldset class="m3-fieldset">
                                            <legend class="m3-fieldset__legend">{{ __('gender') }} <span class="m3-text-field__required" aria-hidden="true">*</span></legend>
                                            <div class="m3-fieldset__options">
                                                <label class="m3-radio">
                                                    <input class="m3-radio__input" type="radio" name="guardian_gender" value="male" id="guardian_male" required>
                                                    <span class="m3-radio__control"></span>{{ __('male') }}
                                                </label>
                                                <label class="m3-radio">
                                                    <input class="m3-radio__input" type="radio" name="guardian_gender" value="female" id="guardian_female" required>
                                                    <span class="m3-radio__control"></span>{{ __('female') }}
                                                </label>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <x-m3.text-field name="guardian_occupation" id="guardian_occupation" :label="__('guardian') . ' ' . __('occupation')" required :class="$half" />
                                    <div class="{{ $half }}">
                                        <x-m3.file-field name="guardian_image" :label="__('guardian') . ' ' . __('image')" required accept="image/*" :hint="__('image_size_hint')" input-class="guardian_image" info-id="guardian_image" />
                                    </div>
                                </div>
                            </div>

                            <div class="m3-col-12" id="parents_div" style="display:none;">
                                <div class="m3-grid site-form-grid">
                                    @foreach (['father', 'mother'] as $parent)
                                        <x-m3.text-field :name="$parent . '_email'" :id="$parent . '_email'" type="email" :label="__($parent . '_email')" required :class="$half" />
                                        <x-m3.text-field :name="$parent . '_mobile'" :id="$parent . '_mobile'" type="number" :label="__($parent) . ' ' . __('mobile')" required :class="$half" min="0" />
                                        <x-m3.text-field :name="$parent . '_first_name'" :id="$parent . '_first_name'" :label="__($parent) . ' ' . __('first_name')" required :class="$half" />
                                        <x-m3.text-field :name="$parent . '_last_name'" :id="$parent . '_last_name'" :label="__($parent) . ' ' . __('last_name')" required :class="$half" />
                                        <x-m3.text-field :name="$parent . '_dob'" :id="$parent . '_dob'" :label="__($parent) . ' ' . __('dob')" required input-class="datepicker-popup-no-future" :class="$half" />
                                        <x-m3.text-field :name="$parent . '_occupation'" :id="$parent . '_occupation'" :label="__($parent) . ' ' . __('occupation')" required :class="$half" />
                                        <div class="{{ $half }}">
                                            <x-m3.file-field :name="$parent . '_image'" :label="__($parent) . ' ' . __('image')" required accept="image/*" :hint="__('image_size_hint')" :input-class="$parent . '_image'" :info-id="$parent . '_image'" />
                                        </div>
                                        @if (! $loop->last)
                                            <hr class="m3-divider m3-col-12 site-registration__divider">
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            @php
                                $site_key = DB::table('settings')->where('type', 'recaptcha_site_key')->pluck('message')->first();
                                $status = DB::table('settings')->where('type', 'recaptcha_status')->pluck('message')->first();
                            @endphp
                            @if ($status == 1)
                                <div class="m3-col-12">
                                    <input type="hidden" name="recaptcha_status" value="1">
                                    <div class="g-recaptcha" name="recaptcha" data-sitekey="{{ $site_key }}"></div>
                                </div>
                            @else
                                <input type="hidden" name="recaptcha_status" value="0">
                            @endif
                            <div class="m3-col-12">
                                <input type="submit" class="m3-button m3-button--filled" value="{{ __('submit') }}">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
@section('script')
    <script>
        $('#student-registration-form1').on('submit', function(e) {

            e.preventDefault();
            let formElement = $(this);
            let submitButtonElement = $(this).find(':submit');
            let url = $(this).attr('action');
            let data = new FormData(this);

            function successCallback() {
                setTimeout(function() {
                    window.location.reload();
                }, 4000);
            }

            // Google invalidates a g-recaptcha token after the first verify attempt
            // (pass or fail). The server only verifies it once every other field has
            // already passed validation, so a "recaptcha_error" response means the
            // token is now dead (reCAPTCHA failed, or an email that already has an
            // account was found after it) and the widget must be reset before
            // retrying. Other validation errors don't touch reCAPTCHA, so the
            // checkbox stays checked for those.
            function errorCallback(response) {
                if (response && response.recaptcha_error && typeof grecaptcha !== 'undefined' && $('.g-recaptcha').length) {
                    grecaptcha.reset();
                }
            }

            formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
        });


        // Mobile: digits only (leading zeros allowed), total 7 to 16 digits. No "+", negatives or decimals.
        $.validator.addMethod('mobileFormat', function(value, element) {
            return this.optional(element) || /^[0-9]{7,16}$/.test(value);
        }, "{{ __('Please enter a valid mobile number (7 to 16 digits).') }}");

        // Strip anything that is not a digit while typing.
        $(".student-registration-form1").on('input', 'input[name="mobile"]', function() {
            let cleaned = this.value.replace(/\D/g, '');
            if (this.value !== cleaned) {
                this.value = cleaned;
            }
        });

        $(".student-registration-form1").validate({
            rules: {
                'first_name': "required",
                'last_name': "required",
                'mobile': {
                    mobileFormat: true,
                },
                'image': {
                    required: true,
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },
                'dob': "required",
                'class_section_id': "required",
                'category_id': "required",
                'admission_no': "required",
                'roll_number': "required",
                // 'caste': "required",
                // 'religion': "required",
                'admission_date': "required",
                'blood_group': "required",
                // 'height': "required",
                // 'weight': "required",
                'current_address': "required",
                'permanent_address': "required",
                'father_first_name': "required",
                'father_last_name': "required",
                'father_email': {
                    "email": true,
                    "required": true,
                },
                'father_mobile': {
                    "number": true,
                    "required": true,
                },
                'father_occupation': "required",
                'father_dob': "required",
                'father_image': {
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },

                'mother_email': {
                    "required": true,
                    "email": true,
                },
                'mother_first_name': "required",
                'mother_last_name': "required",
                'mother_mobile': {
                    "number": true,
                    "required": true,
                },
                'mother_occupation': "required",
                'mother_dob': "required",
                'mother_image': {
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },
                'guardian_email': {
                    "required": true,
                    "email": true,
                },
                'guardian_first_name': "required",
                'guardian_last_name': "required",
                'guardian_mobile': {
                    "number": true,
                    "required": true,
                },
                'guardian_occupation': "required",
                'guardian_dob': "required",
                'guardian_image': {
                    extension: "jpg|jpeg|png",
                    filesize: 2048
                },
            },
            messages: {
                'image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                },
                'father_image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                },
                'mother_image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                },
                'guardian_image': {
                    extension: "Please select a valid image file (JPEG, JPG, or PNG)",
                    filesize: "Image size should not be greater than 2048 KB"
                }
            },
            // Error text goes under the M3 field or group (script.js).
            errorPlacement: siteErrorPlacement,
            highlight: siteHighlight,
            unhighlight: siteUnhighlight
        });
    </script>
@endsection
