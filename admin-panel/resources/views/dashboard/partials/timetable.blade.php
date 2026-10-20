{{-- Today's Timetable: the teacher's own teaching schedule for today (server-rendered) --}}
<div class="dash-card dash-widget h-100" id="timetable-card">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('todays_timetable') }}</h4>
    </div>
    <div class="dash-card-body">
        <div class="timetable-list">
            @forelse ($timetable as $row)
                <div class="timetable-item">
                    <span class="timetable-time">{{ $row['time'] }}</span>
                    <span class="timetable-class">{{ $row['class_label'] }}@if (!empty($row['subject'])) - {{ $row['subject'] }}@endif</span>
                </div>
            @empty
                <div class="dash-empty">{{ __('no_data_found') }}</div>
            @endforelse
        </div>
    </div>
</div>
