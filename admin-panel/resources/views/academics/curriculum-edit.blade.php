@extends('layouts.master')
@section('title', __('Curriculum plans'))
@section('content')
<div class="content-wrapper m3 page-content" data-curriculum-editor>
    <x-page.header :title="$plan?->title ?? __('New curriculum draft')" :subtitle="__('Draft future terms at your own pace. Publication checks only the selected term.')" />
    <a href="{{ route('curriculum.index') }}">{{ __('All curriculum plans') }}</a>
    @if ($errors->any())<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if ($plan)<p>{{ __('Version') }} {{ $plan->version }} · {{ __($plan->state) }}</p>@endif
    @if ($plan?->standard_snapshot)<p>{{ $plan->standard_snapshot['framework'] }} · {{ $plan->standard_snapshot['code'] }} · {{ $plan->standard_snapshot['version'] }}</p>@endif
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
    @if ($plan)
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
        @can('curriculum-manage')
            <form method="POST" action="{{ route('curriculum.copy', $plan->id) }}" class="page-section">@csrf
                <x-page.field name="session_year_id" type="select" :label="__('Copy definitions to school year')" :value="$plan->session_year_id" :options="$years->pluck('name', 'id')->all()" required />
                <p>{{ __('The copy is a new draft. Learner marks and released reports are never copied.') }}</p>
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Copy to draft') }}</button>
            </form>
        @endcan
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
