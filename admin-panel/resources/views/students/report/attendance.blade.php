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

<section class="workspace-section" aria-labelledby="learner-attendance-calendar-title">
    <h2 id="learner-attendance-calendar-title">{{ __('attendance_calendar') }}</h2>
    <form method="GET" class="sr-month-select-form panel-form-grid">
        <input type="hidden" name="tab" value="attendance">
        <x-page.field name="month" id="month-selector" type="select" :label="__('select_month')" :options="collect($mounth)->pluck('name', 'value')->all()" :value="$calendarMonth" :restore-old-input="false" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Show month') }}</button>
    </form>
</section>

<div id="attendance-calendar">
    @include('students.report.calendar')
</div>
