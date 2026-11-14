{{-- Super Admin dashboard root. All config + endpoint URLs are exposed via data-* attributes
     so the external dashboard.js stays free of inline blade. --}}
<div class="content-wrapper m3 page-content dashboard-page" id="dashboard"
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

    <div class="panel-form-grid">
        @if($can_read_fee_receipts ?? false)
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.fees-collection')
        </div>
        @endif
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.attendance')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.total-students')
        </div>
    </div>

    <div class="panel-form-grid">
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.assessment')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.leaves')
        </div>
    </div>

    <div class="panel-form-grid">
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.events')
        </div>
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.noticeboard')
        </div>
    </div>
</div>
