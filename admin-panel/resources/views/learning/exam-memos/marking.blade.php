@extends('layouts.master')
@section('title', __('Try marking an answer'))
@section('content')
@php
    $decimal = fn (int $units) => \App\Services\Academics\AcademicNumbers::decimal($units);
    $markable = array_filter($questions, fn ($q) => $q['markable']);
    $options = [];
    foreach ($markable as $question) {
        $options[$question['number']] = __('Question :number (:total marks)', ['number' => $question['number'], 'total' => $decimal($question['max_units'])]);
    }
    $statuses = ['awarded' => __('Awarded'), 'not_awarded' => __('Not awarded'), 'needs_teacher' => __('Needs a teacher')];
    $deciders = ['rule' => __('Memo rule'), 'follow_through' => __('Follow-through'), 'teacher' => __('Teacher')];
    $ruleKinds += ['judgement' => __('Judgement')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Try marking an answer')" :subtitle="$paper['title'].' · '.$paper['paper_year'].' '.$paper['paper_code']" />
    <a href="{{ route('exam-memos.show', ['group' => $group_id, 'version' => $paper['version_id']]) }}">{{ __('Back to the memo') }}</a>
    @if ($errors->any())<div role="alert" class="m3-alert">@foreach ($errors->all() as $problem)<p>{{ $problem }}</p>@endforeach</div>@endif

    <section class="page-section" aria-labelledby="marking-about">
        <h2 id="marking-about">{{ __('How this answer is marked') }}</h2>
        <p>{{ __('A preview for teachers: type an answer as a learner would, one step per line, and see how the memo\'s mark points decide each mark. Nothing is saved, and learners do not see it.') }}</p>
        <p role="status">
            @if ($memo_state === 'reviewed')
                {{ __('This memo is reviewed.') }}
            @else
                {{ __('This memo is not reviewed yet. Learners never get marking from it until a teacher reviews it.') }}
            @endif
            {{ $profile['learner_marking']
                ? __('Learner marking is on for :subject.', ['subject' => $profile['label']])
                : __('Learner marking is off for :subject until it passes the accuracy check against teacher marking.', ['subject' => $profile['label']]) }}
        </p>
        <h3>{{ __('Conventions for :subject', ['subject' => $profile['label']]) }}</h3>
        <ul>@foreach ($profile['conventions'] as $convention)<li>{{ $convention }}</li>@endforeach</ul>
        <p>{{ __('Method and explanation marks that no rule decides always go to a teacher. Automatic judgement is switched off.') }}</p>
    </section>

    @if ($options === [])
        <x-page.empty-state :title="__('No question is ready to mark')" :message="__('Give a question mark points that add up to its total, then come back to try an answer.')" />
    @else
        <form method="POST" action="{{ route('exam-memos.marking.preview', ['group' => $group_id, 'version' => $paper['version_id']]) }}" aria-label="{{ __('Answer to mark') }}" class="page-section">
            @csrf
            <x-page.field id="marking-question" name="question" type="select" :label="__('Question')" :options="$options" :value="$chosen ?? array_key_first($options)" required />
            <x-page.field id="marking-answer" name="answer" type="textarea" :label="__('Answer, one step per line')" :value="$answer" rows="8" maxlength="4000" dir="auto"
                :help="__('Write numbers with a comma or a dot for decimals, units after the value (12,5 m/s) and powers with ^ (x^2, s^-1).')" />
            <button type="submit" class="m3-button m3-button--filled">{{ __('Mark the answer') }}</button>
        </form>
    @endif

    @if ($result !== null)
        <section class="page-section" aria-labelledby="marking-result">
            <h2 id="marking-result">{{ __('Question :number', ['number' => $result['question']]) }}</h2>
            <p role="status">
                {{ __('Awarded :awarded of :total marks.', ['awarded' => $decimal($result['awarded_units']), 'total' => $decimal($result['max_units'])]) }}
                @if ($result['pending_units'] > 0) {{ __(':marks marks need a teacher.', ['marks' => $decimal($result['pending_units'])]) }} @endif
                @if ($result['confidence'] !== null) {{ __('Lowest confidence of the decided marks: :confidence.', ['confidence' => number_format($result['confidence'], 2)]) }} @endif
            </p>
            <div class="page-table"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Mark points and how each was decided') }}</caption>
                <thead><tr>
                    <th scope="col">{{ __('Mark point') }}</th><th scope="col">{{ __('Type of mark') }}</th><th scope="col">{{ __('Marks') }}</th>
                    <th scope="col">{{ __('Result') }}</th><th scope="col">{{ __('Rule') }}</th><th scope="col">{{ __('Decided by') }}</th>
                    <th scope="col">{{ __('Reason') }}</th><th scope="col">{{ __('Confidence') }}</th>
                </tr></thead>
                <tbody>
                    @foreach ($result['points'] as $point)
                        <tr>
                            <td dir="auto">{{ $point['index'] }}. {{ $point['earns'] }}</td>
                            <td>{{ $types[$point['type']] ?? $point['type'] }}</td>
                            <td>{{ $decimal($point['awarded_units']) }} / {{ $decimal($point['mark_units']) }}</td>
                            <td>{{ $statuses[$point['status']] ?? $point['status'] }}</td>
                            <td>{{ $ruleKinds[$point['rule']] ?? $point['rule'] }}</td>
                            <td>{{ $deciders[$point['decided_by']] ?? $point['decided_by'] }}</td>
                            <td>{{ $point['reason'] }}@if ($point['line'] !== null) {{ __('(line :line)', ['line' => $point['line']]) }}@endif</td>
                            <td>{{ number_format($point['confidence'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif
</div>
@endsection
