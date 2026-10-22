@extends('layouts.master')

@section('title')
    {{ __('chat_settings') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('chat_settings') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('messages') }} {{ __('configuration') }}
                        </h4>
                        <hr>
                        @php
                            $max_files_or_images_in_one_message = ini_get('max_file_uploads');
                            $maxFileSizeStr = ini_get('upload_max_filesize');
                            // A closure, not a named function: the page may be rendered more than once in a process.
                            $returnBytes = function ($size_str) {
                                switch (substr($size_str, -1)) {
                                    case 'M':
                                    case 'm':
                                        return (int) $size_str * 1048576;
                                    case 'K':
                                    case 'k':
                                        return (int) $size_str * 1024;
                                    case 'G':
                                    case 'g':
                                        return (int) $size_str * 1073741824;
                                    default:
                                        return $size_str;
                                }
                            };
                            $maxFileSizeBytes = $returnBytes($maxFileSizeStr);
                            $maxFileSizeMB = $maxFileSizeBytes / (1024 * 1024); // Convert bytes to megabytes
                            $max_file_size_in_bytes = round($maxFileSizeMB, 2) . ' MB'; // Round to 2 decimal places for MB
                        @endphp
                        <form id="frmData" class="general-setting" action="{{ route('chat_setting.update') }}"
                            novalidate="novalidate" enctype="multipart/form-data">
                            @csrf
                            <div class="row mt-5">
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('max_files_upload') }} <span
                                            class="text-info">({{ __('in_single_message') }})</span></label>
                                    <input name="max_files_or_images_in_one_message"
                                        value="{{ isset($settings['max_files_or_images_in_one_message']) ? $settings['max_files_or_images_in_one_message'] : '10' }}"
                                        type="number" required placeholder="{{ __('max_files_upload') }}"
                                        class="form-control" min="2"
                                        max="{{ $max_files_or_images_in_one_message }}" />
                                    <p><strong>{{ __('note_max_file_uploads_allowed', ['count' => $max_files_or_images_in_one_message]) }}</strong>
                                    </p>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('max_file_size') }} <span
                                            class="text-info">({{ __('in_mb') }})</span></label>
                                    {{-- Never above what PHP accepts, or the form couldn't be saved at all. --}}
                                    <input name="max_file_size_in_bytes"
                                        value="{{ min((float) ($settings['max_file_size_in_bytes'] ?? 10), $maxFileSizeMB) }}"
                                        type="number" required placeholder="{{ __('max_file_size') }}"
                                        class="form-control" min="0.1" step="any" max="{{ $maxFileSizeMB }}" />
                                    <p><strong>{{ __('note_max_file_size_allowed', ['size' => $max_file_size_in_bytes]) }}</strong>
                                    </p>
                                </div>
                                <div class="form-group col-md-6 col-sm-12">
                                    <label>{{ __('max_characters') }} <span
                                            class="text-info">({{ __('in_text_message') }})</span></label>
                                    <input name="max_characters_in_text_message"
                                        value="{{ isset($settings['max_characters_in_text_message']) ? $settings['max_characters_in_text_message'] : '500' }}"
                                        type="number" required placeholder="{{ __('max_characters') }}"
                                        class="form-control" min="10" max="1000" />
                                </div>

                            </div>

                            <h4 class="card-title mt-5 mb-1">
                                {{ __('automatic_message_cleanup') }}
                            </h4>
                            <p class="text-muted mb-0">{{ __('automatic_message_cleanup_subtitle') }}</p>
                            <hr>

                            <div class="row mt-4">
                                <div class="form-group col-md-3 col-sm-12">
                                    <label>{{ __('delete_messages_older_than') }}</label>
                                    <div class="input-group">
                                        <input name="automatically_messages_removed_days"
                                            value="{{ isset($settings['automatically_messages_removed_days']) ? $settings['automatically_messages_removed_days'] : '30' }}"
                                            type="number" min="1" placeholder="30" class="form-control" required />
                                        <span class="input-group-append">
                                            <span class="input-group-text text-dark">{{ __('days') }}</span>
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="fa fa-info-circle mr-1"></i>{{ __('automatic_cleanup_help') }}
                                    </small>
                                </div>

                                <div class="form-group col-md-9 col-sm-12">
                                    <label>
                                        {{ __('one_time_server_setup') }}
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="scheduler_cron_command"
                                            class="form-control text-monospace"
                                            value="0 0 * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1"
                                            readonly aria-label="{{ __('one_time_server_setup') }}">
                                        <span class="input-group-append">
                                            <button class="btn btn-theme" type="button" id="copy_cron_command"
                                                data-target="#scheduler_cron_command">
                                                <i class="fa fa-copy mr-1"></i>
                                                {{ __('copy') }}
                                            </button>
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mt-2">{{ __('one_time_server_setup_help') }}</small>
                                </div>
                            </div>

                            <input class="btn btn-theme mt-4" type="submit" value="{{ __('submit') }}">
                        </form>
                    </div>
                </div>


            </div>

        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('manually') }} {{ __('delete') }} {{ __('messages') }}
                        </h4>
                        <hr>
                        <form id="chat-delete" class="chat-delete" action="{{ route('chat_message.delete') }}"
                            method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row mt-5">
                                <div class="form-group col-md-6">
                                    <label>{{ __('from_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="from_date" placeholder="{{ __('from_date') }}"
                                        class="datepicker-popup form-control" required>
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-md-5">
                                    <label>{{ __('to_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="to_date" placeholder="{{ __('to_date') }}"
                                        class="datepicker-popup form-control" required>
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-md-1 mt-4">
                                    <button class="btn btn-xs btn-danger btn-icon"><i class="fa fa-trash"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('#copy_cron_command').on('click', function() {
                var $input = $($(this).data('target'));
                var text = $input.val();

                var onCopied = function() {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast(@json(__('copied_to_clipboard')));
                    }
                };

                var legacyCopy = function() {
                    $input.prop('readonly', false).select();
                    document.execCommand('copy');
                    $input.prop('readonly', true);
                    window.getSelection().removeAllRanges();
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(onCopied).catch(function() {
                        legacyCopy();
                        onCopied();
                    });
                } else {
                    legacyCopy();
                    onCopied();
                }
            });
        });
    </script>
@endsection
