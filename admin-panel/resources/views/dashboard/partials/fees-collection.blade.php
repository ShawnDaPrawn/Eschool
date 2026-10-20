{{-- Fees Collection: donut + collected / upcoming dues / overdue --}}
<div class="dash-card dash-widget h-100" id="fees-card">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('fees_collection') }}</h4>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>

        <div class="fees-grid" data-content hidden>
            <div class="fees-chart">
                <div id="fees-donut"></div>
            </div>
            <div class="fees-legend">
                <div class="fees-legend-row">
                    <span class="dash-dot dot-collected"></span>
                    <div class="fees-legend-text">
                        <b class="fees-legend-label">{{ __('collected') }}</b>
                        <span class="fees-legend-value" id="fees-collected">&mdash;</span>
                    </div>
                </div>
                <div class="fees-legend-row">
                    <span class="dash-dot dot-dues"></span>
                    <div class="fees-legend-text">
                        <b class="fees-legend-label">{{ __('upcoming_dues') }}</b>
                        <span class="fees-legend-value" id="fees-dues">&mdash;</span>
                    </div>
                </div>
                <div class="fees-legend-row">
                    <span class="dash-dot dot-overdue"></span>
                    <div class="fees-legend-text">
                        <b class="fees-legend-label">{{ __('overdue_amount') }}</b>
                        <span class="fees-legend-value" id="fees-overdue">&mdash;</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
