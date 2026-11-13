{{-- Staff (Admin / custom role) dashboard root. Each data widget is gated by its permission;
     Total Students, Upcoming Events and the Noticeboard are visible to every staff member.
     Config + endpoint URLs are exposed via data-* attributes so dashboard.js stays inline-free
     (the shared script only hydrates a widget when its card is actually rendered). --}}
<div class="content-wrapper m3 page-content dashboard-page" id="dashboard"
    data-session-year-id="{{ $session_year_id }}"
    data-currency="{{ $currency_symbol ?? '' }}"
    data-default-month="{{ $default_month }}"
    data-url-fees="{{ route('home.widget.fees') }}"
    data-url-attendance="{{ route('home.widget.attendance') }}"
    data-url-assessment="{{ route('home.widget.assessment') }}"
    data-url-leaves="{{ route('home.widget.leaves') }}"
    data-url-events="{{ route('home.widget.events') }}">

    @include('home.top')

    {{-- Summary stat strip — visible to every staff member --}}
    @include('dashboard.partials.stat-cards')

    <div class="panel-form-grid">
        @if($can_read_fee_receipts ?? false)
            <div class="panel-dashboard-cell">
                @include('dashboard.partials.fees-collection')
            </div>
        @endif

        @can('attendance-report')
            <div class="panel-dashboard-cell">
                @include('dashboard.partials.attendance')
            </div>
        @endcan

        {{-- Total Students — visible to every staff member --}}
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.total-students')
        </div>

        @can('view-exam-result')
            <div class="panel-dashboard-cell">
                @include('dashboard.partials.assessment')
            </div>
        @endcan

        @can('leave-list')
            <div class="panel-dashboard-cell">
                @include('dashboard.partials.leaves')
            </div>
        @endcan

        {{-- Upcoming Events & Noticeboard — visible to every staff member --}}
        <div class="panel-dashboard-cell">
            @include('dashboard.partials.events')
        </div>

        <div class="panel-dashboard-cell">
            @include('dashboard.partials.noticeboard')
        </div>
    </div>
</div>
