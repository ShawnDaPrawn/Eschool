{{-- Upcoming Events: month selector + event list + month calendar grid --}}
<div class="dash-card dash-widget h-100" id="events-card">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('upcoming_events') }}</h4>
        <select class="dash-select" id="events-month" aria-label="{{ __('events') }}">
            @foreach ($months as $month)
                <option value="{{ $month['value'] }}" @selected($month['value'] === $default_month)>{{ $month['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div class="events-grid" data-content hidden>
            <div class="events-list" id="events-list" tabindex="0" role="region" aria-label="{{ __('upcoming_events') }}"></div>
            <div class="events-calendar" id="events-calendar"></div>
        </div>
    </div>
</div>
