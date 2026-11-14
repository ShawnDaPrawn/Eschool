{{-- Teacher dashboard root. Config + endpoint URLs via data-* so dashboard.js stays inline-free. --}}
<div class="content-wrapper m3 page-content dashboard-page" id="dashboard"
    data-dashboard="teacher"
    data-session-year-id="{{ $session_year_id }}"
    data-default-month="{{ $default_month }}"
    data-url-attendance="{{ route('home.widget.attendance') }}"
    data-url-leaves="{{ route('home.widget.teacher-leaves') }}"
    data-url-exams="{{ route('home.widget.teacher-exams') }}"
    data-url-assignments="{{ route('home.widget.teacher-assignments') }}">

    @include('home.top')

    @include('dashboard.partials.teacher-stat-cards')

    <div class="panel-form-grid">
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.attendance')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.timetable')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.teacher-leaves')
        </div>
    </div>

    <div class="panel-form-grid">
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.exams')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.assignments')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.noticeboard')
        </div>
    </div>
</div>
