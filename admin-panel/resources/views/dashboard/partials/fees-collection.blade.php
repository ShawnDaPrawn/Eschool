<div class="dash-card dash-widget h-100" id="fees-card" data-error-message="{{ __('Could not load current receipts. Open Fee payments to review them.') }}">
    <div class="dash-card-head"><h4 class="dash-card-title">{{ __('Verified fee receipts') }}</h4></div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div data-receipt-list role="status"></div>
            <p class="dash-empty" data-empty hidden>{{ __('No verified receipts in this school year yet.') }}</p>
        </div>
        <p>{{ __('Gross confirmed receipts received during the selected school year, including held credit. Refunds and current debt are shown in their own reports.') }}</p>
        <x-m3.button variant="outlined" :href="route('fee-payments.index')">{{ __('Fee payments') }}</x-m3.button>
        <x-m3.button variant="text" :href="route('fee-reporting.index')">{{ __('Arrears and accounting') }}</x-m3.button>
    </div>
</div>
