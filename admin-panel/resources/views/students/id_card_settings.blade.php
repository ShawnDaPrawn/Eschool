@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@php
    // Field keys + localized labels — used BOTH for the checkbox grid and (mirrored) the JS preview.
    // Keys MUST stay exactly as the controller/PDF expect (implode(',') -> student_id_card_fields).
    $idCardFields = [
        'student_name' => __('student_name'),
        'gr_no' => __('gr_no'),
        'class_section' => __('class_section'),
        'roll_no' => __('roll_no'),
        'dob' => __('dob'),
        'blood_group' => __('blood_group'),
        'gender' => __('gender'),
        'session_year' => __('session_year'),
        'guardian_name' => __('father') . '/' . __('guardian') . ' ' . __('name'),
        'guardian_contact' => __('father') . '/' . __('guardian') . ' ' . __('contact'),
        'address' => __('address'),
    ];
    $selectedFields = $settings['student_id_card_fields'] ?? [];
    $selectedFields = is_array($selectedFields) ? $selectedFields : [];

    // Staff field keys + labels. "Staff" is the umbrella that includes teachers, so qualification
    // is offered (teachers have it in the DB; generic staff simply won't have data for it).
    // Keys MUST stay exactly as the (future) generator expects (implode(',') -> staff_id_card_fields).
    $staffIdCardFields = [
        'staff_name' => __('staff') . ' ' . __('name'),
        'designation' => __('designation'),
        'qualification' => __('qualification'),
        'email' => __('email'),
        'mobile' => __('mobile'),
        'dob' => __('dob'),
        'gender' => __('gender'),
        'address' => __('address'),
    ];
    $selectedStaffFields = $settings['staff_id_card_fields'] ?? [];
    $selectedStaffFields = is_array($selectedStaffFields) ? $selectedStaffFields : [];
    // Default: all staff fields selected when none have been saved yet.
    if (empty($selectedStaffFields)) {
        $selectedStaffFields = array_keys($staffIdCardFields);
    }


    // Standard CR80 (bank-card) dimensions, per orientation (aspect ~1.586). Used only when nothing is saved.
    // Landscape: 85.6 × 53.98 — Portrait: 53.98 × 85.6. The LONG side is 70–100 mm, the SHORT side 45–65 mm.
    $idcLayout = $settings['layout_type'] ?? 'vertical';
    $idcIsHorizontal = $idcLayout === 'horizontal';
    $idcDefaultWidth = $idcIsHorizontal ? 85.6 : 53.98;
    $idcDefaultHeight = $idcIsHorizontal ? 53.98 : 85.6;
    $idcWMin = $idcIsHorizontal ? 70 : 45;
    $idcWMax = $idcIsHorizontal ? 100 : 65;
    $idcHMin = $idcIsHorizontal ? 45 : 70;
    $idcHMax = $idcIsHorizontal ? 65 : 100;

    // Saved image / logo URLs passed to the JS preview so it renders existing uploads.
    $savedBackground = $settings['background_image'] ?? '' ? Storage::url($settings['background_image']) : '';
    $savedSignature = $settings['signature'] ?? '' ? Storage::url($settings['signature']) : '';
    $logo1Url = $settings['logo1'] ?? '' ? Storage::url($settings['logo1']) : '';
    $logo2Url = $settings['logo2'] ?? '' ? Storage::url($settings['logo2']) : '';
    $fallbackLogo = asset('assets/logo.svg');
    // Background watermark transparency (0 = opaque, 100 = fully transparent). Default 80 == opacity .2 (matches PDF).
    $bgTransparency = (int) ($settings['background_transparency'] ?? 80);
    // Background watermark size as a % of the card width (20–100). Default 80 == the preview's previous max-width cap.
    $bgSize = (int) ($settings['background_size'] ?? 80);
@endphp

@section('css')
    <link rel="stylesheet"
        href="{{ asset('assets/css/id_card_settings.css') }}?v={{ filemtime(public_path('assets/css/id_card_settings.css')) }}">
@endsection

@section('content')
    <div class="content-wrapper idc-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('student') . ' ' . __('id') . ' ' . __('card') . ' ' . __('settings') }}
            </h3>
        </div>

        <div class="row">
            {{-- ============ LEFT: CONFIG PANEL ============ --}}
            <div class="col-lg-5 col-xl-4 grid-margin idc-config-col">
                <form class="id-card-setting" id="id-card-setting" action="{{ route('id_card_settings.update') }}"
                    method="POST" novalidate="novalidate">
                    @csrf
                    <div class="idc-config">

                        {{-- Layout & Style --}}
                        <div class="idc-section">
                            <div class="idc-section-title">
                                <span class="idc-ico"><x-icon name="layout-grid" :size="16" /></span>
                                {{ __('layout') . ' & ' . __('style') }}
                            </div>

                            <label class="idc-field-label">{{ __('id_card_for') }} <span class="req">*</span></label>
                            <div class="idc-pills mb-3">
                                <label class="idc-pill is-active">
                                    <input type="radio" name="id_card_type" value="student" checked>
                                    <x-icon name="graduation-cap" :size="16" /> {{ __('student') }}
                                </label>
                                <label class="idc-pill">
                                    <input type="radio" name="id_card_type" value="staff">
                                    <x-icon name="users" :size="16" /> {{ __('staff') }}
                                </label>
                            </div>

                            <label class="idc-field-label">{{ __('layout_type') }} <span class="req">*</span></label>
                            <div class="idc-pills mb-3">
                                <label
                                    class="idc-pill {{ ($settings['layout_type'] ?? 'vertical') == 'vertical' ? 'is-active' : '' }}">
                                    <input type="radio" name="layout_type" value="vertical" required
                                        @checked(($settings['layout_type'] ?? 'vertical') == 'vertical')>
                                    <x-icon name="rectangle-vertical" :size="16" /> {{ __('vertical') }}
                                </label>
                                <label
                                    class="idc-pill {{ ($settings['layout_type'] ?? '') == 'horizontal' ? 'is-active' : '' }}">
                                    <input type="radio" name="layout_type" value="horizontal" required
                                        @checked(($settings['layout_type'] ?? '') == 'horizontal')>
                                    <x-icon name="rectangle-horizontal" :size="16" /> {{ __('horizontal') }}
                                </label>
                            </div>

                            <label class="idc-field-label">{{ __('profile_image_style') }} <span
                                    class="req">*</span></label>
                            <div class="idc-pills">
                                <label
                                    class="idc-pill {{ ($settings['profile_image_style'] ?? 'round') == 'round' ? 'is-active' : '' }}">
                                    <input type="radio" name="profile_image_style" value="round" required
                                        @checked(($settings['profile_image_style'] ?? 'round') == 'round')>
                                    <x-icon name="circle" :size="16" /> {{ __('round') }}
                                </label>
                                <label
                                    class="idc-pill {{ ($settings['profile_image_style'] ?? '') == 'squre' ? 'is-active' : '' }}">
                                    <input type="radio" name="profile_image_style" value="squre" required
                                        @checked(($settings['profile_image_style'] ?? '') == 'squre')>
                                    <x-icon name="square" :size="16" /> {{ __('squre') }}
                                </label>
                            </div>
                        </div>

                        {{-- Colours --}}
                        <div class="idc-section">
                            <div class="idc-section-title">
                                <span class="idc-ico"><x-icon name="paintbrush" :size="16" /></span>
                                {{ __('colors') }}
                            </div>
                            <div class="idc-color-row">
                                <label class="idc-field-label" for="header_color">{{ __('header_color') }} <span
                                        class="req">*</span></label>
                                <span class="idc-swatch-well">
                                    <input name="header_color" id="header_color"
                                        value="{{ $settings['header_color'] ?? '#07a5a5' }}" type="text" required
                                        placeholder="{{ __('color') }}" class="color-picker" />
                                </span>
                            </div>
                            <div class="idc-color-row">
                                <label class="idc-field-label" for="footer_color">{{ __('footer_color') }} <span
                                        class="req">*</span></label>
                                <span class="idc-swatch-well">
                                    <input name="footer_color" id="footer_color"
                                        value="{{ $settings['footer_color'] ?? '#56cc99' }}" type="text" required
                                        placeholder="{{ __('color') }}" class="color-picker" />
                                </span>
                            </div>
                            <div class="idc-color-row">
                                <label class="idc-field-label"
                                    for="header_footer_text_color">{{ __('header_footer_text_color') }} <span
                                        class="req">*</span></label>
                                <span class="idc-swatch-well">
                                    <input name="header_footer_text_color" id="header_footer_text_color"
                                        value="{{ $settings['header_footer_text_color'] ?? '#ffffff' }}" type="text"
                                        required placeholder="{{ __('color') }}" class="color-picker" />
                                </span>
                            </div>
                        </div>

                        {{-- Dimensions --}}
                        <div class="idc-section">
                            <div class="idc-section-title">
                                <span class="idc-ico"><x-icon name="ruler" :size="16" /></span>
                                {{ __('dimensions') }} ({{ __('mm') }})
                            </div>
                            <div class="row">
                                <div class="form-group col-6 mb-0">
                                    <label class="idc-field-label" for="card_width">{{ __('card_width') }} <span
                                            class="req">*</span></label>
                                    <input name="card_width" id="card_width"
                                        value="{{ $settings['card_width'] ?? $idcDefaultWidth }}" type="number"
                                        min="{{ $idcWMin }}" max="{{ $idcWMax }}" step="any" required
                                        placeholder="{{ __('card_width') }}" class="form-control" />
                                </div>
                                <div class="form-group col-6 mb-0">
                                    <label class="idc-field-label" for="card_height">{{ __('card_height') }} <span
                                            class="req">*</span></label>
                                    <input name="card_height" id="card_height"
                                        value="{{ $settings['card_height'] ?? $idcDefaultHeight }}" type="number"
                                        min="{{ $idcHMin }}" max="{{ $idcHMax }}" step="any" required
                                        placeholder="{{ __('card_height') }}" class="form-control" />
                                </div>
                            </div>
                            <span class="idc-dim-reco" id="idc-dim-reco"><x-icon name="info" :size="14" />
                                <span></span></span>
                        </div>

                        {{-- Background & Signature --}}
                        <div class="idc-section">
                            <div class="idc-section-title">
                                <span class="idc-ico"><x-icon name="image" :size="16" /></span>
                                {{ __('background_image') }} &amp; {{ __('signature') }}
                            </div>

                            {{-- Background Image --}}
                            <div class="form-group">
                                <label class="idc-field-label" for="bg-image-name">{{ __('background_image') }}</label>
                                <input type="file" name="background_image" id="background_image_input"
                                    accept="image/jpg,image/png,image/jpeg,image/svg" class="file-upload-default" />
                                <div class="input-group">
                                    <input type="text" id="bg-image-name" class="form-control file-upload-info"
                                        disabled placeholder="{{ __('image') }}" />
                                    <span class="input-group-append">
                                        <button class="file-upload-browse btn btn-theme"
                                            type="button">{{ __('upload') }}</button>
                                    </span>
                                </div>
                                {{-- Shown ONLY when a background image is present (saved or newly picked). --}}
                                <div id="idc-bg-controls" class="idc-bg-controls"
                                    @unless ($savedBackground) style="display:none" @endunless>
                                    <div class="idc-bg-row">
                                        <img id="idc-bg-thumb" class="idc-bg-thumb" src="{{ $savedBackground }}" alt="">
                                        <button type="button" id="idc-bg-remove" data-type="background_image"
                                            class="btn btn-inverse-danger btn-sm student-id-card-settings"
                                            title="{{ __('remove') }}">
                                            <x-icon name="close" :size="14" /> {{ __('remove') }}
                                        </button>
                                    </div>
                                    <label class="idc-field-label idc-range-label" for="background_transparency">
                                        {{ __('transparency') }}
                                        <span id="idc-bg-trans-val">{{ $bgTransparency }}%</span>
                                    </label>
                                    <input type="range" name="background_transparency" id="background_transparency"
                                        min="0" max="100" step="1" value="{{ $bgTransparency }}" class="idc-range">
                                    <label class="idc-field-label idc-range-label mt-3" for="background_size">
                                        {{ __('size') }}
                                        <span id="idc-bg-size-val">{{ $bgSize }}%</span>
                                    </label>
                                    <input type="range" name="background_size" id="background_size"
                                        min="20" max="100" step="1" value="{{ $bgSize }}" class="idc-range">
                                </div>
                            </div>

                            {{-- Signature --}}
                            <div class="form-group mb-0">
                                <label class="idc-field-label" for="sign-image-name">{{ __('signature') }}</label>
                                <input type="file" name="signature" id="signature_input"
                                    accept="image/jpg,image/png,image/jpeg,image/svg" class="file-upload-default" />
                                <div class="input-group">
                                    <input type="text" id="sign-image-name" class="form-control file-upload-info"
                                        disabled placeholder="{{ __('image') }}" />
                                    <span class="input-group-append">
                                        <button class="file-upload-browse btn btn-theme"
                                            type="button">{{ __('upload') }}</button>
                                    </span>
                                </div>
                                @if ($savedSignature)
                                    <div id="signature" class="mt-2">
                                        <img src="{{ $savedSignature }}" class="img-fluid w-25" alt="">
                                        <a href="" data-type="signature"
                                            class="btn btn-inverse-danger btn-sm student-id-card-settings">
                                            <x-icon name="close" :size="14" />
                                        </a>
                                    </div>
                                @endif
                                <small class="idc-help"><x-icon name="info" :size="14" /> {{ __('note') }}:
                                    {{ __('signature_used_in_other_documents_note') }}</small>
                            </div>
                        </div>

                        {{-- Select Fields --}}
                        <div class="idc-section">
                            <div class="idc-section-title">
                                <span class="idc-ico"><x-icon name="check-square" :size="16" /></span>
                                {{ __('select_fields') }} (<span
                                    id="idc-field-set-label">{{ __('student') }}</span>)
                                <span class="req">*</span>
                                <span class="idc-count" id="idc-field-count">0 / {{ count($idCardFields) }}</span>
                            </div>

                            {{-- Student field set --}}
                            <div class="idc-field-set" data-card-type="student">
                                <div class="idc-field-grid">
                                    @foreach ($idCardFields as $key => $label)
                                        <label class="idc-check {{ in_array($key, $selectedFields) ? 'is-checked' : '' }}">
                                            <input type="checkbox" name="student_id_card_fields[]"
                                                value="{{ $key }}" class="idc-field-cb"
                                                @checked(in_array($key, $selectedFields))>
                                            <span class="idc-box"><x-icon name="check" :size="12" :stroke="3" /></span>
                                            <span class="idc-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Staff field set --}}
                            <div class="idc-field-set d-none"
                                data-card-type="staff">
                                <div class="idc-field-grid">
                                    @foreach ($staffIdCardFields as $key => $label)
                                        <label
                                            class="idc-check {{ in_array($key, $selectedStaffFields) ? 'is-checked' : '' }}">
                                            <input type="checkbox" name="staff_id_card_fields[]"
                                                value="{{ $key }}" class="idc-field-cb"
                                                @checked(in_array($key, $selectedStaffFields))>
                                            <span class="idc-box"><x-icon name="check" :size="12" :stroke="3" /></span>
                                            <span class="idc-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="idc-actions">
                            <button type="reset" class="btn btn-secondary" id="idc-reset">{{ __('reset') }}</button>
                            <input class="btn btn-theme" id="create-btn" type="submit"
                                value="{{ __('save') . ' ' . __('configuration') }}">
                        </div>

                    </div>
                </form>
            </div>

            {{-- ============ RIGHT: LIVE PREVIEW ============ --}}
            <div class="col-lg-7 col-xl-8 grid-margin idc-preview-col">
                <div class="card">
                    <div class="card-body">
                        <div class="idc-preview-head">
                            <span class="idc-preview-badge">
                                <span class="idc-live-dot"></span>{{ __('live_preview') }}
                            </span>
                            <span class="idc-dim-chip">
                                <span id="idc-dim-w">54</span><span class="idc-dim-x">&times;</span><span
                                    id="idc-dim-h">85</span> {{ __('mm') }}
                                <span class="idc-orient" id="idc-orient">&middot; {{ __('portrait') }}</span>
                            </span>
                        </div>
                        <div class="idc-preview-stage">
                            <div id="id-card-preview"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    {{-- Server-injected data consumed by id_card_settings.js (keys MUST match window.idcSettings there). --}}
    <script type="text/javascript">
        window.idcSettings = {
            savedBackground: @json($savedBackground),
            savedSignature: @json($savedSignature),
            schoolName: @json($settings['school_name'] ?? 'School Name'),
            schoolAddress: @json($settings['school_address'] ?? ''),
            logo1Url: @json($logo1Url),
            logo2Url: @json($logo2Url),
            fallbackLogo: @json($fallbackLogo),
            savedSelectedFields: @json(array_values(array_intersect(array_keys($idCardFields), $selectedFields))),
            savedStaffSelectedFields: @json(array_values(array_intersect(array_keys($staffIdCardFields), $selectedStaffFields))),
            activeCardType: 'student',
            // i18n strings used in the renderer
            T: {
                portrait: @json(__('portrait')),
                landscape: @json(__('landscape')),
                recommended: @json(__('recommended')),
                mm: @json(__('mm')),
                signature: @json(__('signature')),
                selectOne: @json(__('please_select_at_least_one_field')),
                badDims: @json(__('card_size_validation')),
                student: @json(__('student')),
                staff: @json(__('staff'))
            }
        };
    </script>
    <script
        src="{{ asset('assets/js/id_card_settings.js') }}?v={{ filemtime(public_path('assets/js/id_card_settings.js')) }}"></script>
@endsection

