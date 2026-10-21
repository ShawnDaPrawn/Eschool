{{-- Dashboard header --}}
<div class="dash-header mb-4">
    <p class="dash-header-date">{{ now()->translatedFormat('l, j F Y') }}</p>
    <h3 class="dash-header-title">{{ __('welcome') }}, {{ $admin_name }}</h3>
    <p class="dash-header-subtitle">{{ __('dashboard_subtitle') }}</p>
</div>
