{{-- Upcoming Exams: exams in the selected month for the teacher's class-teacher classes --}}
<div class="m3-card m3-card--outlined dash-card dash-widget h-100" id="exams-card">
    <div class="dash-card-head">
        <h2 class="dash-card-title">{{ __('upcoming_exams') }}</h2>
        <select class="dash-select" id="exams-month" aria-label="{{ __('upcoming_exams') }}">
            @foreach ($months as $month)
                <option value="{{ $month['value'] }}" @selected($month['value'] === $default_month)>{{ $month['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div class="exams-list" id="exams-list"></div>
        </div>
    </div>
</div>
