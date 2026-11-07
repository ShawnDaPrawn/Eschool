@extends('layouts.master')
@section('title', __('Starting checks'))
@section('content')
@php
    $statusLabels = ['not_started' => __('Not started'), 'in_progress' => __('Started'), 'completed' => __('Finished')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Starting checks')" :subtitle="__('A short check from the Navigate Ed library that each learner takes once a year, in the learner app. It fills the mastery map with a starting point for each topic. It is not a test: learners are not told a mark, and families see only where practice begins.')" />

    @if ($groups === [])
        <x-page.empty-state :title="__('No groups to show')" :message="__('Starting checks appear for the teaching groups you teach this year.')" />
    @else
        <form method="GET" action="{{ route('starting-checks.index') }}" class="page-section" aria-label="{{ __('Choose a group') }}">
            <x-page.field name="group" id="starting-check-group" type="select" :label="__('Teaching group')" :restore-old-input="false"
                :options="collect($groups)->mapWithKeys(fn ($g) => [$g['id'] => $g['name'].' · '.$g['subject_name']])->all()" :value="$chosen" />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Open group') }}</button>
        </form>
    @endif

    @if ($view !== null)
        @if (! $view['available'])
            <x-page.empty-state :title="__('No starting check for this group')" :message="__('A starting check appears when the Navigate Ed library is switched on for the school and has a published check for this subject and grade.')" />
        @else
            <section class="page-section" aria-labelledby="starting-map">
                <h2 id="starting-map">{{ __('Starting map for :group', ['group' => $view['group_name']]) }}</h2>
                <p dir="auto">{{ $view['title'] }}</p>
                <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Starting map per topic') }}" tabindex="0">
                    <table class="page-table__table">
                        <caption>{{ __('How many learners start where, per topic') }}</caption>
                        <thead><tr><th scope="col">{{ __('Topic') }}</th>@foreach ($bands as $band => $label)<th scope="col">{{ __($label) }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($view['topics'] as $topic)
                                <tr><th scope="row" dir="auto">{{ $topic['name'] }}</th>@foreach ($bands as $band => $label)<td>{{ $topic[$band] }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div></div>
            </section>

            <section class="page-section" aria-labelledby="starting-learners">
                <h2 id="starting-learners">{{ __('Learners') }}</h2>
                @if ($view['learners'] === [])
                    <x-page.empty-state :title="__('No learners in this group today')" :message="__('Learners appear here while they are members of the group.')" />
                @else
                    @php $topicNames = collect($view['topics'])->pluck('name', 'library_topic_id'); @endphp
                    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Learners by name') }}" tabindex="0">
                        <table class="page-table__table">
                            <caption>{{ __('Learners by name, with where each starts per topic') }}</caption>
                            <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Starting check') }}</th><th scope="col">{{ __('Where they start') }}</th></tr></thead>
                            <tbody>
                                @foreach ($view['learners'] as $learner)
                                    <tr>
                                        <th scope="row" dir="auto">{{ $learner['name'] }}</th>
                                        <td>{{ $statusLabels[$learner['status']] ?? $learner['status'] }}</td>
                                        <td>
                                            @if ($learner['topics'] === [])
                                                —
                                            @else
                                                <ul>
                                                    @foreach ($learner['topics'] as $topic)
                                                        <li dir="auto">{{ $topicNames[$topic['library_topic_id']] ?? '' }}: {{ __($bands[$topic['band']] ?? $topic['band']) }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div></div>
                @endif
            </section>
        @endif
    @endif
</div>
@endsection
