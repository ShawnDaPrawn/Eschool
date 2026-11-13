@extends('layouts.master')
@php($f = $fixture)
@php($home = $f['host_is_home'] ? $f['host'] : $f['guest'])
@php($away = $f['host_is_home'] ? $f['guest'] : $f['host'])
@section('title', __('Fixture'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$home['name'].' '.__('vs').' '.$away['name']" :subtitle="$f['discipline'].' · '.$f['on_date'].($f['starts_at'] ? ' '.$f['starts_at'] : '').' · '.$f['venue'].($f['field'] ? ', '.$f['field'] : '')" />
    @include('activities.fixtures.messages')
    <nav class="panel-local-nav" aria-label="{{ __('Fixture tasks') }}">
    <a href="{{ route('activity-fixtures.show',['fixture'=>$f['id'],'screen'=>'overview']) }}" @if($screen==='overview') aria-current="page" @endif>{{ __('Overview') }}</a>
    @if($f['can_decide'])<a href="{{ route('activity-fixtures.show',['fixture'=>$f['id'],'screen'=>'invitation']) }}" @if($screen==='invitation') aria-current="page" @endif>{{ __('Answer invitation') }}</a>@endif
    @if($f['side'])<a href="{{ route('activity-fixtures.show',['fixture'=>$f['id'],'screen'=>'team']) }}" @if($screen==='team') aria-current="page" @endif>{{ __('Team sheet') }}</a>@endif
    @if($f['open'] && $f['side'] && $f['kind'] === 'sport')<a href="{{ route('activity-fixtures.show',['fixture'=>$f['id'],'screen'=>'result']) }}" @if($screen==='result') aria-current="page" @endif>{{ __('Result') }}</a>@endif
    @if($f['can_schedule'])<a href="{{ route('activity-fixtures.show',['fixture'=>$f['id'],'screen'=>'configuration']) }}" @if($screen==='configuration') aria-current="page" @endif>{{ __('Edit or cancel fixture') }}</a>@endif
    </nav>
    <p><a href="{{ route('activity-fixtures.index') }}">{{ __('All fixtures') }}</a>@if ($f['kind'] !== 'sport') · <a href="{{ route('academic-competitions.show', ['competition' => $f['id']]) }}">{{ __('Motion, adjudicators and result') }}</a>@endif</p>

    @if($screen==='overview')
    <x-m3.card :title="__('The fixture')">
        <dl>
            <dt>{{ __('Competition') }}</dt><dd>{{ $f['competition']['name'] ?? __('Friendly') }}</dd>
            <dt>{{ __('Status') }}</dt><dd>{{ $f['status'] === 'cancelled' ? __('Cancelled') : __('Scheduled') }}</dd>
            @foreach (['host' => __('Host'), 'guest' => __('Guest')] as $role => $label)
                <dt>{{ $label }}</dt>
                <dd>{{ $f[$role]['name'] }}@if ($f[$role]['team_label']) · {{ $f[$role]['team_label'] }}@endif
                    @if ($f[$role]['external']) ({{ __('not on NavigateEd') }})@elseif ($f[$role]['state'] === 'invited') ({{ __('has not answered yet') }})@elseif ($f[$role]['state'] === 'declined') ({{ __('declined') }})@endif</dd>
            @endforeach
            <dt>{{ __('Result') }}</dt><dd>@include('activities.fixtures.result', ['result' => $f['result'], 'state' => $f['my_state']])</dd>
        </dl>
    </x-m3.card>

    @endif
    @if ($screen==='invitation' && $f['can_decide'])
    <x-m3.card :title="__('Answer the invitation')">
        <p>{{ __('Until you accept, :school sees only your school\'s name. Once you accept, both schools see each other\'s team name, and a team sheet only if that school shares it.', ['school' => $f['host']['name']]) }}</p>
        <form method="POST" action="{{ route('activity-fixtures.decision', ['fixture' => $f['id']]) }}" aria-label="{{ __('Accept the fixture') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="decision" value="accept">
            <x-page.field name="activity_group_id" id="decision-group" type="select" :label="__('Your team (activity group)')" :options="['' => __('Choose a group')] + $groups" :restore-old-input="false" required :value="$formInput['activity_group_id'] ?? ''" />
            <x-page.field name="team_label" id="decision-label" :label="__('Team name the other school will see')" :restore-old-input="false" maxlength="100" required :value="$formInput['team_label'] ?? ''" />
            <x-m3.button type="submit">{{ __('Accept') }}</x-m3.button>
        </form>
        <form method="POST" action="{{ route('activity-fixtures.decision', ['fixture' => $f['id']]) }}" aria-label="{{ __('Decline the fixture') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="decision" value="decline">
            <x-m3.button type="submit" variant="outlined">{{ __('Decline') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif

    @if ($screen==='team' && $f['side'])
    @php($side = $f['side'])
    @php($picked = $selectedGroupId===$side['activity_group_id'] ? collect($side['selections'])->keyBy('learner_id') : collect())
    <x-m3.card :title="__('Your team sheet')">
        <p>{{ __('Only your school sees the learners\' names here. If you share the team sheet, the other school sees the label and position you give each player, nothing else.') }}</p>
        @if($canChangeGroup)
        <form method="GET" action="{{ route('activity-fixtures.show',['fixture'=>$f['id']]) }}" aria-label="{{ __('Choose the team group') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
        <x-page.field name="activity_group_id" id="side-group-choice" type="select" :label="__('Your activity group')" :options="$groups" :value="$formInput['activity_group_id'] ?? ($selectedGroupId)" :restore-old-input="false" required />
        <p>{{ __('Choose the group before editing the team sheet. Refreshing loads its eligible learners; no change is saved until you save the team sheet.') }}</p>
        <x-m3.button type="submit" variant="outlined">{{ __('Load eligible learners') }}</x-m3.button></form>
        @endif
        <form method="POST" action="{{ route('activity-fixtures.side', ['fixture' => $f['id']]) }}" aria-label="{{ __('Your team sheet') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="revision" value="{{ $side['revision'] }}">
            <input type="hidden" name="activity_group_id" value="{{ $selectedGroupId }}"><p>{{ __('Group') }}: {{ $groups[$selectedGroupId] ?? $side['group_name'] }}</p>
            <x-page.field name="team_label" id="side-label" :label="__('Team name the other school will see')" :value="$formInput['team_label'] ?? ($side['team_label'])" :restore-old-input="false" maxlength="100" required />
            @if ($members === [])
                <p>{{ __('Nobody is in this group on the fixture day.') }}</p>
            @else
                <div class="page-table__scroll" role="region" aria-label="{{ __('Players') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Players') }}</caption>
                    <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Label on a shared sheet') }}</th><th scope="col">{{ __('Position') }}</th></tr></thead>
                    <tbody>
                    @foreach ($members as $learnerId => $name)
                        <tr>
                            <td><x-m3.checkbox name="pick[]" :value="$learnerId" :label="$name" :checked="$formInput !== [] ? in_array((string) $learnerId, array_map('strval', $formInput['pick'] ?? []), true) : $picked->has($learnerId)" /></td>
                            <td><x-page.field :name="'label['.$learnerId.']'" :id="'side-sheet-'.$learnerId" :label="__('Label')" :value="$formInput['label'][$learnerId] ?? $picked[$learnerId]['sheet_label'] ?? ''" :restore-old-input="false" maxlength="60" /></td>
                            <td><x-page.field :name="'position['.$learnerId.']'" :id="'side-position-'.$learnerId" :label="__('Position')" :value="$formInput['position'][$learnerId] ?? $picked[$learnerId]['position'] ?? ''" :restore-old-input="false" maxlength="40" /></td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
            @endif
            <x-m3.checkbox name="sheet_shared" value="1" :label="__('Share this team sheet with the other school')" :checked="$formInput !== [] ? (bool) ($formInput['sheet_shared'] ?? false) : $side['sheet_shared']" />
            <x-page.field name="notes" id="side-notes" type="textarea" :label="__('Private notes (only your school)')" :value="$formInput['notes'] ?? ($side['notes'])" :restore-old-input="false" maxlength="2000" />
            <x-m3.button type="submit">{{ __('Save the team sheet') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif

    @if ($screen==='overview' && $f['their_sheet'] !== null)
    <x-m3.card :title="__('The other school\'s shared team sheet')">
        <ul>@forelse ($f['their_sheet'] as $player)<li>{{ $player['sheet_label'] }}@if ($player['position']) · {{ $player['position'] }}@endif</li>@empty<li>{{ __('No players on the sheet yet.') }}</li>@endforelse</ul>
    </x-m3.card>
    @endif

    @if ($screen==='result' && $f['open'] && $f['side'] && $f['kind'] === 'sport')
    <x-m3.card :title="__('Score')">
        @if ($f['guest']['external'])
            <p>{{ __('The other team is not on NavigateEd, so your report is the result.') }}</p>
        @else
            <p>{{ __('Both schools report the score. It counts once the two reports agree.') }}</p>
        @endif
        @if ($f['result']['my_report'])<p>{{ __('You reported :host – :guest.', ['host' => $f['result']['my_report']['host_score'], 'guest' => $f['result']['my_report']['guest_score']]) }}</p>@endif
        @if ($f['result']['their_report'])<p role="status">{{ __('The other school reported :host – :guest. Check with them, then report the correct score.', ['host' => $f['result']['their_report']['host_score'], 'guest' => $f['result']['their_report']['guest_score']]) }}</p>@endif
        @if ($f['result']['waiting_for'] === 'them')<p>{{ __('Waiting for the other school to report the score.') }}</p>@endif
        @if ($f['can_report'])
        <form method="POST" action="{{ route('activity-fixtures.result', ['fixture' => $f['id']]) }}" aria-label="{{ __('Report the score') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <x-page.field name="host_score" id="result-host" type="number" :label="$f['host']['name']" :value="$formInput['host_score'] ?? ($f['result']['my_report']['host_score'] ?? '')" :restore-old-input="false" min="0" max="999" required />
            <x-page.field name="guest_score" id="result-guest" type="number" :label="$f['guest']['name']" :value="$formInput['guest_score'] ?? ($f['result']['my_report']['guest_score'] ?? '')" :restore-old-input="false" min="0" max="999" required />
            <x-m3.button type="submit">{{ __('Report the score') }}</x-m3.button>
        </form>
        @else
            <p>{{ __('Scores can be reported from the day of the fixture.') }}</p>
        @endif
    </x-m3.card>
    @endif

    @if ($screen==='configuration' && $f['can_schedule'])
    <x-m3.card :title="__('Change the date or venue')">
        <form method="POST" action="{{ route('activity-fixtures.schedule', ['fixture' => $f['id']]) }}" aria-label="{{ __('Change the date or venue') }}">
            <input type="hidden" name="screen" value="{{ $screen }}">
            @csrf
            @if(isset($search))<input type="hidden" name="school_search" value="{{ $search }}">@endif
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="revision" value="{{ $f['revision'] }}">
            <x-page.field name="on_date" id="schedule-date" type="date" :label="__('Date')" :value="$formInput['on_date'] ?? ($f['on_date'])" :restore-old-input="false" required />
            <x-page.field name="starts_at" id="schedule-time" type="time" :label="__('Starts at (optional)')" :value="$formInput['starts_at'] ?? ($f['starts_at'])" :restore-old-input="false" />
            <x-page.field name="venue" id="schedule-venue" :label="__('Venue')" :value="$formInput['venue'] ?? ($f['venue'])" :restore-old-input="false" maxlength="191" required />
            <x-page.field name="field" id="schedule-field" :label="__('Field or court (optional)')" :value="$formInput['field'] ?? ($f['field'])" :restore-old-input="false" maxlength="100" />
            <x-page.field name="host_is_home" id="schedule-home" type="select" :label="__('Where you play')" :options="['1' => __('At home'), '0' => __('Away')]" :value="$formInput['host_is_home'] ?? ((int) $f['host_is_home'])" :restore-old-input="false" />
            <x-page.field name="status" id="schedule-status" type="select" :label="__('Status')" :options="['scheduled' => __('Scheduled'), 'cancelled' => __('Cancelled')]" :value="$formInput['status'] ?? ($f['status'])" :restore-old-input="false" />
            <x-m3.button type="submit">{{ __('Save the fixture') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif
</div>
@endsection
