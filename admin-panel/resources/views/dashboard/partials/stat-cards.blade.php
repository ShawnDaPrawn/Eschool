{{-- 5 summary stat cards (server-rendered) --}}
@php
    $stat_cards = [
        ['label' => __('total_students'), 'value' => $counts['total_students'], 'icon' => 'fa fa-users'],
        ['label' => __('session_students'), 'value' => $counts['session_students'], 'icon' => 'fa fa-user-plus'],
        ['label' => __('total_teachers'), 'value' => $counts['total_teachers'], 'icon' => 'fa fa-graduation-cap'],
        ['label' => __('total_classes'), 'value' => $counts['total_classes'], 'icon' => 'fa fa-building'],
        ['label' => __('total_streams'), 'value' => $counts['total_streams'], 'icon' => 'fa fa-sitemap'],
    ];
    // Leavers count apart from current learners while any are reading (Action Plan 1.21).
    if (($counts['leavers'] ?? 0) > 0) {
        array_splice($stat_cards, 2, 0, [['label' => __('Leavers (read-only)'), 'value' => $counts['leavers'], 'icon' => 'fa fa-sign-out']]);
    }
@endphp
<div class="dash-stat-row mb-4">
    @foreach ($stat_cards as $card)
        <div class="dash-card dash-stat-card">
            <span class="dash-stat-icon"><i class="{{ $card['icon'] }}"></i></span>
            <div class="dash-stat-text">
                <span class="dash-stat-label">{{ $card['label'] }}</span>
                <span class="dash-stat-value">{{ number_format((int) $card['value']) }}</span>
            </div>
        </div>
    @endforeach
</div>
