{{-- Assignments To Review: assignments for the teacher's subjects + submitted/total counts --}}
<div class="m3-card m3-card--outlined dash-card dash-widget h-100" id="assignments-card">
    <div class="dash-card-head">
        <h2 class="dash-card-title">{{ __('assignments_to_review') }}</h2>
        <select class="dash-select" id="assignments-class" aria-label="{{ __('select_class') }}">
            <option value="">{{ __('all') }}</option>
            @foreach ($assignment_options as $option)
                <option value="{{ $option['class_section_id'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="dash-card-body">
        <div class="widget-skeleton" data-skeleton></div>
        <div data-content hidden>
            <div class="assignments-list" id="assignments-list"></div>
            <div class="dash-empty" data-empty hidden>{{ __('no_data_found') }}</div>
        </div>
    </div>
</div>
