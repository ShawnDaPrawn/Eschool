@extends('layouts.master')

@section('title')
    {{ __('reset_password') }}
@endsection

@section('css')
    <link rel="stylesheet"
        href="{{ asset('assets/css/reset-password.css') }}?v={{ filemtime(public_path('assets/css/reset-password.css')) }}">
@endsection

@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('reset_password')" :subtitle="__('Confirm your current password, then choose a new one. Passwords are never saved in a browser draft.')" />
        <div class="panel-record-layout">
            <div class="m3-stack">
                <div class="m3-card m3-card--outlined">
                    <div class="m3-card__content">
                        <form id="reset-password-form" class="reset-password-form" action="{{ url('changePassword') }}"
                            method="POST" novalidate="novalidate" data-check-url="{{ url('checkPassword') }}"
                            data-min-length="{{ \App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH }}"
                            data-msg-min-length="{{ __('password_rules_hint') }}"
                            data-msg-mismatch="{{ __('passwords_do_not_match') }}">
                            @csrf

                            <div class="page-field">
                                <label for="old_password">{{ __('old_password') }}</label>
                                <div class="password-field">
                                    <input type="password" name="old_password" id="old_password" required
                                        autocomplete="current-password" placeholder="{{ __('old_password') }}"
                                        class="m3-text-field__input" />
                                    <button type="button" class="password-toggle" aria-label="{{ __('show_password') }}" aria-pressed="false"
                                        data-show-label="{{ __('show_password') }}"
                                        data-hide-label="{{ __('hide_password') }}">
                                        <x-icon name="eye" class="pw-eye" :size="18" />
                                        <x-icon name="eye-off" class="pw-eye-off" :size="18" />
                                    </button>
                                </div>
                                <span id="old_password_status" class="password-status" role="status"
                                    aria-live="polite" aria-atomic="true"
                                    data-checking-label="{{ __('checking') }}"
                                    data-valid-label="{{ __('current_password_correct') }}"
                                    data-invalid-label="{{ __('current_password_incorrect') }}"></span>
                            </div>

                            <div class="page-field">
                                <label for="new_password">{{ __('new_password') }}</label>
                                <div class="password-field">
                                    <input type="password" name="new_password" id="new_password" required minlength="{{ \App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH }}"
                                        autocomplete="new-password" placeholder="{{ __('new_password') }}"
                                        class="m3-text-field__input" />
                                    <button type="button" class="password-toggle" aria-label="{{ __('show_password') }}" aria-pressed="false"
                                        data-show-label="{{ __('show_password') }}"
                                        data-hide-label="{{ __('hide_password') }}">
                                        <x-icon name="eye" class="pw-eye" :size="18" />
                                        <x-icon name="eye-off" class="pw-eye-off" :size="18" />
                                    </button>
                                </div>
                                <small class="password-hint">{{ __('password_rules_hint') }}</small>
                            </div>

                            <div class="page-field">
                                <label for="confirm_password">{{ __('confirm_password') }}</label>
                                <div class="password-field">
                                    <input type="password" name="confirm_password" id="confirm_password" required
                                        autocomplete="new-password" placeholder="{{ __('confirm_password') }}"
                                        class="m3-text-field__input" />
                                    <button type="button" class="password-toggle" aria-label="{{ __('show_password') }}" aria-pressed="false"
                                        data-show-label="{{ __('show_password') }}"
                                        data-hide-label="{{ __('hide_password') }}">
                                        <x-icon name="eye" class="pw-eye" :size="18" />
                                        <x-icon name="eye-off" class="pw-eye-off" :size="18" />
                                    </button>
                                </div>
                            </div>

                            <div class="reset-password-actions">
                                <button class="m3-button m3-button--filled" type="submit">{{ __('submit') }}</button>
                            </div>
                        </form>

                        {{-- Hidden icon templates cloned by reset-password.js into the status line. --}}
                        <span id="pw-icon-valid" hidden><x-icon name="check" :size="16" /></span>
                        <span id="pw-icon-invalid" hidden><x-icon name="close" :size="16" /></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script
        src="{{ asset('assets/js/custom/reset-password.js') }}?v={{ filemtime(public_path('assets/js/custom/reset-password.js')) }}">
    </script>
@endsection
