@extends('layouts.master')

@section('title')
    {{ __('app_settings') }}
@endsection

@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/settings.css') }}?v={{ filemtime(public_path('assets/css/settings.css')) }}">
@endsection

@section('content')
    {{-- student App Settings --}}
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('app_settings')" />
        <div class="panel-form-grid">
            <div class="">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content panel-record-card">
                        <form id="formdata" class="general-setting" action="{{ url('app-settings') }}"
                            novalidate="novalidate">
                            @csrf
                            <h4 class="card-title">
                                {{ __('student_parent_app_settings') }}
                            </h4>
                            <div class="pt-3 row">
                                <div class="form-group  ">
                                    <label>{{ __('app_link') }}</label>
                                    <input name="app_link"
                                        value="{{ isset($settings['app_link']) ? $settings['app_link'] : '' }}"
                                        type="url" required placeholder="{{ __('app_link') }}" class="m3-text-field__input" />
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('ios_app_link') }}</label>
                                    <input name="ios_app_link"
                                        value="{{ isset($settings['ios_app_link']) ? $settings['ios_app_link'] : '' }}"
                                        type="url" required placeholder="{{ __('ios_app_link') }}"
                                        class="m3-text-field__input" />
                                </div>
                            </div>
                            <div class="panel-form-grid">
                                <div class="form-group  ">
                                    <label>{{ __('app_version') }}</label>
                                    <input name="app_version"
                                        value="{{ isset($settings['app_version']) ? $settings['app_version'] : '' }}"
                                        type="text" required placeholder="{{ __('app_version') }}"
                                        class="m3-text-field__input" />
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('ios_app_version') }}</label>
                                    <input type="text" name="ios_app_version" required
                                        placeholder="{{ __('ios_app_version') }}" class="m3-text-field__input"
                                        value="{{ isset($settings['ios_app_version']) ? $settings['ios_app_version'] : '' }}">
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('force_app_update') }}</label>
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input"
                                                value="{{ isset($settings['force_app_update']) ? $settings['force_app_update'] : 0 }}"
                                                id="force_app_update">{{ __('force_app_update') }}
                                            <i class="input-helper"></i></label>
                                    </div>
                                    <input type="hidden" name="force_app_update" id="txt_force_app_update">
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('app_maintenance') }}</label>
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input"
                                                value="{{ isset($settings['app_maintenance']) ? $settings['app_maintenance'] : 0 }}"
                                                id="app_maintenance">{{ __('app_maintenance') }}
                                            <i class="input-helper"></i></label>
                                    </div>
                                    <input type="hidden" name="app_maintenance" id="txt_app_maintenance">
                                </div>
                            </div>
                            <hr class="pt-4 pd-4">
                            <h4 class="card-title">
                                {{ __('teacher_app_settings') }}
                            </h4>
                            <div class="pt-3 row">
                                <div class="form-group  ">
                                    <label>{{ __('app_link') }}</label>
                                    <input name="teacher_app_link"
                                        value="{{ isset($settings['teacher_app_link']) ? $settings['teacher_app_link'] : '' }}"
                                        type="url" required placeholder="{{ __('app_link') }}"
                                        class="m3-text-field__input" />
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('ios_app_link') }}</label>
                                    <input name="teacher_ios_app_link"
                                        value="{{ isset($settings['teacher_ios_app_link']) ? $settings['teacher_ios_app_link'] : '' }}"
                                        type="url" required placeholder="{{ __('ios_app_link') }}"
                                        class="m3-text-field__input" />
                                </div>
                            </div>
                            <div class="panel-form-grid">
                                <div class="form-group  ">
                                    <label>{{ __('app_version') }}</label>
                                    <input name="teacher_app_version"
                                        value="{{ isset($settings['teacher_app_version']) ? $settings['teacher_app_version'] : '' }}"
                                        type="text" required placeholder="{{ __('app_version') }}"
                                        class="m3-text-field__input" />
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('ios_app_version') }}</label>
                                    <input type="text" name="teacher_ios_app_version" required
                                        placeholder="{{ __('ios_app_version') }}" class="m3-text-field__input"
                                        value="{{ isset($settings['teacher_ios_app_version']) ? $settings['teacher_ios_app_version'] : '' }}">
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('force_app_update') }}</label>
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input"
                                                value="{{ isset($settings['teacher_force_app_update']) ? $settings['teacher_force_app_update'] : 0 }}"
                                                id="teacher_force_app_update">{{ __('force_app_update') }}
                                            <i class="input-helper"></i></label>
                                    </div>
                                    <input type="hidden" name="teacher_force_app_update" id="teacher_txt_force_app_update">
                                </div>
                                <div class="form-group  ">
                                    <label>{{ __('app_maintenance') }}</label>
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input"
                                                value="{{ isset($settings['teacher_app_maintenance']) ? $settings['teacher_app_maintenance'] : 0 }}"
                                                id="teacher_app_maintenance">{{ __('app_maintenance') }}
                                            <i class="input-helper"></i></label>
                                    </div>
                                    <input type="hidden" name="teacher_app_maintenance"
                                        id="teacher_txt_app_maintenance">
                                </div>
                            </div>
                            <hr>
                            <div class="settings-actions">
                                <button type="submit" class="m3-button m3-button--filled">{{ __('submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function app_setting() {
            force_app_update = $('#force_app_update').val();
            app_maintenance = $('#app_maintenance').val();
            if (force_app_update == 1) {
                $('#force_app_update').attr('checked', true);
                $('#force_app_update').val(1);
                $('#txt_force_app_update').val(1);
            } else {
                $('#force_app_update').val(0);
                $('#txt_force_app_update').val(0);
            }
            if (app_maintenance == 1) {
                $('#app_maintenance').attr('checked', true);
                $('#app_maintenance').val(1);
                $('#txt_app_maintenance').val(1);
            } else {
                $('#app_maintenance').val(0);
                $('#txt_app_maintenance').val(0);
            }

            teacher_force_app_update = $('#teacher_force_app_update').val();
            teacher_app_maintenance = $('#teacher_app_maintenance').val();

            if (teacher_force_app_update == 1) {
                $('#teacher_force_app_update').attr('checked', true);
                $('#teacher_force_app_update').val(1);
                $('#teacher_txt_force_app_update').val(1);
            } else {
                $('#teacher_force_app_update').val(0);
                $('#teacher_txt_force_app_update').val(0);
            }
            if (teacher_app_maintenance == 1) {
                $('#teacher_app_maintenance').attr('checked', true);
                $('#teacher_app_maintenance').val(1);
                $('#teacher_txt_app_maintenance').val(1);
            } else {
                $('#teacher_app_maintenance').val(0);
                $('#teacher_txt_app_maintenance').val(0);
            }

        }
        $(document).ready(function() {
            app_setting();
        });
        $(document).on('change', '#force_app_update', function(e) {
            if ($('#force_app_update').val() == 1) {
                $('#force_app_update').val(0);
                $('#txt_force_app_update').val(0);
            } else {
                $('#force_app_update').val(1);
                $('#txt_force_app_update').val(1);
            }
        });
        $(document).on('change', '#app_maintenance', function(e) {
            if ($('#app_maintenance').val() == 1) {
                $('#app_maintenance').val(0);
                $('#txt_app_maintenance').val(0);
            } else {
                $('#app_maintenance').val(1);
                $('#txt_app_maintenance').val(1);
            }
        });

        $(document).on('change', '#teacher_force_app_update', function(e) {
            if ($('#teacher_force_app_update').val() == 1) {
                $('#teacher_force_app_update').val(0);
                $('#teacher_txt_force_app_update').val(0);
            } else {
                $('#teacher_force_app_update').val(1);
                $('#teacher_txt_force_app_update').val(1);
            }
        });
        $(document).on('change', '#teacher_app_maintenance', function(e) {
            if ($('#teacher_app_maintenance').val() == 1) {
                $('#teacher_app_maintenance').val(0);
                $('#teacher_txt_app_maintenance').val(0);
            } else {
                $('#teacher_app_maintenance').val(1);
                $('#teacher_txt_app_maintenance').val(1);
            }
        });
    </script>
@endsection
