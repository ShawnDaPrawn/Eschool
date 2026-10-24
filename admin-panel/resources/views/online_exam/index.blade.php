@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('online') . ' ' . __('exam') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('online') . ' ' . __('exam') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">
                            {{ __('create') . ' ' . __('online') . ' ' . __('exam') }}
                        </h4>
                        <form class="pt-3 mt-6 create-online-exam" id="create-form" method="POST"
                            action="{{ route('online-exam.store') }}">

                            {{-- Session Year (shared, always on top) --}}
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label>{{ __('session_years') }} <span class="text-danger">*</span></label>
                                    <select required name="session_year_id" id="session_year_id"
                                        class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                        @foreach ($session_years as $years)
                                            <option value="{{ $years->id }}"
                                                {{ $years->default == 1 ? 'selected' : '' }}>
                                                {{ $years->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-md-4">
                                    <label>{{ __('term') }}</label>
                                    <select name="term_id" id="term_id" class="form-control select2"
                                        style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">--- {{ __('select') . ' ' . __('term') }} ---</option>
                                    </select>
                                    <small class="text-info mt-1 d-block">
                                        <i class="fa fa-info-circle"></i>
                                        {{ __('leave_unselected_for_non_term_classes') }}
                                    </small>
                                </div>
                            </div>

                            {{-- online exam based option --}}
                            <div class="form-group">
                                <label>{{ __('online_exam_based_on') }} <span class="text-danger">*</span> <i
                                        class="fa fa-question-circle ml-1" aria-hidden="true"
                                        title="{{ __('class_and_class_section_exam_info') }}"></i></label><br>
                                <div class="d-flex">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input type="radio" name="online_exam_based_on" class="online_exam_based_on"
                                                value="0">
                                            {{ __('class') }}
                                        <i class="input-helper"></i>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            <input type="radio" name="online_exam_based_on" class="online_exam_based_on"
                                                value="1" checked="true">
                                            {{ __('class_section') }}
                                        <i class="input-helper"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- class container  --}}
                            <div class="class_container" style="display : none">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                        <select name="class_id" class="form-control select2 online-exam-class-id"
                                            style="width:100%;" tabindex="-1" aria-hidden="true">
                                            <option value="">--- {{ __('select') . ' ' . __('class') }} ---</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                                        <select name="subject_class_id" class="form-control select2 online-exam-subject-id"
                                            style="width:100%;" tabindex="-1" aria-hidden="true">
                                            <option value="">--- {{ __('select') . ' ' . __('subject') }} ---
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="online-exam-title" name="title_class"
                                            placeholder="{{ __('title') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('exam') }} {{ __('key') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="online-exam-key" name="exam_key_class"
                                            placeholder="{{ __('exam_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('duration') }} <span class="text-danger">*</span></label><span
                                            class="text-info small">( {{ __('in_minutes') }} )</span>
                                        <input type="number" id="online-exam-duration" name="duration_class"
                                            placeholder="{{ __('duration') }}" min="1" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="online-exam-start-date" name="start_date_class"
                                            placeholder="{{ __('start_date') }}"
                                            class='form-control datetimepicker-popup'>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="online-exam-end-date" name="end_date_class"
                                            placeholder="{{ __('end_date') }}"
                                            class='form-control datetimepicker-popup'>
                                    </div>
                                </div>
                            </div>

                            {{-- class section container --}}
                            <div class="class_section_container">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>{{ __('class_section') }} <span class="text-danger">*</span></label>
                                        <select name="class_section_id"
                                            class="form-control select2 online-exam-class-section-id" style="width:100%;"
                                            tabindex="-1" aria-hidden="true">
                                            <option value="">--- {{ __('select') . ' ' . __('class_section') }} ---
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                                        <select name="subject_class_section_id"
                                            class="form-control select2 online-exam-subject-id" style="width:100%;"
                                            tabindex="-1" aria-hidden="true">
                                            <option value="">--- {{ __('select') . ' ' . __('subject') }} ---
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="online-exam-title" name="title_class_section"
                                            placeholder="{{ __('title') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('exam') }} {{ __('key') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="online-exam-key" name="exam_key_class_section"
                                            placeholder="{{ __('exam_key') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('duration') }} <span class="text-danger">*</span></label><span
                                            class="text-info small">( {{ __('in_minutes') }} )</span>
                                        <input type="number" id="online-exam-duration" name="duration_class_section"
                                            placeholder="{{ __('duration') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="online-exam-start-date"
                                            name="start_date_class_section"
                                            placeholder="{{ __('start_date') }}" class='form-control datetimepicker-popup'>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="online-exam-end-date"
                                            name="end_date_class_section"
                                            placeholder="{{ __('end_date') }}" class='form-control datetimepicker-popup'>
                                    </div>
                                </div>
                            </div>

                            <input class="btn btn-theme" id="add-online-exam-btn" type="submit"
                                value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('exams') }}
                        </h4>
                        <div class="row g-3 align-items-end">
                            <div class="form-group col-sm-12 col-md-2">

                                <label for="filter-term-id" class="form-label text-nowrap">
                                    {{ __('term') }}
                                </label>
                                <select name="term_id" id="filter-term-id"
                                    class="form-control select2 filter-term-id">
                                    <option value="">{{ __('all') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-2">

                                <label for="filter-online-exam-class-id" class="form-label">
                                    {{ __('class') }}
                                </label>
                                <select name="class_id" id="filter-online-exam-class-id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                </select>
                            </div>

                            <div class="form-group col-sm-12 col-md-2">

                                <label for="filter-online-exam-subject-id" class="form-label">
                                    {{ __('subject') }}
                                </label>
                                <select name="subject_id" id="filter-online-exam-subject-id"
                                    class="form-control select2">
                                    <option value="">{{ __('all') }}</option>
                                </select>
                            </div>
                        </div>

                        <table aria-describedby="mydesc" data-escape="true" class='table' id='table_list'
                            data-toggle="table" data-url="{{ route('online-exam.show', 1) }}"
                            data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                            data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                            data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]'
                            data-export-options='{ "fileName": "{{ __('online') . ' ' . __('exam') }}-<?= date(' d-m-y')
                            ?>"
                            ,"ignoreColumn":["operate"]}' data-show-export="true"
                            data-query-params="onlineExamQueryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="online_exam_id" data-sortable="true"
                                        data-visible="false">{{ __('id') }}
                                    </th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="class_name" data-sortable="false">{{ __('class') }}
                                    </th>
                                    <th scope="col" data-field="subject_name" data-sortable="false">
                                        {{ __('subject') }}
                                    </th>
                                    <th scope="col" data-field="term_name" data-sortable="false">
                                        {{ __('term') }}
                                    </th>
                                    <th scope="col" data-field="title" data-sortable="false">{{ __('title') }}</th>
                                    <th scope="col" data-field="exam_key" data-sortable="false" data-align="center">
                                        {{ __('exam_key') }}
                                    </th>
                                    <th scope="col" data-field="duration" data-sortable="false" data-align="center">
                                        {{ __('duration') }}({{ __('in_minutes') }})
                                    </th>
                                    <th scope="col" data-field="start_date" data-sortable="true">
                                        {{ __('start_date') }}
                                    </th>
                                    <th scope="col" data-field="end_date" data-sortable="true">{{ __('end_date') }}
                                    </th>
                                    <th scope="col" data-field="total_questions" data-sortable="false"
                                        data-align="center">{{ __('total') . ' ' . __('questions') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true"
                                        data-visible="false">{{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true"
                                        data-visible="false">{{ __('updated_at') }}</th>
                                    <th scope="col" data-escape="false" data-field="operate" data-sortable="false"
                                        data-events="onlineExamEvents">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- model --}}
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') }} {{ __('online') }}
                        {{ __('exam') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="edit-form" class="pt-3 edit-form" action="{{ url('online-exam') }}">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ __('term') }}</label>
                            <input type="text" id="edit-online-exam-term" class="form-control" readonly />
                            <input type="hidden" id="edit-online-exam-term-id" name="term_id" />
                        </div>
                        <div class="form-group">
                            <label>{{ __('title') }} <span class="text-danger">*</span></label>
                            <input type="text" id="edit-online-exam-title" name="edit_title"
                                placeholder="{{ __('title') }}" class="form-control" />
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('exam') }} {{ __('key') }} <span class="text-danger">*</span></label>
                                <input type="number" id="edit-online-exam-key" name="edit_exam_key"
                                    placeholder="{{ __('exam_key') }}" class="form-control" />
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('duration') }} <span class="text-danger">*</span></label><span
                                    class="text-info small">( {{ __('in_minutes') }} )</span>
                                <input type="number" id="edit-online-exam-duration" name="edit_duration"
                                    placeholder="{{ __('duration') }}" class="form-control" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" id="edit-online-exam-start-date" name="edit_start_date"
                                    placeholder="{{ __('start_date') }}" class='form-control datetimepicker-popup'>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" id="edit-online-exam-end-date" name="edit_end_date"
                                    placeholder="{{ __('end_date') }}" class='form-control datetimepicker-popup'>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ __('close') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // Session year date ranges for validation
        var sessionYearDates = {};
        @foreach ($session_years as $years)
            sessionYearDates[{{ $years->id }}] = {
                start: '{{ \Carbon\Carbon::parse($years->start_date)->format('Y-m-d\TH:i') }}',
                end: '{{ \Carbon\Carbon::parse($years->end_date)->format('Y-m-d\TH:i') }}'
            };
        @endforeach

        // Date constraint helpers
        function updateDateConstraints() {
            var sessionYearId = $('#session_year_id').val();

            if (sessionYearId && sessionYearDates[sessionYearId]) {
                var minDate = sessionYearDates[sessionYearId].start;
                var maxDate = sessionYearDates[sessionYearId].end;

                $('.create-online-exam .datetimepicker-popup').each(function() {
                    if (this._flatpickr) {
                        this._flatpickr.set('minDate', minDate);
                        this._flatpickr.set('maxDate', maxDate);
                    }
                });
            }

            updateEndDateMin();
        }

        function updateEndDateMin() {
            var isClassSection = $('input[name="online_exam_based_on"]:checked').val() == '1';
            var startDateVal, endDateInput;

            if (isClassSection) {
                startDateVal = $('input[name="start_date_class_section"]').val();
                endDateInput = $('input[name="end_date_class_section"]');
            } else {
                startDateVal = $('input[name="start_date_class"]').val();
                endDateInput = $('input[name="end_date_class"]');
            }

            if (startDateVal && endDateInput.length && endDateInput[0]._flatpickr) {
                endDateInput[0]._flatpickr.set('minDate', startDateVal);
            }
        }

        // Populate class / class-section dropdowns from a data array
        function populateClassDropdowns(classes, classSections) {
            var clHtml = "<option value=''>--- {{ __('select') . ' ' . __('class') }} ---</option>";
            classes.forEach(function(item) {
                clHtml += "<option value='" + item.id + "'>" + item.name + "</option>";
            });
            $('.online-exam-class-id').html(clHtml).trigger('change.select2');

            var csHtml = "<option value=''>--- {{ __('select') . ' ' . __('class_section') }} ---</option>";
            classSections.forEach(function(item) {
                csHtml += "<option value='" + item.id + "'>" + item.name + "</option>";
            });
            $('.online-exam-class-section-id').html(csHtml).trigger('change.select2');

            // Reset subject dropdowns whenever classes/sections change
            $('.online-exam-subject-id').html(
                "<option value=''>--- " + lang_select_subject + " ---</option>"
            ).trigger('change.select2');
        }

        // Load everything by session year (initial + session year change)
        // Populates: terms, all classes (unfiltered), all class sections,
        // filter-class dropdown, filter-subject dropdown.
        function loadClassSectionsBySessionYear(sessionYearId, context) {
            if (!sessionYearId) return;

            var url = baseUrl + '/get-class-sections-by-session-year';
            var data = {
                'session_year_id': sessionYearId
            };

            function successCallback(response) {
                if (response.error) return;

                if (context === 'create' || context === 'both') {
                    // Populate terms dropdown
                    var smHtml = "<option value=''>--- {{ __('select') . ' ' . __('term') }} ---</option>";
                    response.data.terms.forEach(function(item) {
                        smHtml += "<option value='" + item.id + "'>" + item.name + "</option>";
                    });
                    $('#term_id').html(smHtml).trigger('change.select2');

                    // Show ALL classes / sections (no term selected yet)
                    populateClassDropdowns(response.data.classes, response.data.class_sections);
                }

                if (context === 'filter' || context === 'both') {
                    // Populate filter term dropdown
                    var filterSmHtml = "<option value=''>{{ __('all') }}</option>";
                    response.data.terms.forEach(function(item) {
                        filterSmHtml += "<option value='" + item.id + "'>" + item.name + "</option>";
                    });
                    $('.filter-term-id').html(filterSmHtml).trigger('change.select2');

                    // Populate filter class dropdown
                    var filterClHtml = "<option value=''>{{ __('all') }}</option>";
                    response.data.classes.forEach(function(item) {
                        filterClHtml += "<option value='" + item.id + "'>" + item.name + "</option>";
                    });
                    $('#filter-online-exam-class-id').html(filterClHtml);

                    // Populate filter subject dropdown
                    var filterSubHtml = "<option value=''>{{ __('all') }}</option>";
                    response.data.subjects.forEach(function(item) {
                        filterSubHtml += "<option value='" + item.id + "'>" + item.name + "</option>";
                    });
                    $('#filter-online-exam-subject-id').html(filterSubHtml).trigger('change.select2');
                }
            }

            ajaxRequest('GET', url, data, null, successCallback, null, null, true);
        }

        // Load classes / class-sections filtered by term (create form only)
        // Called every time the term dropdown changes.
        function loadClassSectionsByTerm(sessionYearId, termId) {
            if (!sessionYearId) return;

            var url = baseUrl + '/get-class-sections-by-term';
            var data = {
                'session_year_id': sessionYearId,
                'term_id': termId // empty string → non-term classes
            };

            function successCallback(response) {
                if (response.error) return;
                populateClassDropdowns(response.data.classes, response.data.class_sections);
            }

            ajaxRequest('GET', url, data, null, successCallback, null, null, true);
        }

        // Document ready
        $(document).ready(function() {
            // Initial load for create form
            loadClassSectionsBySessionYear($('#session_year_id').val(), 'create');

            // Initial load for filter section
            loadClassSectionsBySessionYear($('#filter_session_year_global').val(), 'filter');

            // Set date constraints
            updateDateConstraints();

            // Create form: session year changes
            $(document).on('change', '#session_year_id', function() {
                loadClassSectionsBySessionYear($(this).val(), 'create');
                updateDateConstraints();

                ['start_date_class_section', 'end_date_class_section', 'start_date_class', 'end_date_class']
                    .forEach(function(n) {
                        var el = document.querySelector('input[name="' + n + '"]');
                        if (el && el._flatpickr) el._flatpickr.clear();
                    });
            });

            // Create form: term changes → reload classes/sections from server
            $(document).on('change', '#term_id', function() {
                var sessionYearId = $('#session_year_id').val();
                var termId = $(this).val(); // may be '' for no term
                loadClassSectionsByTerm(sessionYearId, termId);
            });

            // Filter section: session year changes
            $(document).on('change', '#filter_session_year_global', function() {
                loadClassSectionsBySessionYear($(this).val(), 'filter');
                $('#table_list').bootstrapTable('refresh');
            });

            // Filter section: term changes
            $(document).on('change', '.filter-term-id', function() {
                $('#table_list').bootstrapTable('refresh');
            });

            // Filter section: class changes
            $(document).on('change', '#filter-online-exam-class-id', function() {
                $('#table_list').bootstrapTable('refresh');
            });

            // Filter section: subject changes
            $(document).on('change', '#filter-online-exam-subject-id', function() {
                $('#table_list').bootstrapTable('refresh');
            });

            // Start date changes → update end date minimum
            $(document).on('change', 'input[name="start_date_class"], input[name="start_date_class_section"]',
                function() {
                    updateEndDateMin();

                    var isClassSection = $('input[name="online_exam_based_on"]:checked').val() == '1';
                    var endDateInput = isClassSection ?
                        $('input[name="end_date_class_section"]') :
                        $('input[name="end_date_class"]');

                    if (endDateInput.val() && endDateInput.val() < $(this).val()) {
                        if (endDateInput[0]._flatpickr) endDateInput[0]._flatpickr.clear();
                    }
                });

            // Exam based on (class vs class section) changes
            $(document).on('change', '.online_exam_based_on', function() {
                updateDateConstraints();
            });

            // Edit modal: start date changes
            $(document).on('change', '#edit-online-exam-start-date', function() {
                var startDate = $(this).val();
                var endEl = document.getElementById('edit-online-exam-end-date');
                if (!endEl || !endEl._flatpickr) return;
                if (startDate) {
                    endEl._flatpickr.set('minDate', startDate);
                }
                var endDate = endEl.value;
                if (endDate && startDate && endDate < startDate) {
                    endEl._flatpickr.clear();
                }
            });
        });
    </script>
@endsection
