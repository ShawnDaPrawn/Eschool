@extends('layouts.master')
@section('title', __('Practice test results'))
@section('content')
@php
    $stateLabels = ['not_started' => __('Not started'), 'open' => __('Sitting it now'), 'time_up' => __('Time is up, not handed in'), 'handed_in' => __('Handed in')];
    $local = fn (?string $instant) => $instant === null ? '' : \Carbon\CarbonImmutable::parse($instant)->setTimezone($zone)->format('Y-m-d H:i');
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$results['title']" :subtitle="__('Learners are listed by name. Right answers count only questions they answered; anything not answered is shown as not answered, not as wrong.')" />
    <p><a href="{{ route('practice-tests.index', ['group' => $results['group_id']]) }}">{{ __('Back to practice tests') }}</a></p>
    <p>{{ trans_choice(':count question in :minutes minutes|:count questions in :minutes minutes', $results['question_count'], ['count' => $results['question_count'], 'minutes' => $results['time_limit_minutes']]) }} · {{ $local($results['opens_at']) }} – {{ $local($results['closes_at']) }}@unless ($results['active']) · {{ __('Withdrawn') }}@endunless</p>

    <section class="page-section" aria-labelledby="learner-results">
        <h2 id="learner-results">{{ __('Learners') }}</h2>
        @if ($results['learners'] === [])
            <x-page.empty-state :title="__('No learners in this group today')" :message="__('Learners appear here while they are members of the group.')" />
        @else
            <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Learners') }}" tabindex="0"><table class="page-table__table">
                <caption>{{ __('Learners') }}</caption>
                <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Where they are') }}</th><th scope="col">{{ __('Right answers') }}</th></tr></thead>
                <tbody>
                    @foreach ($results['learners'] as $learner)
                        <tr>
                            <td dir="auto">{{ $learner['name'] }}</td>
                            <td>{{ $stateLabels[$learner['state']] }}@if ($learner['closed_by'] === 'time') · {{ __('handed in after time was up, so only answers saved in time count') }}@endif</td>
                            <td>{{ $learner['state'] === 'handed_in' ? __(':correct of :marked answered', ['correct' => $learner['correct_count'], 'marked' => $learner['marked_count']]) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div></div>
        @endif
    </section>

    <section class="page-section" aria-labelledby="topic-results">
        <h2 id="topic-results">{{ __('Topics') }}</h2>
        @if ($results['topics'] === [])
            <p>{{ __('Topic results appear once learners hand in.') }}</p>
        @else
            <ul>
                @foreach ($results['topics'] as $topic)
                    <li>{{ $topic['name'] }}: {{ __(':correct of :answered answers right', ['correct' => $topic['correct'], 'answered' => $topic['answered']]) }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($results['active'])
        <form method="POST" action="{{ route('practice-tests.withdraw', ['group' => $results['group_id'], 'blueprint' => $results['id']]) }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ $operationId }}">
            <input type="hidden" name="expected_revision" value="{{ $results['revision'] }}">
            <button class="m3-button m3-button--text m3-button--error" type="submit">{{ __('Withdraw this test') }}</button>
        </form>
    @endif
</div>
@endsection
