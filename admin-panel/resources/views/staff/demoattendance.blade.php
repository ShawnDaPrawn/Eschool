@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/staff-attendance.css') }}?v={{ filemtime(public_path('assets/css/staff-attendance.css')) }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __('manage') . ' ' . __('attendance') }}</h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('staff') . ' ' . __('attendance') }}</h4>

                        {{-- Filters: view (daily/monthly) + date --}}
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-2">
                                <label class="filter-menu">{{ __('view') }}</label>
                                <select id="attendance_view" class="form-control">
                                    <option value="daily" selected>{{ __('daily') }}</option>
                                    <option value="monthly">{{ __('monthly') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-2">
                                <label class="filter-menu">{{ __('date') }}</label>
                                <input type="text" id="attendanceDate" class="form-control" autocomplete="off">
                            </div>
                        </div>

                        {{-- Daily: standard bootstrap-table list --}}
                        <div id="dailySection">
                            {{-- Hidden Lucide icon template, cloned into each row's action button by JS --}}
                            <span id="markIconTpl" class="d-none"><x-icon name="user-check" :size="16" /></span>

                            {{-- Bulk action bar — lives in the table toolbar; shown only when rows are selected --}}
                            <div id="toolbar">
                                <div class="attendance-actions" id="bulkActionBar">
                                    <button type="button" class="btn btn-inverse-success attendance-btn"
                                        onclick="bulkAttendance('present')">
                                        <x-icon name="check" :size="18" />
                                        <span>{{ __('present') }}</span>
                                    </button>

                                    <button type="button" class="btn btn-inverse-danger attendance-btn"
                                        onclick="bulkAttendance('absent')">
                                        <x-icon name="close" :size="18" />
                                        <span>{{ __('absent') }}</span>
                                    </button>

                                    <div class="attendance-dropdown">
                                        <button class="btn btn-inverse-primary attendance-btn" type="button" aria-haspopup="true"
                                            onclick="toggleHalfDayOptions(event)">
                                            <x-icon name="clock" :size="18" />
                                            <span>{{ __('half_day') }}</span>
                                        </button>
                                        <div id="halfDayBtn" class="halfday-options">
                                            <button type="button" class="halfday-option-item"
                                                onclick="bulkAttendance('first_half')">{{ __('first_half') }}</button>
                                            <button type="button" class="halfday-option-item"
                                                onclick="bulkAttendance('second_half')">{{ __('second_half') }}</button>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-outline-secondary attendance-btn"
                                        onclick="bulkAttendance('holiday')">
                                        <x-icon name="timetable" :size="18" />
                                        <span>{{ __('holiday') }}</span>
                                    </button>
                                </div>
                            </div>

                            <table aria-describedby="mydesc" class="table" id="table_list" data-toggle="table"
                                   data-url="{{ route('staff.attendance.list') }}" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                    data-show-columns="true" data-show-refresh="true" data-mobile-responsive="true"
                                    data-query-params="dailyQueryParams" data-escape="false">
                                <thead>
                                    <tr>
                                        <th scope="col" data-checkbox="true" data-formatter="checkboxStateFormatter"></th>
                                        <th scope="col" data-field="name" data-formatter="nameimageformater">
                                            {{ __('name') }}</th>
                                        <th scope="col" data-field="status_text" data-formatter="statusFormatter">
                                            {{ __('status') }}</th>
                                        <th scope="col" data-field="action" data-sortable="false"
                                            data-formatter="actionFormatter">{{ __('action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        {{-- Monthly: per-day attendance grid --}}
                        <div id="monthlyTableSection" style="display:none;">
                            <div id="monthlyLegend" class="sa-legend">
                                <span class="sa-legend-label">{{ __('attendance_summary') }}</span>
                                <span class="sa-legend-item"><span class="attendance-dot dot-present"></span>
                                    {{ __('present') }}</span>
                                <span class="sa-legend-item"><span class="attendance-dot dot-absent"></span>
                                    {{ __('absent') }}</span>
                                <span class="sa-legend-item"><span class="attendance-dot dot-half"></span>
                                    {{ __('half_day') }}</span>
                                <span class="sa-legend-item"><span class="attendance-dot dot-holiday"></span>
                                    {{ __('holiday') }}</span>
                                
                                <span class="sa-legend-item"><span class="attendance-dot dot-none"></span>
                                    {{ __('no_record') }}</span>
                            </div>

                            <div id="monthly_attendance_wrapper">
                                <p class="text-muted text-center py-4">{{ __('loading') }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance Modal --}}
    <div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="attendanceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('mark_attendance') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="staff_id">

                    {{-- Staff info --}}
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                        <div class="avatar-circle mr-3" id="modalAvatar">{{ __('st') }}</div>
                        <div>
                            <h5 class="mb-1 font-weight-bold" id="modalName">{{ __('staff_name') }}</h5>
                            <small class="text-muted">{{ __('employee') }}</small>
                        </div>
                    </div>

                    {{-- Status radio cards --}}
                    <div class="form-group">
                        <label class="font-weight-bold mb-3">{{ __('attendance_status') }}</label>

                        <label class="w-100 mb-2">
                            <input type="radio" class="status-radio" name="status" value="present" checked>
                            <div class="status-card">
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-soft-success status-badge mr-3">{{ __('present') }}</span>
                                    <span>{{ __('full_working_day') }}</span>
                                </div>
                                <x-icon name="check" :size="16" class="check-icon" />
                            </div>
                        </label>

                        <label class="w-100 mb-2">
                            <input type="radio" class="status-radio" name="status" value="absent">
                            <div class="status-card">
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-soft-danger status-badge mr-3">{{ __('absent') }}</span>
                                    <span>{{ __('not_present_today') }}</span>
                                </div>
                                <x-icon name="check" :size="16" class="check-icon" />
                            </div>
                        </label>

                        <label class="w-100 mb-2">
                            <input type="radio" class="status-radio" id="halfday" name="status" value="half_day">
                            <div class="status-card">
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-soft-primary status-badge mr-3">{{ __('half_day') }}</span>
                                    <span>{{ __('0.5_day_present') }}</span>
                                </div>
                                <x-icon name="check" :size="16" class="check-icon" />
                            </div>
                        </label>

                        <label class="w-100">
                            <input type="radio" class="status-radio" name="status" value="holiday">
                            <div class="status-card">
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-soft-secondary status-badge mr-3">{{ __('holiday') }}</span>
                                    <span>{{ __('official holiday') }}</span>
                                </div>
                                <x-icon name="check" :size="16" class="check-icon" />
                            </div>
                        </label>
                    </div>

                    {{-- Half-day sub-options --}}
                    <div id="halfDaySection" style="display:none;">
                        <label class="font-weight-bold mb-2">{{ __('which_half') }}</label>
                        <div class="d-flex">
                            <label class="flex-fill mr-2">
                                <input type="radio" class="half-day-radio" name="halfType" value="first">
                                <div class="half-day-option">{{ __('first_half') }}</div>
                            </label>
                            <label class="flex-fill">
                                <input type="radio" class="half-day-radio" name="halfType" value="second">
                                <div class="half-day-option">{{ __('second_half') }}</div>
                            </label>
                        </div>
                    </div>

                    {{-- Reason --}}
                    <div class="form-group mt-3">
                        <label>{{ __('reason') }} <small class="text-muted">{{ __('optional') }}</small></label>
                        <textarea id="reasonInput" class="form-control" rows="3" placeholder="{{ __('reason') }}"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                    <button type="button" class="btn btn-theme" id="saveAttendanceBtn"
                        onclick="saveAttendance()">{{ __('save_attendance') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    window.sessionYearId = @json(getSettings('session_year')['session_year'] ?? null);
    window.csrfToken = @json(csrf_token());
    window.SA_AVATAR = @json(asset('assets/avatar.png'));

    window.SA_ROUTES = {
        list:       @json(route('staff-attendance.list')),
        checkLeave: @json(route('staff-attendance.check-leave')),
        bulkStore:  @json(route('staff-attendance.bulk-store')),
        store:      @json(route('staff-attendance.store')),
    };

    window.SA_LANG = {
        failedToLoad:       @json(__('failed_load_attndance')),
        selectStaff:        @json(__('please_select_staff')),
        somethingWentWrong: @json(__('something_went_wrong')),
        selectHalfType:     @json(__('please_select_half_type')),
        saving:             @json(__('saving')),
        staffDetails:       @json(__('staff_details')),
        tryAgain:           @json(__('try_again')),
        noStaffFound:       @json(__('no_staff_found')),
        loading:            @json(__('loading')),
        markAttendance:     @json(__('mark_attendance')),
        onLeaveTitle:       @json(__('on_leave')),
        onLeaveSingular:    @json(__('is_on_leave_cannot_mark_present')),
        onLeavePlural:      @json(__('are_on_leave_cannot_mark_present')),
    };
</script>

<script src="{{ asset('assets/js/custom/staff-attendance.js') }}?v={{ filemtime(public_path('assets/js/custom/staff-attendance.js')) }}"></script>