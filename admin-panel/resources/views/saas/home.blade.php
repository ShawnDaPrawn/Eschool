@extends('saas.layout')

@section('title', __('Home'))

@section('content')
@php
    $plural = fn (string $choice, int $count) => trans_choice($choice, $count, ['count' => $count]);
@endphp
<h1>{{ __('Home') }}</h1>
<p class="saas-lead">{{ __('Where things stand across every school. Counts only: no learner, parent or staff record is shown here. You see the areas your role can open.') }}</p>

<div class="saas-home">
@isset($tiles['stages'])
    @php $tile = $tiles['stages']; @endphp
    <section class="saas-card saas-tile" data-hq-tile="stages" aria-labelledby="tile-stages">
        <h2 id="tile-stages">{{ __('Schools by stage') }}</h2>
        <p class="saas-tile__figure">{{ $tile['total'] }} <span>{{ $plural('{1} school|[0,*] schools', $tile['total']) }}</span></p>
        <dl class="saas-tile__counts">
            @foreach ($tile['stages'] as $stage => $count)
                <div><dt>{{ __('stage_'.$stage) }}</dt><dd>{{ $count }}</dd></div>
            @endforeach
        </dl>
        @if ($tile['statuses']['suspended'] + $tile['statuses']['archived'] > 0)
            <p><span class="saas-badge saas-badge--warn">{{ __(':suspended suspended, :archived archived', ['suspended' => $tile['statuses']['suspended'], 'archived' => $tile['statuses']['archived']]) }}</span></p>
        @endif
        <p class="saas-tile__link"><a href="{{ route('saas.index') }}">{{ __('All schools') }}</a></p>
    </section>
@endisset

@isset($tiles['at_risk'])
    @php $tile = $tiles['at_risk']; @endphp
    <section class="saas-card saas-tile" data-hq-tile="at_risk" aria-labelledby="tile-at-risk">
        <h2 id="tile-at-risk">{{ __('Schools at risk') }}</h2>
        <p class="saas-tile__figure">{{ $tile['count'] }} <span>{{ $plural('{1} school marked at risk|[0,*] schools marked at risk', $tile['count']) }}</span></p>
        @if ($tile['schools'] === [])
            <p>{{ __('No school is marked at risk.') }}</p>
        @else
            <ul class="saas-tile__list">
                @foreach ($tile['schools'] as $school)
                    <li><a href="{{ route('saas.schools.show', $school['id']) }}">{{ $school['name'] }}</a></li>
                @endforeach
            </ul>
        @endif
        <p class="saas-tile__link"><a href="{{ route('saas.index') }}">{{ __('All schools') }}</a></p>
    </section>
@endisset

@isset($tiles['onboarding'])
    @php $tile = $tiles['onboarding']; @endphp
    <section class="saas-card saas-tile saas-tile--wide" data-hq-tile="onboarding" aria-labelledby="tile-onboarding">
        <h2 id="tile-onboarding">{{ __('Onboarding blockers') }}</h2>
        <p class="saas-tile__figure">{{ $tile['count'] }} <span>{{ $plural('{1} school contracted or onboarding|[0,*] schools contracted or onboarding', $tile['count']) }}</span></p>
        @if ($tile['schools'] === [])
            <p>{{ __('No active school is being set up.') }}</p>
        @else
            <ul class="saas-tile__list">
                @foreach ($tile['schools'] as $school)
                    <li>
                        <a href="{{ route('saas.schools.onboarding', $school['id']) }}">{{ $school['name'] }}</a>
                        · {{ __(':done of :total steps done', ['done' => $school['done'], 'total' => $school['total']]) }}
                        @if ($school['next'] !== null)
                            · <span class="saas-badge saas-badge--warn">{{ __('Next: :step', ['step' => $school['next']]) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($tile['more'] > 0)
                <p>{{ $plural('{1} One more school is being set up.|[2,*] :count more schools are being set up.', $tile['more']) }}</p>
            @endif
        @endif
        <p class="saas-tile__link"><a href="{{ route('saas.index') }}">{{ __('All schools') }}</a></p>
    </section>
@endisset

@isset($tiles['incidents'])
    @php $tile = $tiles['incidents']; @endphp
    <section class="saas-card saas-tile" data-hq-tile="incidents" aria-labelledby="tile-incidents">
        <h2 id="tile-incidents">{{ __('Open incidents') }}</h2>
        <p class="saas-tile__figure">{{ $tile['open'] + $tile['contained'] }} <span>{{ $plural('{1} incident not closed|[0,*] incidents not closed', $tile['open'] + $tile['contained']) }}</span></p>
        <dl class="saas-tile__counts">
            <div><dt>{{ __('Open') }}</dt><dd>{{ $tile['open'] }}</dd></div>
            <div><dt>{{ __('Contained') }}</dt><dd>{{ $tile['contained'] }}</dd></div>
            <div><dt>{{ __('High or critical') }}</dt><dd>{{ $tile['serious'] }}</dd></div>
            <div><dt>{{ __('Need a notification') }}</dt><dd>{{ $tile['attention'] }}</dd></div>
        </dl>
        @if ($tile['oldest_days'] !== null)
            <p>{{ $plural('{0} The oldest was detected today.|{1} The oldest has been open 1 day.|[2,*] The oldest has been open :count days.', $tile['oldest_days']) }}</p>
        @endif
        <p class="saas-tile__link"><a href="{{ route('saas.incidents.index') }}">{{ __('Incident register') }}</a></p>
    </section>
@endisset

@isset($tiles['lurits'])
    @php $tile = $tiles['lurits']; @endphp
    <section class="saas-card saas-tile" data-hq-tile="lurits" aria-labelledby="tile-lurits">
        <h2 id="tile-lurits">{{ __('LURITS claims') }}</h2>
        <p class="saas-tile__figure">{{ $tile['open'] }}{{ $tile['capped'] ? '+' : '' }} <span>{{ $plural('{1} claim waiting|[0,*] claims waiting', $tile['open']) }}</span></p>
        <p>{{ __('LURITS numbers claimed by two schools, waiting for support to close.') }}</p>
        <p class="saas-tile__link"><a href="{{ route('saas.lurits') }}">{{ __('LURITS claims') }}</a></p>
    </section>
@endisset

@isset($tiles['ai'])
    @php $tile = $tiles['ai']; @endphp
    <section class="saas-card saas-tile" data-hq-tile="ai" aria-labelledby="tile-ai">
        <h2 id="tile-ai">{{ __('AI') }}</h2>
        <p class="saas-tile__figure">{{ $tile['flags_open'] + $tile['flags_escalated'] }} <span>{{ $plural('{1} safeguarding flag waiting|[0,*] safeguarding flags waiting', $tile['flags_open'] + $tile['flags_escalated']) }}</span></p>
        <dl class="saas-tile__counts">
            <div><dt>{{ __('Flags open') }}</dt><dd>{{ $tile['flags_open'] }}</dd></div>
            <div><dt>{{ __('Flags escalated') }}</dt><dd>{{ $tile['flags_escalated'] }}</dd></div>
            <div><dt>{{ __('Schools at today\'s cap') }}</dt><dd>{{ $tile['over_cap'] }}</dd></div>
            <div><dt>{{ __('Schools with AI paused') }}</dt><dd>{{ $tile['paused'] }}</dd></div>
        </dl>
        <p>@if ($tile['platform_on']){{ __('AI is switched on for the platform.') }}@else<span class="saas-badge saas-badge--warn">{{ __('AI is switched off for the platform.') }}</span>@endif</p>
        <p class="saas-tile__link"><a href="{{ route('saas.ai') }}">{{ __('AI control centre') }}</a></p>
    </section>
@endisset

@isset($tiles['health'])
    @php $tile = $tiles['health']; @endphp
    <section class="saas-card saas-tile saas-tile--wide" data-hq-tile="health" aria-labelledby="tile-health">
        <h2 id="tile-health">{{ __('Platform health') }}</h2>
        <dl class="saas-tile__counts">
            <div><dt>{{ __('Jobs waiting') }}</dt><dd>{{ $tile['backlog'] ?? __('Not measured') }}</dd></div>
            <div><dt>{{ __('Oldest wait (minutes)') }}</dt><dd>{{ $tile['oldest_wait_minutes'] ?? __('Not measured') }}</dd></div>
            <div><dt>{{ __('Failed jobs, last 24 hours') }}</dt><dd>{{ $tile['failed_jobs']['since'] }}</dd></div>
            <div><dt>{{ __('Failed jobs kept') }}</dt><dd>{{ $tile['failed_jobs']['kept'] }}</dd></div>
        </dl>
        @if ($tile['stalled'])
            <p><span class="saas-badge saas-badge--warn">{{ __('The queue worker is not keeping up.') }}</span></p>
        @elseif ($tile['backlog'] === null)
            <p>{{ __('The queue is not the database queue, so its backlog is not measured here.') }}</p>
        @endif
        <table class="saas-table">
            <caption>{{ __('Delivery problems in the last 24 hours, all schools') }}</caption>
            <thead>
            <tr>
                <th scope="col">{{ __('Channel') }}</th>
                <th scope="col" class="num">{{ __('Failed') }}</th>
                <th scope="col" class="num">{{ __('Bounced') }}</th>
                <th scope="col" class="num">{{ __('Complaints') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach (['mail' => __('Email'), 'push' => __('Push')] as $channel => $label)
                <tr>
                    <th scope="row">{{ $label }}</th>
                    <td class="num">{{ $tile['deliveries'][$channel]['failed'] }}</td>
                    <td class="num">{{ $tile['deliveries'][$channel]['bounced'] }}</td>
                    <td class="num">{{ $tile['deliveries'][$channel]['complained'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endisset
</div>
@endsection
