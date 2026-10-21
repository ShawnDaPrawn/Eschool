{{-- Teacher dashboard root. Config + endpoint URLs via data-* so dashboard.js stays inline-free. --}}
<div class="content-wrapper dashboard-page" id="dashboard"
    data-dashboard="teacher"
    data-session-year-id="{{ $session_year_id }}"
    data-default-month="{{ $default_month }}"
    data-url-attendance="{{ route('home.widget.attendance') }}"
    data-url-leaves="{{ route('home.widget.teacher-leaves') }}"
    data-url-exams="{{ route('home.widget.teacher-exams') }}"
    data-url-assignments="{{ route('home.widget.teacher-assignments') }}">

    @include('home.top')

    @include('dashboard.partials.teacher-stat-cards')

    <div class="row">
        <div class="col-12 col-lg-4 mb-4">
            @include('dashboard.partials.attendance')
        </div>
        <div class="col-12 col-lg-4 mb-4">
            @include('dashboard.partials.timetable')
        </div>
        <div class="col-12 col-lg-4 mb-4">
            @include('dashboard.partials.teacher-leaves')
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-4 mb-4">
            @include('dashboard.partials.exams')
        </div>
        <div class="col-12 col-lg-4 mb-4">
            @include('dashboard.partials.assignments')
        </div>
        <div class="col-12 col-lg-4 mb-4">
            @include('dashboard.partials.noticeboard')
        </div>
    </div>
</div>
