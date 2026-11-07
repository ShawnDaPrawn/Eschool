@extends('layouts.master')
@php($c = $competition)
@section('title', $c['name'])
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$c['name']" :subtitle="$c['discipline'].' · '.$c['season'].' · '.__('Organised by :school', ['school' => $c['organiser']])" />
    @include('activities.fixtures.messages')
    <p><a href="{{ route('activity-fixtures.index') }}">{{ __('All fixtures') }}</a></p>

    @if ($c['can_decide'])
    <x-m3.card :title="__('Answer the invitation')">
        <p>{{ __('Until you accept, the other schools see only your school\'s name. Once you accept, members see your team names and confirmed results in this competition.') }}</p>
        @foreach (['accept' => __('Join the competition'), 'decline' => __('Decline')] as $decision => $label)
        <form method="POST" action="{{ route('activity-fixtures.competition-decision', ['competition' => $c['id']]) }}" aria-label="{{ $label }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="decision" value="{{ $decision }}">
            <x-m3.button type="submit" :variant="$decision === 'accept' ? 'filled' : 'outlined'">{{ $label }}</x-m3.button>
        </form>
        @endforeach
    </x-m3.card>
    @endif

    @if ($c['my_state'] === 'accepted')
    <section class="page-section" aria-labelledby="competition-standings">
        <h2 id="competition-standings">{{ __('Standings') }}</h2>
        <p>{{ __('From results both schools confirmed. A win earns :win, a draw :draw and a loss :loss.', ['win' => $c['points_win'], 'draw' => $c['points_draw'], 'loss' => $c['points_loss']]) }}</p>
        @if ($c['standings'] === [])
            <x-page.empty-state :title="__('No confirmed results yet')" :message="__('The table fills in as results are confirmed.')" />
        @else
            <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Standings') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Standings') }}</caption>
                <thead><tr><th scope="col">{{ __('Team') }}</th><th scope="col">{{ __('Played') }}</th><th scope="col">{{ __('Won') }}</th><th scope="col">{{ __('Drawn') }}</th><th scope="col">{{ __('Lost') }}</th><th scope="col">{{ __('For') }}</th><th scope="col">{{ __('Against') }}</th><th scope="col">{{ __('Difference') }}</th><th scope="col">{{ __('Points') }}</th></tr></thead>
                <tbody>@foreach ($c['standings'] as $row)<tr><td>{{ $row['team'] }}</td><td>{{ $row['played'] }}</td><td>{{ $row['won'] }}</td><td>{{ $row['drawn'] }}</td><td>{{ $row['lost'] }}</td><td>{{ $row['scored'] }}</td><td>{{ $row['conceded'] }}</td><td>{{ $row['difference'] }}</td><td>{{ $row['points'] }}</td></tr>@endforeach</tbody>
            </table></div></div>
        @endif
    </section>

    <x-m3.card :title="__('Member schools')">
        <ul>@foreach ($c['members'] as $member)<li>{{ $member['name'] }}@if ($member['state'] === 'invited') ({{ __('has not answered yet') }})@elseif ($member['state'] === 'declined') ({{ __('declined') }})@endif</li>@endforeach</ul>
    </x-m3.card>
    @endif

    @if ($c['can_invite'])
    <x-m3.card :title="__('Invite a school')">
        <form method="GET" action="{{ route('activity-fixtures.competition-show', ['competition' => $c['id']]) }}" aria-label="{{ __('Find a school') }}">
            <x-page.field name="school_search" id="competition-school-search" :label="__('Find a school by name')" :value="$search" :restore-old-input="false" minlength="3" maxlength="100" />
            <x-m3.button type="submit" variant="tonal">{{ __('Find') }}</x-m3.button>
        </form>
        @if ($search !== '' && $schools === [])<p role="status">{{ __('No school on NavigateEd matches that name.') }}</p>@endif
        @if ($schools !== [])
        <form method="POST" action="{{ route('activity-fixtures.competition-invite', ['competition' => $c['id']]) }}" aria-label="{{ __('Invite a school') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <x-page.field name="school_id" id="competition-invite-school" type="select" :label="__('School')" :options="collect($schools)->pluck('name', 'id')->all()" :restore-old-input="false" required />
            <x-m3.button type="submit">{{ __('Send the invitation') }}</x-m3.button>
        </form>
        @endif
    </x-m3.card>
    @endif
</div>
@endsection
