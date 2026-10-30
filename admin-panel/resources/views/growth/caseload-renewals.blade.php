@extends('layouts.master')
@section('title', __('Renew caseloads'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/progress-notes.css') }}">@endsection
@section('content')
@php
    $choices = ['' => __('Do not renew')] + $plan['professionals'];
@endphp
<div class="content-wrapper progress-notes">
    <h1>{{ __('Renew caseloads for :year', ['year' => $plan['to']->name]) }}</h1>
    <p>{{ __('Risk caseload grants end with the school year. Each open grant from :from is offered here: keep the same professional, choose another one with restricted pastoral access, or do not renew. Ended grants are not offered.', ['from' => $plan['from']->name]) }}</p>
    @if ($errors->any())<div role="alert" class="m3 m3-banner m3-banner--warning"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if (session('success'))<p role="status" class="m3 m3-banner m3-banner--success">{{ session('success') }}</p>@endif
    <a href="{{ route('risk-interventions.index', ['mode' => 'assignments']) }}">{{ __('Risk caseload assignments') }}</a>

    <section class="note-card"><h2>{{ __('Choose the year to renew into') }}</h2>
        <form method="get" action="{{ route('risk-interventions.renewals') }}" class="note-filters">
            <label for="renewal-year">{{ __('School year') }}<select id="renewal-year" name="year">@foreach ($years as $id => $name)<option value="{{ $id }}" @selected((int) $plan['to']->id === (int) $id)>{{ $name }}</option>@endforeach</select></label>
            <button type="submit" class="m3-button m3-button--outlined">{{ __('Show') }}</button>
        </form>
    </section>

    <section class="note-card"><h2>{{ __('Open caseloads from :from', ['from' => $plan['from']->name]) }}</h2>
        @if ($plan['unplaced'] > 0)<p>{{ trans_choice(':count open caseload is for a learner with no place in :year and is not offered.|:count open caseloads are for learners with no place in :year and are not offered.', $plan['unplaced'], ['count' => $plan['unplaced'], 'year' => $plan['to']->name]) }}</p>@endif
        @if ($plan['cases'] === [])
            <p>{{ __('There are no open caseloads for you to renew.') }}</p>
        @else
            <form method="post" action="{{ route('risk-interventions.renewals.store') }}">@csrf
                <input type="hidden" name="expected_account_id" value="{{ $actorId }}">
                <input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                <input type="hidden" name="year" value="{{ $plan['to']->id }}">
                <input type="hidden" name="fingerprint" value="{{ $plan['fingerprint'] }}">
                <p>{{ __('A renewed grant runs from :start to :end.', ['start' => $plan['starts_on'], 'end' => $plan['ends_on']]) }}</p>
                @if ($plan['waiting'] > 0)<p>{{ __('This page offers :count open caseloads at a time. Renew these, then the page offers the next :waiting.', ['count' => \App\Services\Growth\CaseloadRenewals::PER_SUBMISSION, 'waiting' => $plan['waiting']]) }}</p>@endif
                @foreach ($plan['cases'] as $case)
                    <article class="note-list-item">
                        <h3>{{ $case['learner'] }}</h3>
                        <p>{{ __('Last year: :name, :start to :end', ['name' => $case['professional'], 'start' => $case['starts_on'], 'end' => $case['ends_on']]) }}@if (! $case['still_eligible']) · {{ __('no longer has restricted pastoral access') }}@endif</p>
                        @if ($case['renewed'] !== [])
                            <p>{{ __('Already has a caseload grant in :year.', ['year' => $plan['to']->name]) }}</p>
                        @elseif (! $case['in_form'])
                            <p>{{ __('Offered once the cases above are renewed.') }}</p>
                        @else
                            <label for="renewal-case-{{ $case['id'] }}">{{ __('Holds the caseload in :year', ['year' => $plan['to']->name]) }}
                                <select id="renewal-case-{{ $case['id'] }}" name="cases[{{ $case['id'] }}]">@foreach ($choices as $id => $name)<option value="{{ $id }}" @selected($case['still_eligible'] && (string) $id === (string) $case['professional_id'])>{{ $name }}</option>@endforeach</select>
                            </label>
                        @endif
                    </article>
                @endforeach
                {{-- Last on purpose: if the server drops trailing fields, this goes first and nothing is renewed. --}}
                <input type="hidden" name="complete" value="1">
                <button type="submit" class="m3-button m3-button--filled">{{ __('Renew the chosen caseloads') }}</button>
            </form>
        @endif
    </section>
</div>
@endsection
