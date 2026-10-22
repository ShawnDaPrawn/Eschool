{{-- Super Admin dashboard root. All config + endpoint URLs are exposed via data-* attributes
     so the external dashboard.js stays free of inline blade. --}}
<div class="content-wrapper dashboard-page" id="dashboard"
    data-session-year-id="{{ $session_year_id }}"
    data-currency="{{ $currency_symbol }}"
    data-default-month="{{ $default_month }}"
    data-url-fees="{{ route('home.widget.fees') }}"
    data-url-attendance="{{ route('home.widget.attendance') }}"
    data-url-assessment="{{ route('home.widget.assessment') }}"
    data-url-leaves="{{ route('home.widget.leaves') }}"
    data-url-events="{{ route('home.widget.events') }}">

    @include('home.top')

    @include('dashboard.partials.stat-cards')

    <div class="row">
        <div class="col-12 col-xl-5 mb-4">
            @include('dashboard.partials.fees-collection')
        </div>
        <div class="col-12 col-lg-7 col-xl-4 mb-4">
            @include('dashboard.partials.attendance')
        </div>
        <div class="col-12 col-lg-5 col-xl-3 mb-4">
            @include('dashboard.partials.total-students')
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-7 mb-4">
            @include('dashboard.partials.assessment')
        </div>
        <div class="col-12 col-lg-5 mb-4">
            @include('dashboard.partials.leaves')
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-5 mb-4">
            @include('dashboard.partials.events')
        </div>
        <div class="col-12 col-lg-7 mb-4">
            @include('dashboard.partials.noticeboard')
        </div>
    </div>
</div>
