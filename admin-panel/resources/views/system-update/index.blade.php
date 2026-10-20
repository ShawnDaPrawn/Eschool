@extends('layouts.master')

@section('title')
    {{ __('system_update') }}
@endsection

@section('css')
    <link rel="stylesheet"
        href="{{ asset('assets/css/system-update.css') }}?v={{ filemtime(public_path('assets/css/system-update.css')) }}">
@endsection

@php
    $currentVersion = isset($system_version['message']) ? $system_version['message'] : null;
@endphp

@section('content')
    <div class="content-wrapper sysupd-page">
        <div class="page-header">
            <div>
                <h3 class="page-title mb-0">{{ __('system_update') }}</h3>
                <p class="page-subtitle mb-0">{{ __('system_update_intro') }}</p>
            </div>
            @if ($currentVersion)
                <span class="sysupd-version-chip">
                    <x-icon name="package" :size="18" />
                    <span class="sysupd-version-chip__label">{{ __('current_version') }}</span>
                    <span class="sysupd-version-chip__value">{{ $currentVersion }}</span>
                </span>
            @endif
        </div>

        {{-- Critical safety message --}}
        <div class="sysupd-banner" role="alert">
            <span class="sysupd-banner__icon">
                <x-icon name="triangle-alert" :size="20" />
            </span>
            <div>
                <h4 class="sysupd-banner__title">{{ __('backup_your_data') }}</h4>
                <p class="sysupd-banner__text">{{ __('backup_before_update_notice') }}</p>
            </div>
        </div>

        <div class="row">
            {{-- Upload form --}}
            <div class="col-lg-8 grid-margin stretch-card">
                <div class="card">
                    <div class="card-header sysupd-card-header">
                        <span class="sysupd-card-header__icon">
                            <x-icon name="hard-drive-download" :size="20" />
                        </span>
                        <div>
                            <h6 class="card-title mb-0">{{ __('upload_update_package') }}</h6>
                            <p class="sysupd-card-header__sub mb-0">{{ __('upload_update_package_sub') }}</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('system-update') }}" id="system-update" method="POST"
                            enctype="multipart/form-data" novalidate="novalidate">
                            @csrf

                            <div class="sysupd-field">
                                <label class="sysupd-label" for="sysupd-file">
                                    {{ __('files') }} <span class="sysupd-req">*</span>
                                </label>

                                {{-- Visually hidden but keyboard-focusable: it is the real,
                                     labelled control. The dropzone below is a mouse affordance. --}}
                                <input type="file" id="sysupd-file" name="file" accept=".zip"
                                    class="sysupd-file-input" />

                                <div class="sysupd-dropzone">
                                    <div class="sysupd-dropzone__prompt">
                                        <span class="sysupd-dropzone__icon">
                                            <x-icon name="package" :size="26" />
                                        </span>
                                        <span class="sysupd-dropzone__primary">
                                            {{ __('drag_drop_update_file') }}
                                            <span class="sysupd-dropzone__browse">{{ __('or_browse_files') }}</span>
                                        </span>
                                        <span class="sysupd-dropzone__hint">{{ __('only_zip_file_allowed') }}</span>
                                    </div>

                                    <div class="sysupd-dropzone__file">
                                        <span class="sysupd-dropzone__file-icon">
                                            <x-icon name="file-archive" :size="22" />
                                        </span>
                                        <span class="sysupd-dropzone__file-meta">
                                            <span class="sysupd-dropzone__file-label">{{ __('selected_file') }}</span>
                                            <span class="sysupd-dropzone__file-name" data-file-name></span>
                                        </span>
                                        <button type="button" class="sysupd-dropzone__remove" data-file-remove
                                            aria-label="{{ __('remove') }}">
                                            <x-icon name="close" :size="16" />
                                        </button>
                                    </div>
                                </div>

                                @if ($currentVersion)
                                    <p class="sysupd-hint">
                                        {{ __('current_version_update_notice', ['version' => $currentVersion]) }}
                                    </p>
                                @endif
                            </div>

                            <div class="sysupd-actions">
                                <button type="submit" class="btn btn-theme sysupd-submit"
                                    data-loading-text="{{ __('Please Wait') }}">
                                    <span class="sysupd-submit__icon">
                                        <x-icon name="hard-drive-download" :size="18" />
                                    </span>
                                    <span>{{ __('install_update') }}</span>
                                </button>
                            </div>

                            {{-- Announces the busy state to assistive tech (the CSS
                                 spinner is decorative; .val() can't swap a button label). --}}
                            <span class="sysupd-sr-only" role="status" aria-live="polite" data-status></span>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Guidelines --}}
            <div class="col-lg-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-header sysupd-card-header">
                        <span class="sysupd-card-header__icon">
                            <x-icon name="list-ordered" :size="20" />
                        </span>
                        <h6 class="card-title mb-0">{{ __('update_guidelines') }}</h6>
                    </div>
                    <div class="card-body">
                        <ul class="sysupd-guide-list">
                            <li class="sysupd-guide-item">
                                <span class="sysupd-guide-item__icon">
                                    <x-icon name="list-ordered" :size="18" />
                                </span>
                                <div>
                                    <h6 class="sysupd-guide-item__title">{{ __('sequential_updates') }}</h6>
                                    <p class="sysupd-guide-item__text mb-0">{{ __('update_sequence_notice') }}</p>
                                </div>
                            </li>
                            <li class="sysupd-guide-item">
                                <span class="sysupd-guide-item__icon">
                                    <x-icon name="database" :size="18" />
                                </span>
                                <div>
                                    <h6 class="sysupd-guide-item__title">{{ __('after_the_update') }}</h6>
                                    <p class="sysupd-guide-item__text mb-0">{{ __('auto_migration_hint') }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/js/custom/system-update.js') }}?v={{ filemtime(public_path('assets/js/custom/system-update.js')) }}">
    </script>
@endsection
