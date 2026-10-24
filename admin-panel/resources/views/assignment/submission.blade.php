@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('assignment') }}
@endsection

@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/assignment-submission.css') }}?v={{ filemtime(public_path('assets/css/assignment-submission.css')) }}">
@endsection

@section('content')
    

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('assignment_submission') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('assignment_submission') }}
                        </h4>

                        <div class="row align-items-end mt-3">
                            <div class="form-group col-sm-12 col-md-2">
                                <label for="filter_term_id" class="form-label text-nowrap">
                                    {{ __('term') }}
                                </label>
                                <select name="filter_term_id" id="filter_term_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                </select>
                            </div>

                            <div class="form-group col-sm-12 col-md-2">
                                <label for="filter_subject_id" class="form-label text-nowrap">
                                    {{ __('subject') }}
                                </label>
                                <select name="filter_subject_id" id="filter_subject_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">
                                            {{ $subject->name }} - {{ $subject->type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('assignment.submission.list') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                            data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                            data-query-params="AssignmentSubmissionQueryParams" data-sort-order="desc"
                            data-maintain-selected="true" data-export-types='["txt","excel"]'
                            data-export-options='{ "fileName": "assignment-submission-list-<?= date('d-m-y') ?>"
                            ,"ignoreColumn": ["operate"]}'
                            data-show-export="true" data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}</th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="assignment_name">
                                        {{ __('assignment_name') }}</th>
                                    <th scope="col" data-field="subject">{{ __('subject') }}</th>
                                    <th scope="col" data-field="student_name" data-formatter="studentSubmissionNameFormatter" data-escape="false">
                                        {{ __('student_name') }}</th>
                                    <th scope="col" data-field="text" data-formatter="textFormatter">{{ __('text') }}
                                        {{ __('submission') }}</th>
                                    <th scope="col" data-field="file" data-formatter="fileFormatter">{{ __('files') }}
                                    </th>
                                    <th scope="col" data-field="status" data-sortable="true"
                                        data-formatter="assignmentSubmissionStatusFormatter">{{ __('status') }}</th>
                                    <th scope="col" data-field="points" data-sortable="true" data-visible="true">
                                        {{ __('points') }}</th>
                                    <th scope="col" data-field="feedback" data-sortable="true">{{ __('feedback') }}
                                    </th>
                                    <th scope="col" data-field="session_year_id" data-sortable="true"
                                        data-visible="false">{{ __('session_year_id') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">
                                        {{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">
                                        {{ __('updated_at') }}</th>
                                    <th scope="col" data-field="operate" data-sortable="false" data-escape="false"
                                        data-events="assignmentSubmissionEvents">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Edit / Review Submission Modal --}}
            <div class="modal fade" id="editModal" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">

                        {{-- Header --}}
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title mb-0">{{ __('review_submission') }}</h5>
                                <div class="modal-subtitle" id="modal_subtitle">
                                    <span id="assignment_name"></span>
                                </div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>

                        <form id="edit-form" action="{{ url('assignment-submission') }}" novalidate="novalidate">
                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="modal-body p-0">
                                <div class="row no-gutters">

                                    <div class="col-lg-7 p-4">

                                        <div class="d-flex align-items-center mb-4">
                                            <div id="student_avatar_container" class="mr-3"></div>
                                            <div>
                                                <h6 class="student-name-text mb-1" id="student_name"></h6>
                                                <div class="student-meta-row">
                                                    <span class="student-info-badge" id="subject_badge">
                                                        <x-icon name="subject" :size="13" />
                                                        <span id="subject"></span>
                                                    </span>
                                                    <span class="student-meta-date">
                                                        <x-icon name="clock" :size="13" />
                                                        {{ __('submitted') }} <span id="submitted_date"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Attached Files --}}
                                        <div class="mb-4">
                                            <div class="section-label">
                                                <x-icon name="paperclip" :size="14" /> {{ __('attached_files') }}
                                            </div>
                                            <div id="files" data-empty-text="{{ __('no_file_uploaded') }}"></div>
                                        </div>

                                        {{-- Submission Text --}}
                                        <div>
                                            <div class="section-label">
                                                <x-icon name="file-text" :size="14" /> {{ __('submission') }} {{ __('text') }}
                                            </div>
                                            <div class="submission-text-box" id="text"
                                                data-empty-text="{{ __('no_submission_text') }}"></div>
                                        </div>

                                    </div>

                                    {{-- ── Right Panel ── --}}
                                    <div class="col-lg-5 right-panel">
                                        <div class="p-4">

                                            {{-- Status --}}
                                            <div class="section-label mb-2">{{ __('submission_status') }}</div>
                                            <div class="row mb-4">
                                                <div class="col-6">
                                                    <div class="status-card" id="card_accept" onclick="selectStatus(1)">
                                                        <div class="status-icon accept-icon">
                                                            <x-icon name="check" :size="18" />
                                                        </div>
                                                        <div class="status-label">{{ __('accept') }}</div>
                                                        <input type="radio" name="status" value="1" class="d-none" id="status_accept">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="status-card" id="card_reject" onclick="selectStatus(2)">
                                                        <div class="status-icon reject-icon">
                                                            <x-icon name="close" :size="18" />
                                                        </div>
                                                        <div class="status-label">{{ __('reject') }}</div>
                                                        <input type="radio" name="status" value="2" class="d-none" id="status_reject">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Grade Points --}}
                                            <div class="mb-4" id="points_div">
                                                <div class="d-flex justify-content-between align-items-end">
                                                    <div class="section-label mb-0">{{ __('grade') }} {{ __('points') }}</div>
                                                    <span class="grade-max-label" id="assignment_points"></span>
                                                </div>
                                                <input type="number" name="points" class="form-control points-input mt-1" id="points" min="0" step="0.1" placeholder="0.0">
                                            </div>

                                            {{-- Feedback --}}
                                            <div>
                                                <div class="section-label">{{ __('internal') }} {{ __('feedback') }}</div>
                                                <textarea id="feedback" name="feedback" class="form-control" rows="4" placeholder="{{ __('feedback') }}..."></textarea>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    {{ __('cancel') }}
                                </button>
                                <button class="btn btn-theme" type="submit">
                                    {{ __('submit_review') }}
                                </button>
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
       
        function setStudentAvatar(name, imageUrl) {
            var container = $('#student_avatar_container');
            container.empty();

            var hasImage = imageUrl
                && imageUrl.length > 0
                && !imageUrl.match(/\/storage\/?$/);

            if (hasImage) {
                var img = $('<img>')
                    .attr('src', imageUrl)
                    .addClass('student-avatar-img')
                    .on('error', function() {
                        
                        $(this).replaceWith(buildInitials(name));
                    });
                container.append(img);
            } else {
                container.append(buildInitials(name));
            }
        }

        function buildInitials(name) {
            var parts = (name || '').trim().split(/\s+/);
            var initials = '';
            if (parts.length >= 2) {
                initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
            } else if (parts.length === 1 && parts[0]) {
                initials = parts[0].charAt(0).toUpperCase();
            } else {
                initials = '?';
            }
            return $('<div>').addClass('student-avatar-initials').text(initials);
        }

        /**
         * Status card selection
         */
        function selectStatus(value) {
            // Reset both cards
            $('#card_accept').removeClass('active-accept');
            $('#card_reject').removeClass('active-reject');
            $('#status_accept').prop('checked', false);
            $('#status_reject').prop('checked', false);

            if (value === 1) {
                $('#card_accept').addClass('active-accept');
                $('#status_accept').prop('checked', true);
                if (window.assignmentHasPoints) {
                    $('#points_div').show();
                } else {
                    $('#points_div').hide();
                }
            } else if (value === 2) {
                $('#card_reject').addClass('active-reject');
                $('#status_reject').prop('checked', true);
                $('#points_div').hide();
            }
        }

       
        function loadTerms() {
            let session_year_id = $('#filter_session_year_global').val();
            let $termSelect = $('#filter_term_id');
            $termSelect.html('<option value="">{{ __('all') }}</option>');
            if (!session_year_id) return;

            let semUrl = baseUrl + '/session-year/' + session_year_id + '/terms';
            ajaxRequest('GET', semUrl, null, null, function(response) {
                var terms = response.data || [];
                var html = '<option value="">{{ __('all') }}</option>';
                $.each(terms, function(i, sem) {
                    html += '<option value="' + sem.id + '">' + sem.name + '</option>';
                });
                $termSelect.html(html);
            });
        }

        $('#filter_session_year_global').on('change', function() {
            loadTerms();
            $('#table_list').bootstrapTable('refresh');
        });

        $('#filter_term_id').on('change', function() {
            $('#table_list').bootstrapTable('refresh');
        });

        $(document).ready(function() {
            loadTerms();
        });
    </script>
@endsection
