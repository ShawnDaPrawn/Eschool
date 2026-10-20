{{-- Total Students: donut (center total) + Boys / Girls progress bars (server-rendered) --}}
<div class="dash-card dash-widget h-100">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('total_students') }}</h4>
    </div>
    <div class="dash-card-body">
        <div id="students-donut"
            data-total="{{ $total_students_chart['total'] }}"
            data-boys="{{ $total_students_chart['boys'] }}"
            data-girls="{{ $total_students_chart['girls'] }}"></div>

        <div class="students-ratio">
            <div class="ratio-row">
                <div class="ratio-head">
                    <span class="ratio-label">{{ __('boys') }}</span>
                    <span class="ratio-value" id="students-boys-count">{{ number_format((int) $total_students_chart['boys']) }}</span>
                </div>
                <div class="dash-progress">
                    <div class="dash-progress-bar dash-progress-bar--boys" id="students-boys-bar"></div>
                </div>
            </div>
            <div class="ratio-row">
                <div class="ratio-head">
                    <span class="ratio-label">{{ __('girls') }}</span>
                    <span class="ratio-value" id="students-girls-count">{{ number_format((int) $total_students_chart['girls']) }}</span>
                </div>
                <div class="dash-progress">
                    <div class="dash-progress-bar dash-progress-bar--girls" id="students-girls-bar"></div>
                </div>
            </div>
        </div>
    </div>
</div>
