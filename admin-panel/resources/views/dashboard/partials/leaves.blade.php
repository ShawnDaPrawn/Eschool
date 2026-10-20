{{-- Approved Leaves: pending-leaves link + day filter + approved leave rows --}}
<div class="dash-card dash-widget h-100" id="leaves-card">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('approved_leaves') }}</h4>
        <div class="dash-head-actions">
            {{-- "View pending leaves" is only shown to users who can approve leaves (Super Admin
                 always; staff with the leave-approve permission). When absent, dashboard.js no-ops. --}}
            @if ($can_approve_leaves ?? true)
                <a href="{{ route('leave-request.index') }}" class="dash-link" id="leaves-pending-link" @if (($pending_leaves_count ?? 0) < 1) hidden @endif>
                    {{ __('view_pending_leaves') }} (<span id="leaves-pending-count">{{ $pending_leaves_count ?? 0 }}</span>)
                </a>
            @endif
            <select class="dash-select" id="leaves-date" aria-label="{{ __('leave') }}">
                <option value="today">{{ __('today') }}</option>
                <option value="tomorrow">{{ __('tomorrow') }}</option>
                <option value="upcoming">{{ __('upcoming') }}</option>
            </select>
        </div>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div class="leaves-list" id="leaves-list"></div>
            <div class="dash-empty" data-empty hidden>{{ __('no_leaves_found') }}</div>
        </div>
    </div>
</div>
