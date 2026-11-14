@extends('layouts.master')
@section('title', __('Fixtures between schools'))
@section('content')
<div class="content-wrapper m3 page-content fixture-workbench">
    <x-page.header :title="__('Fixtures between schools')" :subtitle="__('Arrange matches with other schools and keep a running table for a league. Each school keeps its own team sheet and notes. The other school sees your team name, and your team sheet only if you choose to share it, once both schools have accepted.')" />
    @include('activities.fixtures.messages')
    <nav class="panel-local-nav" aria-label="{{ __('Fixtures and competitions') }}">
    @foreach(['fixtures'=>'Fixtures','fixture-create'=>'Arrange fixture','competitions'=>'Competitions','competition-create'=>'Create competition'] as $key=>$label)<a href="{{ route('activity-fixtures.index',['screen'=>$key]) }}" @if($screen===$key) aria-current="page" @endif>{{ __($label) }}</a>@endforeach
    </nav>
    @if($screen==='fixtures')

    @php($invited = collect($fixtures['items'])->where('my_state', 'invited'))
    @php($competitionInvites = collect($competitions)->where('my_state', 'invited'))
    @if ($invited->isNotEmpty() || $competitionInvites->isNotEmpty())
    <section class="page-section" aria-labelledby="fixture-invitations">
        <h2 id="fixture-invitations">{{ __('Invitations waiting for your answer') }}</h2>
        <ul>
            @foreach ($invited as $row)
                <li><a href="{{ route('activity-fixtures.show', ['fixture' => $row['id']]) }}">{{ __(':school invited you to a fixture on :date', ['school' => $row['host']['name'], 'date' => $row['on_date']]) }}</a></li>
            @endforeach
            @foreach ($competitionInvites as $row)
                <li><a href="{{ route('activity-fixtures.competition-show', ['competition' => $row['id']]) }}">{{ __(':school invited you to join :competition', ['school' => $row['organiser'], 'competition' => $row['name']]) }}</a></li>
            @endforeach
        </ul>
    </section>
    @endif

    <section class="page-section" aria-labelledby="fixture-list">
        <h2 id="fixture-list">{{ __('Fixtures') }}</h2>
        @if ($fixtures['items'] === [])
            <x-page.empty-state :title="__('No fixtures yet')" :message="__('Use Arrange fixture to choose an opponent, date and team.')" />
        @else
            <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Fixtures') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Fixtures') }}</caption>
                <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Fixture') }}</th><th scope="col">{{ __('Competition') }}</th><th scope="col">{{ __('Result') }}</th></tr></thead>
                <tbody>
                @foreach ($fixtures['items'] as $row)
                    <tr>
                        <td>{{ $row['on_date'] }}</td>
                        <td><a href="{{ route('activity-fixtures.show', ['fixture' => $row['id']]) }}">{{ $row['host']['name'] }} {{ __('vs') }} {{ $row['guest']['name'] }}</a> · {{ $row['discipline'] }}@if ($row['status'] === 'cancelled') ({{ __('Cancelled') }})@endif</td>
                        <td>{{ $row['competition']['name'] ?? __('Friendly') }}</td>
                        <td>@include('activities.fixtures.result', ['result' => $row['result'], 'state' => $row['my_state']])</td>
                    </tr>
                @endforeach
                </tbody></table></div></div>
        @endif
    </section>

    @endif
    @if($screen==='fixture-create')
    <section class="m3-card m3-card--outlined page-section" aria-labelledby="fixture-new"><div class="m3-card__content">
        <h2 id="fixture-new">{{ __('Arrange a fixture') }}</h2>
        <p>{{ __('Find the other school first if it is on NavigateEd. For a school or club that is not, type its name instead.') }}</p>
        <form method="GET" action="{{ route('activity-fixtures.index') }}" aria-label="{{ __('Find a school') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            <x-page.field name="school_search" id="fixture-school-search" :label="__('Find a school by name')" :value="$formInput['school_search'] ?? ($search)" :restore-old-input="false" minlength="3" maxlength="100" />
            <x-m3.button type="submit" variant="tonal">{{ __('Find') }}</x-m3.button>
        </form>
        @if ($search !== '' && $schools === [])<p role="status">{{ __('No school on NavigateEd matches that name.') }}</p>@endif
        <form method="POST" action="{{ route('activity-fixtures.create') }}" aria-label="{{ __('Arrange a fixture') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            @if ($schools !== [])
                <x-page.field name="guest_school_id" id="fixture-guest" type="select" :label="__('Opponent school on NavigateEd')" :options="['' => __('None: the opponent is not on NavigateEd')] + collect($schools)->pluck('name', 'id')->all()" :restore-old-input="false" :value="$formInput['guest_school_id'] ?? ''" />
            @endif
            <x-page.field name="external_opponent" id="fixture-external" :label="__('Or an opponent not on NavigateEd')" :restore-old-input="false" maxlength="191" :value="$formInput['external_opponent'] ?? ''" />
            <x-page.field name="activity_group_id" id="fixture-group" type="select" :label="__('Your team (activity group)')" :options="['' => __('Choose a group')] + $groups" :restore-old-input="false" required :value="$formInput['activity_group_id'] ?? ''" />
            <x-page.field name="team_label" id="fixture-label" :label="__('Team name the other school will see')" :help="__('For example U14 A.')" :restore-old-input="false" maxlength="100" required :value="$formInput['team_label'] ?? ''" />
            <x-page.field name="discipline" id="fixture-discipline" :label="__('Sport or activity')" :restore-old-input="false" maxlength="100" required :value="$formInput['discipline'] ?? ''" />
            <x-page.field name="on_date" id="fixture-date" type="date" :label="__('Date')" :restore-old-input="false" required :value="$formInput['on_date'] ?? ''" />
            <x-page.field name="starts_at" id="fixture-time" type="time" :label="__('Starts at (optional)')" :restore-old-input="false" :value="$formInput['starts_at'] ?? ''" />
            <x-page.field name="venue" id="fixture-venue" :label="__('Venue')" :restore-old-input="false" maxlength="191" required :value="$formInput['venue'] ?? ''" />
            <x-page.field name="field" id="fixture-field" :label="__('Field or court (optional)')" :restore-old-input="false" maxlength="100" :value="$formInput['field'] ?? ''" />
            <x-page.field name="host_is_home" id="fixture-home" type="select" :label="__('Where you play')" :options="['1' => __('At home'), '0' => __('Away')]" :restore-old-input="false" :value="$formInput['host_is_home'] ?? ''" />
            @php($open = collect($competitions)->where('my_state', 'accepted')->pluck('name', 'id')->all())
            <x-page.field name="shared_competition_id" id="fixture-competition" type="select" :label="__('Competition')" :options="['' => __('Friendly (no competition)')] + $open" :restore-old-input="false" :value="$formInput['shared_competition_id'] ?? ''" />
            <x-m3.button type="submit">{{ __('Arrange the fixture') }}</x-m3.button>
        </form>
    </div></section>

    @endif
    @if($screen==='competitions')
    <section class="page-section" aria-labelledby="competition-list">
        <h2 id="competition-list">{{ __('Competitions and leagues') }}</h2>
        @if ($competitions === [])
            <x-page.empty-state :title="__('No competitions yet')" :message="__('Create a competition and invite schools to join it.')" />
        @else
            <ul>@foreach ($competitions as $row)<li><a href="{{ route('activity-fixtures.competition-show', ['competition' => $row['id']]) }}">{{ $row['name'] }} · {{ $row['season'] }}</a>@if ($row['my_state'] !== 'accepted') ({{ __('Not joined yet') }})@endif</li>@endforeach</ul>
        @endif
    </section>

    @endif
    @if($screen==='competition-create')
    <section class="m3-card m3-card--outlined page-section" aria-labelledby="competition-new"><div class="m3-card__content">
        <h2 id="competition-new">{{ __('Start a competition') }}</h2>
        <form method="POST" action="{{ route('activity-fixtures.competition-create') }}" aria-label="{{ __('Start a competition') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="kind" value="sport">
            <x-page.field name="name" id="competition-name" :label="__('Competition name')" :restore-old-input="false" maxlength="191" required :value="$formInput['name'] ?? ''" />
            <x-page.field name="discipline" id="competition-discipline" :label="__('Sport or activity')" :restore-old-input="false" maxlength="100" required :value="$formInput['discipline'] ?? ''" />
            <x-page.field name="season" id="competition-season" :label="__('Season')" :restore-old-input="false" maxlength="100" required :value="$formInput['season'] ?? ''" />
            <x-page.field name="points_win" id="competition-win" type="number" :label="__('Points for a win')" :value="$formInput['points_win'] ?? ('3')" :restore-old-input="false" min="0" max="10" required />
            <x-page.field name="points_draw" id="competition-draw" type="number" :label="__('Points for a draw')" :value="$formInput['points_draw'] ?? ('1')" :restore-old-input="false" min="0" max="10" required />
            <x-page.field name="points_loss" id="competition-loss" type="number" :label="__('Points for a loss')" :value="$formInput['points_loss'] ?? ('0')" :restore-old-input="false" min="0" max="10" required />
            <x-m3.button type="submit">{{ __('Start the competition') }}</x-m3.button>
        </form>
    </div></section>
@endif
</div>
@endsection
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/activity-workbench.css') }}?v={{ filemtime(public_path('assets/css/activity-workbench.css')) }}">@endsection
