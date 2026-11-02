@extends('layouts.master')
@section('title', __('Intervention library'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/progress-notes.css') }}">@endsection
@section('content')
@php
    $areaNames = ['attendance' => __('Attendance'), 'learning' => __('Learning'), 'engagement' => __('Engagement'), 'behaviour' => __('Behaviour'), 'relationships' => __('Friendships and belonging'), 'wellbeing' => __('Wellbeing')];
@endphp
<div class="content-wrapper progress-notes">
    <h1>{{ __('Intervention library') }}</h1>
    <p>{{ __('Reusable school actions to start an intervention from: the steps, a suggested length and how to see progress. They are guidance for staff, not a diagnosis or a label.') }}</p>
    @if ($errors->any())<div role="alert" class="m3 m3-banner m3-banner--warning"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if (session('success'))<p role="status" class="m3 m3-banner m3-banner--success">{{ session('success') }}</p>@endif
    <nav class="note-tabs" aria-label="{{ __('Intervention workbench') }}"><a href="{{ route('risk-interventions.index') }}">{{ __('Review actions') }}</a><a href="{{ route('risk-interventions.library') }}" aria-current="page">{{ __('Intervention library') }}</a></nav>

    @if ($can_manage && $adoptable !== [])
        <section class="note-card"><h2>{{ __('Suggested actions') }}</h2>
            <p>{{ __('NavigateEd suggests these everyday school actions. Add them to your library and change them to suit your school.') }}</p>
            <ul>@foreach ($adoptable as $key)<li>{{ $suggested[$key]['name'] }} · {{ $areaNames[$suggested[$key]['concern_area']] }}</li>@endforeach</ul>
            <form method="post" action="{{ route('risk-interventions.library.adopt') }}">@csrf
                <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                <button type="submit" class="m3-button m3-button--filled" @disabled(! $canWrite)>{{ __('Add the suggested actions') }}</button>
            </form>
        </section>
    @endif

    <section class="note-card"><h2>{{ __('Library actions') }}</h2>
        @forelse ($actions as $action)
            <article class="note-list-item">
                <h3>{{ $action['name'] }}@if ($action['archived']) · {{ __('Archived') }}@endif</h3>
                <p>{{ $areaNames[$action['concern_area']] ?? $action['concern_area'] }} · {{ trans_choice(':count week|:count weeks', $action['suggested_weeks'], ['count' => $action['suggested_weeks']]) }}</p>
                <ol>@foreach ($action['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                <p><strong>{{ __('Measure of progress') }}</strong>: {{ $action['progress_measure'] }}</p>
                @if ($can_manage)
                    <details class="note-history"><summary>{{ __('Change this action') }}</summary>
                        <form method="post" action="{{ route('risk-interventions.library.update', ['action' => $action['id']]) }}">@csrf
                            <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                            <input type="hidden" name="expected_revision" value="{{ $action['revision'] }}">
                            @include('growth.intervention-library-fields', ['prefix' => 'library-'.$action['id'], 'values' => $action])
                            <button type="submit" class="m3-button m3-button--filled" @disabled(! $canWrite)>{{ __('Save library action') }}</button>
                        </form>
                    </details>
                    <form method="post" action="{{ route('risk-interventions.library.archive', ['action' => $action['id']]) }}">@csrf
                        <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                        <input type="hidden" name="expected_revision" value="{{ $action['revision'] }}"><input type="hidden" name="archived" value="{{ $action['archived'] ? 0 : 1 }}">
                        <button type="submit" class="m3-button m3-button--outlined" @disabled(! $canWrite)>{{ $action['archived'] ? __('Offer this action again') : __('Archive this action') }}</button>
                    </form>
                @endif
            </article>
        @empty
            <p>{{ __('The library is empty. A principal can add the suggested actions or write the school\'s own.') }}</p>
        @endforelse
    </section>

    @if ($can_manage)
        <section class="note-card"><h2>{{ __('Add a library action') }}</h2>
            <form method="post" action="{{ route('risk-interventions.library.store') }}">@csrf
                <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                @include('growth.intervention-library-fields', ['prefix' => 'library-new', 'values' => null])
                <button type="submit" class="m3-button m3-button--filled" @disabled(! $canWrite)>{{ __('Add library action') }}</button>
            </form>
        </section>
    @endif
</div>
@endsection
