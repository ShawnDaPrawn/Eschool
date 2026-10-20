@extends('layouts.master')

@section('title')
{{ __('email_template') }}
@endsection
@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/email-template.css') }}?v={{ filemtime(public_path('assets/css/email-template.css')) }}">
@endsection
@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">{{ __('email_template') }}</h3>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">

                    

                    {{-- Toast notification (replaces full-page alert) --}}
                    <div id="toast-msg"></div>

                    <ul class="nav nav-tabs" id="emailTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="student-tab" data-toggle="tab" href="#student-registration" role="tab">
                               {{ __('student_email_template') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="staff-tab" data-toggle="tab" href="#staff" role="tab">
                                {{ __('staff_email_template') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="rejection-tab" data-toggle="tab" href="#rejection" role="tab">
                               {{ __('application_rejection') }}
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="emailTabContent">

                        {{-- ===== STUDENT TAB ===== --}}
                        <div class="tab-pane fade show active" id="student-registration" role="tabpanel">
                            <form id="studentForm">
                                @csrf
                                <input type="hidden" name="type" value="student">

                                <div class="form-group mb-3">
                                    <textarea id="student_content" name="content" rows="10" class="form-control">{!! $template->content ?? '' !!}</textarea>
                                </div>

                                <div class="d-flex flex-wrap align-items-center bg-light p-3 border rounded mb-3">
                                    <small class="w-100 mb-2 text-muted font-weight-bold">{{ __('click_to_insert_tag') }}</small>
                                    <button type="button" class="custom-tag-btn" data-tag="{name}" data-editor="student_content">{{ __('{name}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{school_name}" data-editor="student_content">{{ __('{school_name}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{school_contact}" data-editor="student_content">{{ __('{school_contact}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{school_email}" data-editor="student_content">{{ __('{school_email}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{url}" data-editor="student_content">{{ __('{url}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{android_app}" data-editor="student_content">{{ __('{android_app}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{ios_app}" data-editor="student_content">{{ __('{ios_app}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{child_name}" data-editor="student_content">{{ __('{child_name}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{child_grnumber}" data-editor="student_content">{{ __('{child_grnumber}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{child_password}" data-editor="student_content">{{ __('{child_password}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{class_name}" data-editor="student_content">{{ __('{class_name}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{username}" data-editor="student_content">{{ __('{username}') }}</button>
                                </div>

                                <button type="submit" class="btn btn-theme">{{ __('save_template') }}</button>
                            </form>
                        </div>

                        {{-- ===== STAFF TAB ===== --}}
                        <div class="tab-pane fade" id="staff" role="tabpanel">
                            <form id="staffForm">
                                @csrf
                                <input type="hidden" name="type" value="staff">

                                <div class="form-group mb-3">
                                    <textarea id="staff_content" name="content" rows="10" class="form-control">{!! $stafftemplate->content ?? '' !!}</textarea>
                                </div>

                                <div class="d-flex flex-wrap align-items-center bg-light p-3 border rounded mb-3">
                                    <small class="w-100 mb-2 text-muted font-weight-bold">{{ __('click_to_insert_tag') }}</small>
                                    <button type="button" class="custom-tag-btn" data-tag="{name}" data-editor="staff_content">{{ __('{name}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{role}" data-editor="staff_content">{{ __('{role}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{email}" data-editor="staff_content">{{ __('{email}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{password}" data-editor="staff_content">{{ __('{password}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{school_name}" data-editor="staff_content">{{ __('{school_name}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{android_app}" data-editor="staff_content">{{ __('{android_app}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{ios_app}" data-editor="staff_content">{{ __('{ios_app}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{url}" data-editor="staff_content">{{ __('{url}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{support_email}" data-editor="staff_content">{{ __('{support_email}') }}</button>
                                    <button type="button" class="custom-tag-btn" data-tag="{support_contact}" data-editor="staff_content">{{ __('{support_contact}') }}</button>
                                </div>

                                <button type="submit" class="btn btn-theme">{{ __('save_template') }}</button>
                            </form>
                        </div>
                        {{-- ===== REJECTION TAB ===== --}}
                        <div class="tab-pane fade" id="rejection" role="tabpanel" aria-labelledby="rejection-link">
                            <form action="{{ route('email.store') }}" method="post">
                                @csrf
                                <input type="hidden" name="type" value="rejection">

                                <div class="form-group mb-3">
                                    <textarea id="rejection_content" name="content">{!! $rejectionTempplate->content ?? '' !!}</textarea>
                                </div>

                                <div class="d-flex flex-wrap align-items-center bg-white p-3 border rounded shadow-sm mb-3" style="gap: 8px;">
                                     <small class="w-100 mb-2 text-muted font-weight-bold">{{ __('click_to_insert_tag') }}</small>
                                    <button type="button" class="custom-tag-btn btn btn-sm btn-outline-secondary" data-tag="{name}" data-editor="rejection_content">{{ __('{ name }') }}</button>
                                    <button type="button" class="custom-tag-btn btn btn-sm btn-outline-secondary" data-tag="{school_name}" data-editor="rejection_content">{{ __('{ School name }') }}</button>
                                    <button type="button" class="custom-tag-btn btn btn-sm btn-outline-secondary" data-tag="{child_name}" data-editor="rejection_content">{{ __('{ Child name }') }}</button>
                                    <button type="button" class="custom-tag-btn btn btn-sm btn-outline-secondary" data-tag="{school_email}" data-editor="rejection_content">{{ __('{ Support email }') }}</button>
                                    <button type="button" class="custom-tag-btn btn btn-sm btn-outline-secondary" data-tag="{school_contact}" data-editor="rejection_content">{{ __('{ Support contact }') }}</button>
                                    <button type="button" class="custom-tag-btn btn btn-sm btn-outline-secondary" data-tag="{class_name}" data-editor="rejection_content">{{ __('{ Class_name }') }}</button>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">{{ __('save_template') }}</button>
                            </form>
                        </div>




                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

    @section('script')

    @section('script')
<script src="{{ asset('assets/tinymce/tinymce.min.js') }}"></script>
<script>
    var storeUrl  = "{{ route('email.store') }}";
    var csrfToken = "{{ csrf_token() }}";

    function initEditor(id) {
        if (tinymce.get(id)) return;

        tinymce.init({
            selector: '#' + id,
            height: 450,
            menubar: true,
            plugins: 'lists link image table code',
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | code',
            setup: function(editor) {
                editor.on('change keyup', function() {
                    editor.save();
                });
            }
        });
    }

    $(document).on('submit', 'form', function(e) {
        e.preventDefault();
        var $btn         = $(this).find('button[type="submit"]');
        var originalText = $btn.text();

        $btn.prop('disabled', true).html("<span class='spinner-border spinner-border-sm mr-2' role='status' aria-hidden='true'></span>{{ __('saving_dot') }}");

        tinymce.triggerSave();
        var formData = new FormData(this);

        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                $btn.html('<span>✓ Saved!</span>');
                setTimeout(function() {
                    $btn.prop('disabled', false).text(originalText);
                }, 1500);
                showSuccessToast(response.message);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text(originalText);
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    msg = errors[Object.keys(errors)[0]][0];
                }
                showErrorToast(msg);
            }
        });
    });

    $(function() {
        initEditor('student_content');

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr('href');
            localStorage.setItem('activeEmailTab', target);

            if (target === '#student-registration') initEditor('student_content');
            if (target === '#staff')                initEditor('staff_content');
            if (target === '#rejection')            initEditor('rejection_content');
        });

        $(document).on('click', '.custom-tag-btn', function() {
            var editorId = $(this).data('editor');
            var tag      = $(this).data('tag');
            var editor   = tinymce.get(editorId);

            if (editor) {
                editor.insertContent(tag);
            } else {
                var $ta = $('#' + editorId);
                if ($ta.length) $ta.val($ta.val() + tag);
            }
        });
    });
</script>
@endsection
    @endsection