{{-- 5 summary stat cards (server-rendered) --}}
@php
    $stat_cards = [
        ['label' => __('total_students'), 'value' => $counts['total_students'], 'icon' => 'fa fa-users'],
        ['label' => __('session_students'), 'value' => $counts['session_students'], 'icon' => 'fa fa-user-plus'],
        ['label' => __('total_teachers'), 'value' => $counts['total_teachers'], 'icon' => 'fa fa-graduation-cap'],
        ['label' => __('total_classes'), 'value' => $counts['total_classes'], 'icon' => 'fa fa-building'],
        ['label' => __('total_streams'), 'value' => $counts['total_streams'], 'icon' => 'fa fa-sitemap'],
    ];
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
