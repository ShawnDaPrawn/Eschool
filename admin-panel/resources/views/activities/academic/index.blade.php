@extends('layouts.master')
@section('title', __('Academic competitions'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Academic competitions')" :subtitle="__('Debates, quizzes and speech contests with other schools. Each school keeps its own entrants, adjudicators and notes. The other school sees your school and team name, never your learners\' names.')" />
    @include('activities.fixtures.messages')
    <p><a href="{{ route('activity-fixtures.index') }}">{{ __('Fixtures between schools') }}</a></p>

    <section class="page-section" aria-labelledby="academic-list">
        <h2 id="academic-list">{{ __('Competitions') }}</h2>
        @if ($contests['items'] === [])
            <x-page.empty-state :title="__('No academic competitions yet')" :message="__('Arrange your first one below.')" />
        @else
            <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Academic competitions') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Academic competitions') }}</caption>
                <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Kind') }}</th><th scope="col">{{ __('Competition') }}</th><th scope="col">{{ __('Result') }}</th></tr></thead>
                <tbody>
                @foreach ($contests['items'] as $row)
                    <tr>
                        <td>{{ $row['on_date'] }}</td>
                        <td>@include('activities.academic.kind', ['kind' => $row['kind']])</td>
                        <td><a href="{{ route('academic-competitions.show', ['competition' => $row['id']]) }}">{{ $row['host']['name'] }} {{ __('vs') }} {{ $row['guest']['name'] }}</a> · {{ $row['discipline'] }}@if ($row['status'] === 'cancelled') ({{ __('Cancelled') }})@endif</td>
                        <td>@include('activities.fixtures.result', ['result' => $row['result'], 'state' => $row['my_state']])</td>
                    </tr>
                @endforeach
                </tbody></table></div></div>
        @endif
    </section>

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="academic-new"><div class="m3-card__content">
        <h2 id="academic-new">{{ __('Arrange an academic competition') }}</h2>
        <p>{{ __('Find the other school first if it is on NavigateEd. For a school that is not, type its name instead.') }}</p>
        <form method="GET" action="{{ route('academic-competitions.index') }}" aria-label="{{ __('Find a school') }}">
            <x-page.field name="school_search" id="academic-school-search" :label="__('Find a school by name')" :value="$search" :restore-old-input="false" minlength="3" maxlength="100" />
            <x-m3.button type="submit" variant="tonal">{{ __('Find') }}</x-m3.button>
        </form>
        @if ($search !== '' && $schools === [])<p role="status">{{ __('No school on NavigateEd matches that name.') }}</p>@endif
        <form method="POST" action="{{ route('academic-competitions.create') }}" aria-label="{{ __('Arrange an academic competition') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <x-page.field name="kind" id="academic-kind" type="select" :label="__('Kind')" :options="collect($kinds)->mapWithKeys(fn ($k) => [$k => trim(view('activities.academic.kind', ['kind' => $k])->render())])->all()" :restore-old-input="false" required />
            @if ($schools !== [])
                <x-page.field name="guest_school_id" id="academic-guest" type="select" :label="__('Other school on NavigateEd')" :options="['' => __('None: the other school is not on NavigateEd')] + collect($schools)->pluck('name', 'id')->all()" :restore-old-input="false" />
            @endif
            <x-page.field name="external_opponent" id="academic-external" :label="__('Or a school not on NavigateEd')" :restore-old-input="false" maxlength="191" />
            <x-page.field name="activity_group_id" id="academic-group" type="select" :label="__('Your team (activity group)')" :options="['' => __('Choose a group')] + $groups" :restore-old-input="false" required />
            <x-page.field name="team_label" id="academic-label" :label="__('Team name the other school will see')" :restore-old-input="false" maxlength="100" required />
            <x-page.field name="discipline" id="academic-discipline" :label="__('Subject or activity')" :help="__('For example Debating or Mathematics olympiad.')" :restore-old-input="false" maxlength="100" required />
            <x-page.field name="motion" id="academic-motion" type="textarea" :label="__('Motion or topic')" :restore-old-input="false" maxlength="500" required />
            <x-page.field name="format" id="academic-format" :label="__('Format (optional)')" :help="__('For example World Schools, or a written round.')" :restore-old-input="false" maxlength="60" />
            <x-page.field name="rounds" id="academic-rounds" type="number" :label="__('Rounds (optional)')" :restore-old-input="false" min="1" max="20" />
            <x-page.field name="host_stance" id="academic-stance" type="select" :label="__('Your side in a debate')" :options="['' => __('Not a debate, or not decided yet'), 'proposition' => __('Proposition'), 'opposition' => __('Opposition')]" :restore-old-input="false" />
            <x-page.field name="on_date" id="academic-date" type="date" :label="__('Date')" :restore-old-input="false" required />
            <x-page.field name="starts_at" id="academic-time" type="time" :label="__('Starts at (optional)')" :restore-old-input="false" />
            <x-page.field name="venue" id="academic-venue" :label="__('Venue')" :restore-old-input="false" maxlength="191" required />
            <x-page.field name="host_is_home" id="academic-home" type="select" :label="__('Where it takes place')" :options="['1' => __('At your school'), '0' => __('At the other school')]" :restore-old-input="false" />
            <x-m3.button type="submit">{{ __('Arrange the competition') }}</x-m3.button>
        </form>
    </div></section>
</div>
@endsection
