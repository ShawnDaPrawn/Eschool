{{-- Noticeboard: latest notices (server-rendered) --}}
<div class="dash-card dash-widget h-100" id="noticeboard-card">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('noticeboard') }}</h4>
        <a href="{{ route('announcement.index') }}" class="dash-link">{{ __('view_all_notices') }}</a>
    </div>
    <div class="dash-card-body">
        <div class="notices-list">
            @forelse ($notices as $notice)
                <div class="notice-item">
                    <div class="notice-thumb">
                        @if (!empty($notice['thumb']))
                            <img src="{{ $notice['thumb'] }}" alt="" onerror="onErrorImage(event)">
                        @else
                            <span class="notice-thumb-placeholder"><i class="fa fa-bullhorn"></i></span>
                        @endif
                    </div>
                    <div class="notice-body">
                        <div class="notice-head">
                            <h6 class="notice-title">{{ $notice['title'] }}</h6>
                            @if (!empty($notice['class_label']))
                                <span class="dash-chip">{{ $notice['class_label'] }}</span>
                            @endif
                        </div>
                        <span class="notice-date">{{ \Carbon\Carbon::parse($notice['date'])->format('d/M/Y') }}</span>
                        <p class="notice-desc">{{ $notice['description'] }}</p>
                    </div>
                </div>
            @empty
                <div class="dash-empty">{{ __('no_notices_found') }}</div>
            @endforelse
        </div>
    </div>
</div>
