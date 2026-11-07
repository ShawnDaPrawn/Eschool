@extends('layouts.master')
@section('title', __('Practice marking'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Practice marking')" :subtitle="__('Short written answers that matched no accepted answer, and spoken answers, wait here for you. Only the current teachers of the learner\'s group can see, hear or mark them. Your mark counts towards the learner\'s practice exactly once.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach
    <p><a class="m3-button m3-button--text" href="{{ route('question-banks.index') }}">{{ __('Question banks') }}</a> <a class="m3-button m3-button--text" href="{{ route('question-banks.write') }}">{{ __('Write a practice question') }}</a></p>

    @if ($groups === [])
        <x-page.empty-state :title="__('Nothing to mark')" :message="__('Answers wait here for the teachers of the learning groups they were given in.')" />
    @else
        <nav class="page-section" aria-label="{{ __('Your groups') }}"><ul>
            @foreach ($groups as $row)
                <li><a href="{{ route('question-banks.marking', ['group' => $row['id']]) }}" @if (($group['id'] ?? null) === $row['id']) aria-current="page" @endif>{{ $row['name'] }} · {{ $row['subject_name'] }}</a>:
                    {{ trans_choice(':count answer waiting|:count answers waiting', $row['waiting'], ['count' => $row['waiting']]) }}</li>
            @endforeach
        </ul></nav>
    @endif

    @if ($queue !== null)
        <section class="page-section" aria-labelledby="marking-queue">
            <h2 id="marking-queue">{{ $group['name'] }}</h2>
            @forelse ($queue['items'] as $item)
                <article class="m3-card m3-card--outlined page-section" aria-labelledby="answer-{{ $item['id'] }}"><div class="m3-card__content">
                    <h3 id="answer-{{ $item['id'] }}">{{ $item['learner_name'] }} · {{ __($kinds[$item['kind']] ?? $item['kind']) }} · {{ $item['answered_on'] }}</h3>
                    <p dir="auto"><strong>{{ __('Question') }}:</strong> {{ $item['prompt'] }}</p>
                    @if ($item['answer'] !== null)
                        <p dir="auto"><strong>{{ __('Learner\'s answer') }}:</strong> {{ $item['answer'] }}</p>
                    @endif
                    @if ($item['audio'] !== null)
                        @if ($item['audio']['kept'])
                            <audio controls preload="none" src="{{ route('question-banks.marking.audio', ['group' => $group['id'], 'review' => $item['id']]) }}">{{ __('Your browser cannot play this recording.') }}</audio>
                            <p class="page-field-help">{{ __('Deleted on :date under the retention rule.', ['date' => $item['audio']['delete_after']]) }}</p>
                        @else
                            <p>{{ __('This recording has been deleted under the retention rule.') }}</p>
                        @endif
                    @endif
                    @if ($item['accepted'] !== [])
                        <p><strong>{{ __('Accepted answers') }}:</strong> {{ implode(' · ', $item['accepted']) }}</p>
                    @endif
                    <p><strong>{{ __('Worked explanation') }}:</strong> {{ $item['explanation'] }}</p>
                    <div class="page-inline-form">
                        @foreach ([1 => __('Correct'), 0 => __('Not yet')] as $value => $label)
                            <form method="POST" action="{{ route('question-banks.marking.mark', ['group' => $group['id'], 'review' => $item['id']]) }}" class="page-inline-form">
                                @csrf
                                <input type="hidden" name="operation_id" value="{{ $operationId() }}">
                                <input type="hidden" name="correct" value="{{ $value }}">
                                <button class="m3-button {{ $value ? 'm3-button--filled' : 'm3-button--outlined' }}" type="submit"
                                    aria-label="{{ __(':mark for :learner', ['mark' => $label, 'learner' => $item['learner_name']]) }}">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                </div></article>
            @empty
                <x-page.empty-state :title="__('Nothing waiting')" :message="__('Every practice answer in this group is marked.')" />
            @endforelse
            @if ($queue['last_page'] > 1)
                <nav aria-label="{{ __('Pages') }}"><ul class="m3-pagination">
                    @for ($page = 1; $page <= $queue['last_page']; $page++)
                        <li><a href="{{ route('question-banks.marking', ['group' => $group['id'], 'page' => $page]) }}" @if ($page === $queue['page']) aria-current="page" @endif>{{ $page }}</a></li>
                    @endfor
                </ul></nav>
            @endif
        </section>
    @endif
</div>
@endsection
