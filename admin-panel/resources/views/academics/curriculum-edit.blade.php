@extends('layouts.master')
@section('title', __('Curriculum plans'))
@section('content')
<div class="content-wrapper m3 page-content" data-curriculum-editor>
    <x-page.header :title="$plan?->title ?? __('New curriculum draft')" :subtitle="__('Draft future terms at your own pace. Publication checks only the selected term.')" />
    <a href="{{ route('curriculum.index') }}">{{ __('All curriculum plans') }}</a>
    @php
        $curriculumView = $plan && in_array(request('view'), ['publication', 'copy'], true) ? request('view') : 'definition';
    @endphp
    @php
        if ($curriculumView === 'publication' && !auth()->user()->can('curriculum-publish')) $curriculumView = 'definition';
        if ($curriculumView === 'copy' && !auth()->user()->can('curriculum-manage')) $curriculumView = 'definition';
    @endphp
    <nav class="workspace-tabs" aria-label="{{ __('Curriculum record') }}">
        <a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['view' => 'definition']) }}" @if($curriculumView === 'definition') aria-current="page" @endif>{{ __('Definition') }}</a>
        @if($plan)
        @can('curriculum-publish')<a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['view' => 'publication']) }}" @if($curriculumView === 'publication') aria-current="page" @endif>{{ __('Publication') }}</a>@endcan
        @can('curriculum-manage')<a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['view' => 'copy']) }}" @if($curriculumView === 'copy') aria-current="page" @endif>{{ __('Copy to a new draft') }}</a>@endcan
        @endif
    </nav>
    @if($plan?->state === 'published')<div class="m3-banner m3-banner--info" role="status">{{ __('Published definitions are locked. Publish a remaining draft term separately, or copy this plan to make changes.') }}</div>@endif
    @if ($errors->any())<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if ($plan)<p>{{ __('Version') }} {{ $plan->version }} · {{ __($plan->state) }}</p>@endif
    @if ($plan?->standard_snapshot)<p>{{ $plan->standard_snapshot['framework'] }} · {{ $plan->standard_snapshot['code'] }} · {{ $plan->standard_snapshot['version'] }}</p>@endif
    @if($curriculumView === 'definition')
    <form method="POST" action="{{ $plan ? route('curriculum.update', $plan->id) : route('curriculum.store') }}" data-definition-form>
        @csrf @if ($plan) @method('PUT') @endif
        <input type="hidden" name="definition" data-definition-input>
        <fieldset @disabled($plan?->state === 'published' || !auth()->user()->can('curriculum-manage'))>
            <legend>{{ __('Curriculum definition') }}</legend>
            <div data-definition-fields></div>
            <button type="button" class="m3-button m3-button--outlined" data-add-term>{{ __('Add a term') }}</button>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save draft') }}</button>
        </fieldset>
        <noscript><p>{{ __('Enable JavaScript to edit curriculum plans.') }}</p></noscript>
    </form>
    @endif
    @if ($plan)
        @if($curriculumView === 'publication')
        @can('curriculum-publish')
            @foreach ($plan->assessments as $assessment)
                @if ($assessment->state === 'draft')
                    <form method="POST" action="{{ route('curriculum.publish', [$plan->id, $assessment->id]) }}" class="page-section">@csrf
                        <input type="hidden" name="revision" value="{{ $plan->revision }}">
                        <button class="m3-button m3-button--filled" type="submit">{{ __('Publish term') }}: {{ $assessment->term->name }}</button>
                    </form>
                @else<p>{{ $assessment->term->name }} · {{ __('Published') }}</p>@endif
            @endforeach
        @endcan
        @endif
        @if($curriculumView === 'copy')
        @can('curriculum-manage')
            <form method="POST" action="{{ route('curriculum.copy', $plan->id) }}" class="page-section">@csrf
                <x-page.field name="session_year_id" type="select" :label="__('Copy definitions to school year')" :value="$plan->session_year_id" :options="$years->pluck('name', 'id')->all()" required />
                <p>{{ __('The copy is a new draft. Learner marks and released reports are never copied.') }}</p>
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Copy to draft') }}</button>
            </form>
        @endcan
        @endif
    @endif
    @php
        $oldDefinition = old('definition') ? json_decode(old('definition'), true) : null;
        $editorState = ['definition' => is_array($oldDefinition) && !array_is_list($oldDefinition) ? $oldDefinition : $definition, 'subjects' => $subjects, 'grades' => $grades, 'standards' => $standards, 'years' => $years, 'terms' => $terms];
    @endphp
    <script type="application/json" data-curriculum-state>@json($editorState)</script>
</div>
@endsection
@section('script')
<script src="{{ asset('assets/js/custom/curriculum.js') }}" defer></script>
@endsection
