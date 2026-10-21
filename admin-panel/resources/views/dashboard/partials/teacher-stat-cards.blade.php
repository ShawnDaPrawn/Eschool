{{-- 3 summary stat cards for the teacher (server-rendered) --}}
@php
    $teacher_stat_cards = [
        ['label' => __('total_students'), 'value' => $counts['total_students'], 'icon' => 'fa fa-users'],
        ['label' => __('total_classes'), 'value' => $counts['total_classes'], 'icon' => 'fa fa-building'],
        ['label' => __('total_assigned_subjects'), 'value' => $counts['total_subjects'], 'icon' => 'fa fa-book'],
    ];
@endphp
<div class="dash-stat-row dash-stat-row--3 mb-4">
    @foreach ($teacher_stat_cards as $card)
        <div class="dash-card dash-stat-card">
            <span class="dash-stat-icon"><i class="{{ $card['icon'] }}"></i></span>
            <div class="dash-stat-text">
                <span class="dash-stat-label">{{ $card['label'] }}</span>
                <span class="dash-stat-value">{{ number_format((int) $card['value']) }}</span>
            </div>
        </div>
    @endforeach
</div>
