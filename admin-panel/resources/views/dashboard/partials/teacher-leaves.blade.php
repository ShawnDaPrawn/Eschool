{{-- Leaves: pending student leave requests for the teacher's class-teacher classes --}}
<div class="m3-card m3-card--outlined dash-card dash-widget h-100" id="leaves-card">
    <div class="dash-card-head">
        <h2 class="dash-card-title">{{ __('leaves') }}</h2>
        <select class="dash-select" id="leaves-date" aria-label="{{ __('leave') }}">
            <option value="today">{{ __('today') }}</option>
            <option value="tomorrow">{{ __('tomorrow') }}</option>
            <option value="upcoming">{{ __('upcoming') }}</option>
        </select>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div class="leaves-list" id="leaves-list"></div>
            <div class="dash-empty" data-empty hidden>{{ __('no_leaves_found') }}</div>
        </div>
    </div>
</div>
