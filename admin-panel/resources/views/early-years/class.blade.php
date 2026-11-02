@extends('layouts.master')
@section('title', __('Early years'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$section->full_name" :subtitle="__('Early years')" />
    <p><a href="{{ route('early-years.index') }}">{{ __('All classes') }}</a> · <a href="{{ route('early-years.diary', ['section' => $section->id]) }}">{{ __('Today\'s diary') }}</a>
        ({{ trans_choice(':count entry today|:count entries today', $diaryCount, ['count' => $diaryCount]) }})</p>

    <section class="page-section" aria-labelledby="early-years-learners">
        <h2 id="early-years-learners">{{ __('Portfolios') }}</h2>
        @if ($learners->isEmpty())
            <x-page.empty-state :title="__('No learners in this class today')" :message="__('Learners appear here while they are enrolled in the class.')" />
        @else
            <ul>
                @foreach ($learners as $learner)
                    <li><a href="{{ route('early-years.portfolio', ['learner' => $learner->id]) }}">{{ $learner->user?->full_name }}</a>
                        · {{ trans_choice(':count observation|:count observations', (int) ($counts[$learner->id] ?? 0), ['count' => (int) ($counts[$learner->id] ?? 0)]) }}</li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
