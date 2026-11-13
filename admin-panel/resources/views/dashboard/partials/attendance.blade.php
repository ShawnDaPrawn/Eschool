{{-- Attendance: class selector + present/absent bar chart per section --}}
<div class="m3-card m3-card--outlined dash-card dash-widget h-100" id="attendance-card">
    <div class="dash-card-head">
        <h2 class="dash-card-title">{{ __('attendance') }}</h2>
        <select class="dash-select" id="attendance-class" aria-label="{{ __('select_class') }}">
            @forelse ($classes as $class)
                <option value="{{ $class->id }}">{{ $class->full_name }}</option>
            @empty
                <option value="">{{ __('no_data_found') }}</option>
            @endforelse
        </select>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div id="attendance-chart"></div>
            <div class="dash-empty" data-empty hidden>{{ __('no_data_found') }}</div>
        </div>
    </div>
</div>
