@extends('layouts.master')
@php($c = $contest)
@php($a = $c['academic'])
@php($home = $c['host_is_home'] ? $c['host'] : $c['guest'])
@php($away = $c['host_is_home'] ? $c['guest'] : $c['host'])
@php($entrants = collect($c['side']['selections'] ?? [])->mapWithKeys(fn ($row) => [$row['learner_id'] => $row['learner_name']])->all())
@section('title', __('Academic competition'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$home['name'].' '.__('vs').' '.$away['name']" :subtitle="$c['discipline'].' · '.$c['on_date'].($c['starts_at'] ? ' '.$c['starts_at'] : '').' · '.$c['venue']" />
    @include('activities.fixtures.messages')
    <p><a href="{{ route('academic-competitions.index') }}">{{ __('All academic competitions') }}</a> · <a href="{{ route('activity-fixtures.show', ['fixture' => $c['id']]) }}">{{ __('Invitation and entrants') }}</a></p>

    <x-m3.card :title="__('The competition')">
        <dl>
            <dt>{{ __('Kind') }}</dt><dd>@include('activities.academic.kind', ['kind' => $a['kind']]) ({{ $a['entry'] === 'team' ? __('teams') : __('individual entrants') }})</dd>
            <dt>{{ __('Motion or topic') }}</dt><dd>{{ $a['motion'] }}</dd>
            @if ($a['format'])<dt>{{ __('Format') }}</dt><dd>{{ $a['format'] }}</dd>@endif
            @if ($a['rounds'])<dt>{{ __('Rounds') }}</dt><dd>{{ $a['rounds'] }}</dd>@endif
            @if ($a['my_stance'])<dt>{{ __('Your side') }}</dt><dd>{{ $a['my_stance'] === 'proposition' ? __('Proposition') : __('Opposition') }}</dd>@endif
            <dt>{{ __('Competition') }}</dt><dd>{{ $c['competition']['name'] ?? __('Friendly') }}</dd>
            @foreach (['host' => __('Host'), 'guest' => __('Guest')] as $role => $label)
                <dt>{{ $label }}</dt>
                <dd>{{ $c[$role]['name'] }}@if ($c[$role]['team_label']) · {{ $c[$role]['team_label'] }}@endif
                    @if ($c[$role]['external']) ({{ __('not on NavigateEd') }})@elseif ($c[$role]['state'] === 'invited') ({{ __('has not answered yet') }})@elseif ($c[$role]['state'] === 'declined') ({{ __('declined') }})@endif</dd>
            @endforeach
            <dt>{{ __('Result') }}</dt>
            <dd>@include('activities.fixtures.result', ['result' => $c['result'], 'state' => $c['my_state']])
                @if ($c['result']['winner'] === 'draw') · {{ __('A draw') }}@elseif ($c['result']['winner']) · {{ __('Won by :school', ['school' => $c[$c['result']['winner']]['name']]) }}@endif</dd>
        </dl>
    </x-m3.card>

    @if ($a['can_edit_details'])
    <x-m3.card :title="__('Motion, format and rounds')">
        <form method="POST" action="{{ route('academic-competitions.details', ['competition' => $c['id']]) }}" aria-label="{{ __('Motion, format and rounds') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="revision" value="{{ $c['revision'] }}">
            <x-page.field name="motion" id="details-motion" type="textarea" :label="__('Motion or topic')" :value="$a['motion']" :restore-old-input="false" maxlength="500" required />
            <x-page.field name="format" id="details-format" :label="__('Format (optional)')" :value="$a['format']" :restore-old-input="false" maxlength="60" />
            <x-page.field name="rounds" id="details-rounds" type="number" :label="__('Rounds (optional)')" :value="$a['rounds']" :restore-old-input="false" min="1" max="20" />
            @if ($a['kind'] === 'debate')
                <x-page.field name="host_stance" id="details-stance" type="select" :label="__('Your side in a debate')" :options="['' => __('Not decided yet'), 'proposition' => __('Proposition'), 'opposition' => __('Opposition')]" :value="$a['host_stance']" :restore-old-input="false" />
            @endif
            <x-m3.button type="submit">{{ __('Save the details') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif

    @if ($c['my_state'] === 'accepted')
    <x-m3.card :title="__('Your adjudicators')">
        <p>{{ __('Only your school sees who your adjudicators are. Staff you name here can record your school\'s result and ask for AI feedback on reasoning.') }}</p>
        <ul>@forelse ($a['adjudicators'] as $person)<li>{{ $person['name'] }}@if ($person['user_id'] === null) ({{ __('from outside the school') }})@endif</li>@empty<li>{{ __('No adjudicators named yet.') }}</li>@endforelse</ul>
        <form method="POST" action="{{ route('academic-competitions.adjudicators', ['competition' => $c['id']]) }}" aria-label="{{ __('Your adjudicators') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            @php($chosen = collect($a['adjudicators'])->pluck('user_id')->filter()->all())
            <fieldset><legend>{{ __('Staff') }}</legend>
                @foreach ($staff as $userId => $name)
                    <x-m3.checkbox name="staff[]" :value="$userId" :label="$name" :checked="in_array($userId, $chosen, true)" />
                @endforeach
            </fieldset>
            <x-page.field name="outside" id="adjudicators-outside" type="textarea" :label="__('Adjudicators from outside the school (one name a line)')" :value="collect($a['adjudicators'])->whereNull('user_id')->pluck('name')->implode(PHP_EOL)" :restore-old-input="false" maxlength="1300" />
            <x-m3.button type="submit">{{ __('Save the adjudicators') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif

    @if ($a['can_adjudicate'])
    @php($r = $a['result'])
    @php($rows = collect($r['entrants'] ?? [])->keyBy('learner_id'))
    <x-m3.card :title="__('Your adjudicators\' result')">
        <p>{{ __('Only a person records the result. Keep it as a draft until the adjudicators agree, then submit it. It counts once both schools submit the same result, or at once when the other school is not on NavigateEd. Your entrants\' scores, places and the written reasons stay with your school.') }}</p>
        @if ($r)<p>{{ $r['state'] === 'submitted' ? __('Submitted') : __('Draft') }}</p>@endif
        @if ($a['their_winner'])<p role="status">{{ __('The other school submitted a different result. Check with them, then submit the agreed result.') }}</p>@endif
        <form method="POST" action="{{ route('academic-competitions.result', ['competition' => $c['id']]) }}" aria-label="{{ __('Your adjudicators\' result') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="revision" value="{{ $r['revision'] ?? 0 }}">
            <x-page.field name="host_score" id="result-host" type="number" :label="__('Score: :school', ['school' => $c['host']['name']])" :value="$r['host_score'] ?? ''" :restore-old-input="false" min="0" max="9999" required />
            <x-page.field name="guest_score" id="result-guest" type="number" :label="__('Score: :school', ['school' => $c['guest']['name']])" :value="$r['guest_score'] ?? ''" :restore-old-input="false" min="0" max="9999" required />
            <x-page.field name="winner" id="result-winner" type="select" :label="__('Winner')" :options="['host' => $c['host']['name'], 'guest' => $c['guest']['name']] + ($a['kind'] === 'debate' ? [] : ['draw' => __('A draw')])" :value="$r['winner'] ?? ''" :restore-old-input="false" required />
            @if ($entrants !== [])
            <div class="page-table__scroll" role="region" aria-label="{{ __('Your entrants') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Your entrants') }}</caption>
                <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Score') }}</th><th scope="col">{{ __('Place') }}</th></tr></thead>
                <tbody>
                @foreach ($entrants as $learnerId => $name)
                    <tr><td>{{ $name }}</td>
                        <td><x-page.field :name="'entrant_score['.$learnerId.']'" :id="'entrant-score-'.$learnerId" type="number" :label="__('Score')" :value="$rows[$learnerId]['score'] ?? ''" :restore-old-input="false" min="0" max="9999" /></td>
                        <td><x-page.field :name="'entrant_place['.$learnerId.']'" :id="'entrant-place-'.$learnerId" type="number" :label="__('Place')" :value="$rows[$learnerId]['place'] ?? ''" :restore-old-input="false" min="1" max="999" /></td></tr>
                @endforeach
                </tbody></table></div>
            @endif
            <x-page.field name="reasons" id="result-reasons" type="textarea" :label="__('Adjudicators\' reasons (only your school)')" :value="$r['reasons'] ?? ''" :restore-old-input="false" maxlength="4000" />
            <x-page.field name="state" id="result-state" type="select" :label="__('Save as')" :options="['draft' => __('Draft'), 'final' => __('Submit the result')]" :restore-old-input="false" required />
            <x-m3.button type="submit">{{ __('Save the result') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif

    @if ($a['reasoning_feedback'] && $c['my_state'] === 'accepted')
    <x-m3.card :title="__('AI feedback on reasoning')">
        <p>{{ __('Advice for you as adjudicator on the structure, use of evidence, rebuttal and any logical fallacies in one speech or written argument. It never decides or changes a result and is never shown to learners or families. Names are removed before the text is sent. Paste text only, never a learner\'s name.') }}</p>
        <form method="POST" action="{{ route('academic-competitions.review', ['competition' => $c['id']]) }}" aria-label="{{ __('Ask for AI feedback on reasoning') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <x-page.field name="source" id="review-source" type="select" :label="__('The text is')" :options="['speech' => __('A speech transcript'), 'written' => __('A written argument')]" :restore-old-input="false" required />
            <x-page.field name="from_side" id="review-side" type="select" :label="__('From')" :options="['ours' => __('One of our entrants'), 'theirs' => __('The other school\'s entrant')]" :restore-old-input="false" required />
            <x-page.field name="text" id="review-text" type="textarea" :label="__('Text')" :restore-old-input="false" minlength="50" maxlength="12000" required />
            <x-m3.button type="submit">{{ __('Ask for feedback') }}</x-m3.button>
        </form>
    </x-m3.card>
    @endif

    @if ($a['reviews'] !== [])
    <x-m3.card :title="__('Feedback you asked for')">
        @foreach ($a['reviews'] as $review)
            <section aria-label="{{ __('Feedback :number', ['number' => $review['id']]) }}">
                <h3>{{ $review['source'] === 'speech' ? __('Speech transcript') : __('Written argument') }} · {{ $review['from_side'] === 'ours' ? __('our entrant') : __('the other school\'s entrant') }}</h3>
                @if ($review['state'] === 'completed')
                    <dl>
                        <dt>{{ __('Structure') }}</dt><dd>{{ $review['feedback']['structure'] }}</dd>
                        <dt>{{ __('Use of evidence') }}</dt><dd>{{ $review['feedback']['evidence'] }}</dd>
                        <dt>{{ __('Rebuttal') }}</dt><dd>{{ $review['feedback']['rebuttal'] }}</dd>
                        <dt>{{ __('Logical fallacies') }}</dt><dd>@forelse ($review['feedback']['fallacies'] as $fallacy){{ $fallacy['name'] }}: {{ $fallacy['explanation'] }}@if (! $loop->last)<br>@endif @empty{{ __('None noted') }}@endforelse</dd>
                        <dt>{{ __('Summary') }}</dt><dd>{{ $review['feedback']['summary'] }}</dd>
                    </dl>
                    @if ($review['shared'])
                        <p>{{ __('You shared feedback with :name.', ['name' => $entrants[$review['shared']['learner_id']] ?? __('your entrant')]) }}</p>
                    @elseif ($review['from_side'] === 'ours' && $entrants !== [])
                        <form method="POST" action="{{ route('academic-competitions.share', ['competition' => $c['id'], 'review' => $review['id']]) }}" aria-label="{{ __('Share your feedback with your entrant') }}">
                            @csrf
                            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <p>{{ __('Write the feedback in your own words. You are responsible for what you share. The learner and their family see it with the competition.') }}</p>
                            <x-page.field name="learner_id" :id="'share-learner-'.$review['id']" type="select" :label="__('Entrant')" :options="$entrants" :restore-old-input="false" required />
                            <x-page.field name="text" :id="'share-text-'.$review['id']" type="textarea" :label="__('Your feedback')" :restore-old-input="false" minlength="20" maxlength="4000" required />
                            <x-m3.button type="submit" variant="tonal">{{ __('Share with the entrant') }}</x-m3.button>
                        </form>
                    @endif
                @elseif ($review['state'] === 'quarantined')
                    <p>{{ __('This text was held for your school\'s safeguarding reviewers. No feedback was written.') }}</p>
                @elseif ($review['state'] === 'unavailable')
                    <p>{{ __('Feedback could not be written this time. No result was changed.') }}</p>
                @else
                    <p>{{ __('Waiting for feedback.') }}</p>
                @endif
            </section>
        @endforeach
    </x-m3.card>
    @endif

    @if ($a['can_award'])
    <x-m3.card :title="__('Awards for your entrants')">
        <p>{{ __('Present one of your school\'s awards to your own entrants, for taking part or for a placing. The awards module\'s own rules apply.') }}</p>
        @if ($a['awards'] !== [])
            <ul>@foreach ($a['awards'] as $award)<li>{{ $entrants[$award['learner_id']] ?? __('Entrant') }}: {{ $award['title'] }} ({{ $award['basis'] === 'placing' ? __('placing') : __('taking part') }})</li>@endforeach</ul>
        @endif
        @if ($definitions === [] || $entrants === [])
            <p>{{ __('Add entrants and an active award definition first.') }}</p>
        @else
        <form method="POST" action="{{ route('academic-competitions.awards', ['competition' => $c['id']]) }}" aria-label="{{ __('Awards for your entrants') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
            <x-page.field name="award_definition_id" id="award-definition" type="select" :label="__('Award')" :options="$definitions" :restore-old-input="false" required />
            <x-page.field name="basis" id="award-basis" type="select" :label="__('For')" :options="['participation' => __('Taking part'), 'placing' => __('A placing')]" :restore-old-input="false" required />
            <x-page.field name="presented_on" id="award-date" type="date" :label="__('Presented on')" :restore-old-input="false" required />
            <fieldset><legend>{{ __('Entrants') }}</legend>
                @foreach ($entrants as $learnerId => $name)
                    <x-m3.checkbox name="learner_ids[]" :value="$learnerId" :label="$name" />
                @endforeach
            </fieldset>
            <x-m3.button type="submit">{{ __('Present the award') }}</x-m3.button>
        </form>
        @endif
    </x-m3.card>
    @endif
</div>
@endsection
