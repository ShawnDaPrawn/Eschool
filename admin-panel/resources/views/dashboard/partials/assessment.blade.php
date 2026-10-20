{{-- Recent Assessment Performance: class+subject selector + top student percentages --}}
<div class="dash-card dash-widget h-100" id="assessment-card">
    <div class="dash-card-head">
        <h4 class="dash-card-title">{{ __('recent_assessment_performance') }}</h4>
        <select class="dash-select" id="assessment-select" aria-label="{{ __('select_class') }}">
            @forelse ($assessment_options as $option)
                <option value="{{ $option['class_section_id'] }}">{{ $option['label'] }}</option>
            @empty
                <option value="">{{ __('no_data_found') }}</option>
            @endforelse
        </select>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div class="assessment-list" id="assessment-list"></div>
            <div class="dash-empty" data-empty hidden>{{ __('no_data_found') }}</div>
            <p class="dash-footnote">{{ __('based_on_recent_offline_examination_results') }}</p>
        </div>
    </div>
</div>
