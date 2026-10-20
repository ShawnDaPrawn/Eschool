@extends('layouts.master')

@section('title')
    {{ __('semester') }}
@endsection

@section('content')
    <div class="content-wrapper">
        {{-- Page Header --}}
        <div class="page-header semester-manage-header">
            <h3 class="page-title">
                {{ __('semester_management') }}
            </h3>
            <div class="d-flex align-items-center">
                <span class="mr-2 text-muted semester-header-subtitle">{{ __('manage_semesters_for_session_year') }}:</span>
                <select id="semester_session_year" class="form-control semester-session-dropdown">
                    @php
                        $hasCurrentSessionYear = $session_years->contains(
                            fn($year) => (int) ($year->default ?? 0) === 1,
                        );
                    @endphp
                    @foreach ($session_years as $session_year)
                        <option value="{{ $session_year->id }}" data-name="{{ $session_year->name }}"
                            data-start-date="{{ $session_year->start_date ?? '' }}"
                            data-end-date="{{ $session_year->end_date ?? '' }}"
                            {{ (int) ($session_year->default ?? 0) === 1 || (!$hasCurrentSessionYear && $loop->first) ? 'selected' : '' }}>
                            {{ $session_year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Info Banner --}}
        <div class="semester-info-banner mb-4">
            <x-icon name="info" :size="18" />
            <span>{{ __('semesters_session_year_info') }}</span>
        </div>

        <div class="row">
            {{-- Class Assignment Configuration --}}
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">{{ __('class_assignment_configuration') }}</h4>
                            @if (Auth::user()->can('semester-edit'))
                                <button type="button" class="btn btn-sm btn-theme-soft d-inline-flex align-items-center"
                                    id="btn-configure-classes">
                                    <x-icon name="settings" :size="16" class="mr-1" />
                                    {{ __('configure_classes') }}
                                </button>
                            @endif
                        </div>
                        <hr class="config-divider">
                        {{-- No-semester warning – shown via JS when no semesters exist for this session year --}}
                        <div id="no-semester-config-warning" class="no-semester-warning d-none">
                            <x-icon name="alert" :size="16" />
                            <span>{{ __('no_semesters_create_first') }}</span>
                        </div>
                        <p class="text-muted mb-2 config-section-label">
                            {{ __('semesters_will_be_automatically_assigned_to') }}:</p>
                        <div class="assigned-classes-section">
                            <div id="assigned-classes-badges">
                                {{-- Populated via JS --}}
                                <span class="text-muted">{{ __('loading') }}</span>
                            </div>
                        </div>
                        <div class="config-hint mt-3">
                            <x-icon name="tip" :size="16" />
                            {{ __('class_auto_assign_note') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Semesters List Section --}}
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title mb-1">{{ __('semesters') }}</h4>
                            </div>
                            @if (Auth::user()->can('semester-create'))
                                <button type="button" class="btn btn-theme" id="btn-create-semester" data-toggle="modal"
                                    data-target="#createSemesterModal">
                                    {{ __('create_semester') }}
                                </button>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table aria-describedby="semester-table-desc" class="table table-striped" id="semester-table"
                                data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true"
                                data-show-columns="true" data-mobile-responsive="true" data-sort-name="id"
                                data-sort-order="asc" data-maintain-selected="true" data-escape="false"
                                data-query-params="semesterQueryParams">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="name" data-sortable="true">
                                            {{ __('semester_name') }}</th>
                                        <th scope="col" data-field="start_date" data-sortable="true">
                                            {{ __('start_date') }}</th>
                                        <th scope="col" data-field="end_date" data-sortable="true">
                                            {{ __('end_date') }}</th>
                                        <th scope="col" data-field="status" data-sortable="false"
                                            data-formatter="semesterStatusFormatter">{{ __('status') }}</th>
                                        <th scope="col" data-field="operate" data-escape="false" data-sortable="false"
                                            class="text-right">{{ __('action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configure Classes Modal --}}
        <div class="modal fade" id="configureClassesModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header border-bottom remove-bottom-padding">
                        <div>
                            <h5 class="modal-title">{{ __('configure_class_assignment') }}</h5>
                            <p class="text-muted mb-0 mt-1 small">
                                {{ __('configure_class_assignment_desc') }}
                            </p>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="padding-md">
                        {{-- ⚠ Warning Note --}}
                        <div class="semester-modal-warning">
                            <x-icon name="alert" :size="16" />
                            <span>
                                <strong>{{ __('note') }}:</strong>
                                {{ __('changing_class_assignment_warning') }}
                            </span>
                        </div>
                    </div>

                    <div class="modal-body remove-bottom-padding">

                        <label>{{ __('select_classes') }} <span class="text-danger">*</span></label>

                        <div id="class-selector-container">
                            {{-- Selected classes chips --}}
                            <div id="selected-classes-chips" class="class-chips-container mb-2"></div>

                            {{-- Search input --}}
                            <div class="class-search-wrapper mb-2">
                                <x-icon name="search" :size="16" />
                                <input type="text" id="class-search-input" class="form-control"
                                    placeholder="{{ __('search_classes') }}...">
                            </div>

                            {{-- Class list --}}
                            <div class="class-list-container" id="class-list">
                                @foreach ($classes as $class)
                                    <label class="class-list-item" data-class-id="{{ $class->id }}"
                                        data-class-name="{{ $class->name }}"
                                        data-medium-name="{{ $class->medium->name ?? '' }}">

                                        <input type="checkbox" class="class-checkbox" value="{{ $class->id }}">
                                        <span class="custom-check"></span>
                                        <span class="class-name">{{ $class->name }}</span>

                                        @if ($class->medium)
                                            <span class="class-medium">{{ $class->medium->name }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            {{ __('close') }}
                        </button>
                        <button type="button" class="btn btn-theme" id="btn-save-class-config">
                            {{ __('save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Create Semester Modal --}}
        <div class="modal fade" id="createSemesterModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header remove-bottom-padding">
                        <h5 class="modal-title">{{ __('create') . ' ' . __('semester') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="semester-create-form" action="{{ route('semester.store') }}" method="POST"
                        novalidate="novalidate">
                        <div class="modal-body remove-bottom-padding">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    <input name="name" type="text" placeholder="{{ __('name') }}"
                                        class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="start_date" class="datepicker-popup form-control"
                                        placeholder="{{ __('start_date') }}" autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="end_date" class="datepicker-popup form-control"
                                        placeholder="{{ __('end_date') }}" autocomplete="off" required>
                                </div>
                            </div>
                            <input type="hidden" name="session_year_id" id="create_session_year_id" value="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" id="semester-create-btn" type="submit"
                                value="{{ __('submit') }}">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit Semester Modal --}}
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('edit') . ' ' . __('semester') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="pt-3" id="semester-edit-form" action="{{ url('semester') }}"
                        novalidate="novalidate">
                        <input type="hidden" name="edit_id" id="edit_id" value="" />
                        <input type="hidden" name="edit_session_year_id" id="edit_session_year_id" value="" />
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    <input name="edit_name" id="edit_name" type="text"
                                        placeholder="{{ __('name') }}" class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="edit_start_date" id="edit_start_date"
                                        class="datepicker-popup form-control" placeholder="{{ __('start_date') }}"
                                        autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="edit_end_date" id="edit_end_date"
                                        class="datepicker-popup form-control" placeholder="{{ __('end_date') }}"
                                        autocomplete="off" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" type="submit" value="{{ __('edit') }} " />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function semesterQueryParams(params) {
            return {
                limit: params.limit,
                sort: params.sort,
                order: params.order,
                offset: params.offset,
                search: params.search,
                session_year_id: $('#semester_session_year').val(),
            };
        }

        function semesterStatusFormatter(value) {
            return value ?
                '<span class="badge badge-soft-success text-success px-3 py-2">{{ __('yes') }}</span>' :
                '<span class="badge badge-soft-danger text-danger px-3 py-2">{{ __('no') }}</span>';
        }

        $(document).ready(function() {
            const semesterShowUrl = "{{ url('semester/show') }}";
            const semesterStoreUrl = "{{ route('semester.store') }}";
            const semesterUpdateUrl = "{{ url('semester') }}";
            const classConfigUrl = "{{ url('semester/class-config') }}";

            let currentSessionYearId = $('#semester_session_year').val();
            let assignedClasses = [];
            let hasSemesters = false;

            // Load initial data
            applySessionYearDateLimits();
            loadAssignedClasses();
            // Initialize table with URL and trigger first load
            loadSemesters();

            // Session year change
            $('#semester_session_year').on('change', function() {
                currentSessionYearId = $(this).val();
                applySessionYearDateLimits();
                loadAssignedClasses();
                loadSemesters();
            });

            // ===================== ASSIGNED CLASSES =====================

            function loadAssignedClasses() {
                $.ajax({
                    url: classConfigUrl,
                    type: 'GET',
                    data: {
                        session_year_id: currentSessionYearId
                    },
                    success: function(response) {
                        assignedClasses = response.data || [];
                        renderAssignedClassesBadges();
                    },
                    error: function() {
                        $('#assigned-classes-badges').html(
                            '<span class="text-muted">{{ __('error_loading_classes') }}</span>');
                    }
                });
            }

            function renderAssignedClassesBadges() {
                const container = $('#assigned-classes-badges');
                container.empty();
                if (assignedClasses.length === 0) {
                    container.html('<span class="text-muted">{{ __('no_classes_configured') }}</span>');
                    return;
                }
                assignedClasses.forEach(function(cls) {
                    const mediumText = cls.medium_name ? ' (' + cls.medium_name + ')' : '';
                    container.append(
                        '<span class="badge badge-semester-class mr-2 mb-1">' +
                        cls.name + mediumText +
                        '</span>'
                    );
                });
            }

            // ===================== CONFIGURE CLASSES MODAL =====================

            $('#btn-configure-classes').on('click', function() {
                if (!hasSemesters) {
                    showWarningToast(@json(__('create_semester_before_configuring')));
                    return;
                }
                // Reset search
                $('#class-search-input').val('');
                $('.class-list-item').show();

                // Check currently assigned classes
                $('.class-checkbox').prop('checked', false);
                assignedClasses.forEach(function(cls) {
                    $('.class-checkbox[value="' + cls.id + '"]').prop('checked', true);
                });
                renderSelectedChips();
                $('#configureClassesModal').modal('show');
            });

            // Search filter
            $('#class-search-input').on('input', function() {
                const search = $(this).val().toLowerCase();
                $('.class-list-item').each(function() {
                    const name = $(this).data('class-name').toString().toLowerCase();
                    const medium = ($(this).data('medium-name') || '').toString().toLowerCase();
                    $(this).toggle(name.indexOf(search) > -1 || medium.indexOf(search) > -1);
                });
            });

            // Checkbox change
            $(document).on('change', '.class-checkbox', function() {
                renderSelectedChips();
            });

            function renderSelectedChips() {
                const container = $('#selected-classes-chips');
                container.empty();
                $('.class-checkbox:checked').each(function() {
                    const item = $(this).closest('.class-list-item');
                    const id = $(this).val();
                    const name = item.data('class-name');
                    const medium = item.data('medium-name') || '';
                    const label = name + (medium ? ' (' + medium + ')' : '');
                    container.append(
                        '<span class="chip-badge">' +
                        label +
                        ' <span class="chip-remove" data-id="' + id + '">&times;</span>' +
                        '</span>'
                    );
                });
            }

            // Remove chip
            $(document).on('click', '.chip-remove', function() {
                const id = $(this).data('id');
                $('.class-checkbox[value="' + id + '"]').prop('checked', false);
                renderSelectedChips();
            });

            // Save class config
            $('#btn-save-class-config').on('click', function() {
                const selectedIds = [];
                $('.class-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                const btn = $(this);
                btn.prop('disabled', true).text('{{ __('saving') }}...');

                $.ajax({
                    url: classConfigUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        session_year_id: currentSessionYearId,
                        class_ids: selectedIds
                    },
                    success: function(response) {
                        if (!response.error) {
                            $('#configureClassesModal').modal('hide');
                            loadAssignedClasses();
                            loadSemesters();
                            showSuccessToast(response.message);
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON?.message ||
                            @json(__('error_occurred')));
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('{{ __('save') }}');
                    }
                });
            });

            // ===================== SEMESTERS TABLE =====================

            function loadSemesters() {
                $('#create_session_year_id').val(currentSessionYearId);
                $('#edit_session_year_id').val(currentSessionYearId);

                const $table = $('#semester-table');
                if ($table.data('bootstrap.table')) {
                    // Already initialized — just refresh
                    $table.bootstrapTable('refresh', {
                        silent: true
                    });
                } else {
                    // First call — initialize with url
                    $table.bootstrapTable({
                        url: semesterShowUrl,
                    });
                }
            }

            $('#semester-table').on('load-success.bs.table', function(e, data) {
                hasSemesters = data?.total > 0;
                updateConfigureButtonState();
            });

            function updateConfigureButtonState() {
                const btn = $('#btn-configure-classes');
                const warning = $('#no-semester-config-warning');
                if (hasSemesters) {
                    btn.removeClass('btn-disabled-configure');
                    btn.prop('title', '');
                    warning.addClass('d-none');
                } else {
                    btn.addClass('btn-disabled-configure');
                    btn.prop('title', '{{ __('create_semester_before_configuring') }}');
                    warning.removeClass('d-none');
                }
            }

            // ===================== CREATE SEMESTER =====================

            $('#semester-create-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('#semester-create-btn');
                btn.prop('disabled', true);

                $.ajax({
                    url: semesterStoreUrl,
                    type: 'POST',
                    data: form.serialize() + '&_token={{ csrf_token() }}',
                    success: function(response) {
                        if (!response.error) {
                            $('#createSemesterModal').modal('hide');
                            form[0].reset();
                            loadSemesters();
                            showSuccessToast(response.message);
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON?.message ||
                            @json(__('error_occurred')));
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });

            // ===================== EDIT SEMESTER =====================

            $(document).on('click', '.btn-edit-semester', function() {
                const btn = $(this);
                $('#edit_id').val(btn.data('id'));
                $('#edit_name').val(btn.data('name'));
                // Flatpickr's setDate() silently drops any value outside the picker's
                // current min/max bounds. Some semesters legitimately extend beyond their
                // session-year range, so the session-year limits (already applied on page
                // load) would filter out the saved start/end and leave the field blank.
                // Clear the bounds, fill the saved values, then re-apply the limits — a
                // maxDate/minDate change only re-jumps the calendar and does NOT clear an
                // already-selected date, so the prefilled value stays visible while new
                // calendar picks remain constrained.
                setDatePickerBounds($('#edit_start_date'), null, null);
                setDatePickerBounds($('#edit_end_date'), null, null);
                setDatePickerValue($('#edit_start_date'), formatDate(btn.data('start')));
                setDatePickerValue($('#edit_end_date'), formatDate(btn.data('end')));
                applySessionYearDateLimits();
                $('#editModal').modal('show');
            });

            $('#semester-edit-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const editId = $('#edit_id').val();

                $.ajax({
                    url: semesterUpdateUrl + '/' + editId,
                    type: 'POST',
                    data: form.serialize() + '&_token={{ csrf_token() }}&_method=PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (!response.error) {
                            $('#editModal').modal('hide');
                            loadSemesters();
                            showSuccessToast(response.message);
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON?.message ||
                            @json(__('error_occurred')));
                    }
                });
            });

            // ===================== DELETE SEMESTER =====================

            $(document).on('click', '.btn-delete-semester', function() {
                const id = $(this).data('id');
                const related = $(this).data('related');
                let extraHtml = '';
                if (Array.isArray(related) && related.length > 0) {
                    extraHtml = `
                    <p class="text-danger mt-2">
                        Deleting this semester will also remove related data such as 
                        <strong>${related.join(', ')}</strong> if they exist. If you will delete all semesters then semester config with class will be reset.
                    </p>
                    `
                }
                Swal.fire({
                    title: "{{ __('delete_title') }}",
                    icon: 'warning',
                    html: `
                        <p>{{ __('you_wont_be_able_to_revert_this') }}</p>
                        ${extraHtml}
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: "{{ __('yes_delete') }}",
                    cancelButtonText: "{{ __('cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: semesterUpdateUrl + '/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (!response.error) {
                                    loadSemesters();
                                    loadAssignedClasses();
                                    showSuccessToast(response.message);
                                } else {
                                    showErrorToast(response.message);
                                }
                            },
                            error: function(xhr) {
                                showErrorToast(xhr.responseJSON?.message ||
                                    @json(__('error_occurred')));
                            }
                        });
                    }
                });
            });

            // ===================== HELPERS =====================

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                return day + '-' + month + '-' + d.getFullYear();
            }

            function parseIsoDate(dateStr) {
                if (!dateStr) return null;
                const parts = dateStr.split('-');
                if (parts.length !== 3) return null;
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                if (Number.isNaN(year) || Number.isNaN(month) || Number.isNaN(day)) return null;
                return new Date(year, month, day);
            }

            function applySessionYearDateLimits() {
                const selectedOption = $('#semester_session_year option:selected');
                const sessionStart = parseIsoDate(selectedOption.data('start-date'));
                const sessionEnd = parseIsoDate(selectedOption.data('end-date'));
                const fields = $(
                    'input[name="start_date"], input[name="end_date"], #edit_start_date, #edit_end_date');

                fields.each(function() {
                    setDatePickerBounds(this, sessionStart, sessionEnd);
                });
            }

            function showSuccessToast(msg) {
                $.toast({
                    text: escapeToastText(msg),
                    showHideTransition: 'slide',
                    icon: 'success',
                    position: 'top-right'
                });
            }

            function showErrorToast(msg) {
                $.toast({
                    text: escapeToastText(msg),
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right'
                });
            }

            function showWarningToast(msg) {
                $.toast({
                    text: escapeToastText(msg),
                    showHideTransition: 'slide',
                    icon: 'warning',
                    position: 'top-right',
                    loaderBg: '#f5a623'
                });
            }
        });
    </script>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('/assets/css/semester.css') }}">
@endsection
