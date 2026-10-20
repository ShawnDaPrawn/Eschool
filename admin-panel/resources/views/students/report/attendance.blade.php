<div class="report-stat-strip">

    <div class="report-stat-item accent-secondary">
        <div class="report-stat-label">{{ __('Total_Days') }}</div>
        <div class="report-stat-value">{{ $workingdays + $holidayscount }}</div>
    </div>

    <div class="report-stat-item accent-success">
        <div class="report-stat-label">{{ __('present') }}</div>
        <div class="report-stat-value">{{ $presentday }}</div>
    </div>

    <div class="report-stat-item accent-danger">
        <div class="report-stat-label">{{ __('absent') }}</div>
        <div class="report-stat-value">{{ $absentCount }}</div>
    </div>

    <div class="report-stat-item accent-info">
        <div class="report-stat-label">{{ __('holiday') }}</div>
        <div class="report-stat-value">{{ $holidayscount }}</div>
    </div>

    <div class="report-stat-item accent-warning">
        <div class="report-stat-label">{{ __('first_half_absent') }}</div>
        <div class="report-stat-value">{{ $firstHalfAbsentCount }}</div>
    </div>

    <div class="report-stat-item accent-warning">
        <div class="report-stat-label">{{ __('second_half_absent') }}</div>
        <div class="report-stat-value">{{ $secondHalfAbsentCount }}</div>
    </div>

    <div class="report-stat-item accent-theme">
        <div class="report-stat-label">{{ __('attendance_percentage') }}</div>
        <div class="report-stat-value">
            {{ $workingdays > 0 ? round(($presentday / $workingdays) * 100, 1) : 0 }}%
        </div>
    </div>

</div>

<div class="row mb-3 align-items-end">

    <div class="col-md-6">
        <h4 class="mb-0">{{ __('attendance_calendar') }}</h4>
    </div>

    <div class="col-md-4 col-lg-3 ml-auto d-flex justify-content-end">
        <form method="GET" class="sr-month-select-form">
            <input type="hidden" name="tab" value="attendance">

            <label class="d-block text-right small mb-1" for="month-selector">{{ __('select_month') }}</label>

            <select id="month-selector" name="month" class="form-control">
                @foreach($mounth as $month)
                    <option value="{{ $month['value'] }}"
                        {{ $calendarMonth == $month['value'] ? 'selected' : '' }}>
                        {{ $month['name'] }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div id="attendance-calendar">
    @include('students.report.calendar')
</div>

<script>
$(document).ready(function() {
    $('#month-selector').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
