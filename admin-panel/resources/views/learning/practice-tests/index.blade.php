@extends('layouts.master')
@section('title', __('Practice tests'))
@section('content')
@php
    $windowLabel = function (array $test) {
        $now = now('UTC');
        if (! $test['active']) {
            return __('Withdrawn');
        }
        if ($now->lt(\Carbon\CarbonImmutable::parse($test['opens_at']))) {
            return __('Not open yet');
        }

        return $now->lt(\Carbon\CarbonImmutable::parse($test['closes_at'])) ? __('Open') : __('Closed');
    };
    $local = fn (?string $instant) => $instant === null ? '' : \Carbon\CarbonImmutable::parse($instant)->setTimezone($zone)->format('Y-m-d H:i');
    $sourceLabels = ['school' => __('your question bank'), 'library' => __('the Navigate Ed library')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Practice tests')" :subtitle="__('Set a timed cycle test from your question bank for one of your groups. Each learner gets their own mix of questions, answers stay on the server until they hand in, and the memo opens when the test closes. Results count as practice, never as official marks.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    @if ($groups === [])
        <x-page.empty-state :title="__('No groups to set tests for')" :message="__('Your current teaching groups appear here once daily practice is switched on and you teach a group.')" />
    @else
        <nav aria-label="{{ __('Teaching groups') }}"><ul>
            @foreach ($groups as $group)
                <li>@if ($group['id'] === $groupId)<strong aria-current="page">{{ $group['name'] }} · {{ $group['subject_name'] }}</strong>@else<a href="{{ route('practice-tests.index', ['group' => $group['id']]) }}">{{ $group['name'] }} · {{ $group['subject_name'] }}</a>@endif</li>
            @endforeach
        </ul></nav>

        <section class="page-section" aria-labelledby="cycle-tests">
            <h2 id="cycle-tests">{{ __('Cycle tests') }}</h2>
            @if ($listing['tests'] === [])
                <x-page.empty-state :title="__('No cycle tests yet')" :message="__('Tests you set for this group appear here with how many learners have handed in.')" />
            @else
                <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Cycle tests') }}" tabindex="0"><table class="page-table__table">
                    <caption>{{ __('Cycle tests') }}</caption>
                    <thead><tr><th scope="col">{{ __('Test') }}</th><th scope="col">{{ __('Window') }}</th><th scope="col">{{ __('Questions and time') }}</th><th scope="col">{{ __('Handed in') }}</th><th scope="col"><span class="m3-visually-hidden">{{ __('Open') }}</span></th></tr></thead>
                    <tbody>
                        @foreach ($listing['tests'] as $test)
                            <tr>
                                <td dir="auto">{{ $test['title'] }}</td>
                                <td>{{ $windowLabel($test) }} · {{ $local($test['opens_at']) }} – {{ $local($test['closes_at']) }}</td>
                                <td>{{ trans_choice(':count question in :minutes minutes|:count questions in :minutes minutes', $test['question_count'], ['count' => $test['question_count'], 'minutes' => $test['time_limit_minutes']]) }}</td>
                                <td>{{ __(':handed of :started started', ['handed' => $test['handed_in'], 'started' => $test['started']]) }}</td>
                                <td><a href="{{ route('practice-tests.show', ['group' => $groupId, 'blueprint' => $test['id']]) }}">{{ __('See results') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div></div>
            @endif
        </section>

        @if ($options['writable'])
        <section class="page-section" aria-labelledby="set-test">
            <h2 id="set-test">{{ __('Set a cycle test') }}</h2>
            @if (collect($options['topics'])->sum('available') === 0)
                <x-page.empty-state :title="__('No questions ready yet')" :message="__('Approve practice questions for this group, or use questions from the question bank here, then set a test.')" />
            @else
            <form method="POST" action="{{ route('practice-tests.create', ['group' => $groupId]) }}" class="m3-card m3-card--outlined"><div class="m3-card__content">
                @csrf
                <input type="hidden" name="operation_id" value="{{ old('operation_id', $operationId) }}">
                <x-page.field name="title" id="test-title" :label="__('Title')" required maxlength="120" />
                <x-m3.checkbox-group name="topic_ids" :legend="__('Topics')" required
                    :options="collect($options['topics'])->filter(fn ($t) => $t['available'] > 0)->mapWithKeys(fn ($t) => [$t['id'] => $t['name'].' ('.trans_choice(':count question from :source|:count questions from :source', $t['available'], ['count' => $t['available'], 'source' => $sourceLabels[$t['source']]]).')'])->all()" />
                <div class="m3-grid">
                    <div class="m3-col-12 m3-col-expanded-6"><x-page.field name="question_count" id="test-questions" type="number" :label="__('Number of questions')" :value="10" required :min="$options['limits']['questions_min']" :max="$options['limits']['questions_max']" /></div>
                    <div class="m3-col-12 m3-col-expanded-6"><x-page.field name="time_limit_minutes" id="test-minutes" type="number" :label="__('Time limit in minutes')" :value="30" required :min="$options['limits']['minutes_min']" :max="$options['limits']['minutes_max']" /></div>
                    <div class="m3-col-12 m3-col-expanded-6"><x-page.field name="opens_at" id="test-opens" type="datetime-local" :label="__('Opens')" required /></div>
                    <div class="m3-col-12 m3-col-expanded-6"><x-page.field name="closes_at" id="test-closes" type="datetime-local" :label="__('Closes')" :help="__('At most 31 days after it opens. The memo opens to learners when the test closes.')" required /></div>
                </div>
                <x-page.field name="weighting" id="test-weighting" type="select" :label="__('Share the questions between topics')" required
                    :options="['even' => __('Evenly'), 'past_papers' => __('Like the past papers chosen below')]" value="even" />
                @if ($options['papers'] !== [])
                    <x-m3.checkbox-group name="paper_version_ids" :legend="__('Past papers to copy the topic shares from')"
                        :options="collect($options['papers'])->mapWithKeys(fn ($p) => [$p['version_id'] => $p['title'].' · '.$p['paper_year'].' '.$p['paper_code']])->all()" />
                @endif
                <button class="m3-button m3-button--filled" type="submit">{{ __('Set the test') }}</button>
            </div></form>
            @endif
        </section>
        @endif
    @endif
</div>
@endsection
