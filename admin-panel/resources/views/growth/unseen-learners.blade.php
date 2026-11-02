@extends('layouts.master')
@section('title', __('No child unseen'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/progress-notes.css') }}">@endsection
@section('content')
@php
    $kindLabels = ['progress_note' => __('Progress notes'), 'award' => __('Awards'), 'house_points' => __('House points'), 'peer_tutoring' => __('Peer tutoring')];
    $kinds = fn (array $list) => $list === [] ? __('None this year') : implode(', ', array_map(fn ($k) => $kindLabels[$k] ?? $k, $list));
    $since = fn (array $row) => $row['last_contact_on'] === null ? __('No positive contact recorded this year') : trans_choice(':count school day since the last positive contact|:count school days since the last positive contact', $row['school_days_since'], ['count' => $row['school_days_since']]);
    $leads = $access['principal'] || $access['grade_ids'] !== [];
@endphp
<div class="content-wrapper progress-notes">
    <h1>{{ __('No child unseen') }}</h1>
    <p>{{ __('Learners with no recorded positive contact from any adult in the last :days school days. Progress notes (never counselling notes), award nominations and presentations, awarded house points and completed peer tutoring sessions count. A source that is switched off or not used stays unknown. This is a prompt for staff, never a label for the learner, and families never see it.', ['days' => $schoolDays]) }}</p>
    @if ($errors->any())<div role="alert" class="m3 m3-banner m3-banner--warning"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if (session('success'))<p role="status" class="m3 m3-banner m3-banner--success">{{ session('success') }}</p>@endif
    <nav class="note-tabs" aria-label="{{ __('Intervention workbench') }}"><a href="{{ route('risk-interventions.index') }}">{{ __('Early warning and interventions') }}</a><a href="{{ route('risk-interventions.unseen') }}" aria-current="page">{{ __('No child unseen') }}</a></nav>

    @if ($mine !== [])
        <section class="note-card"><h2>{{ __('Learners you were asked to check in with') }}</h2>
            @foreach ($mine as $row)
                <article class="note-list-item"><h3>{{ $row['name'] }}</h3>
                    <p>{{ $row['grade'] }} · {{ $since($row) }}</p>
                    <p>{{ __('Kinds of contact this year') }}: {{ $kinds($row['kinds']) }}</p>
                    @if ($row['status'] === 'seen')<p>{{ __('A positive contact has been recorded. This request closes automatically.') }}</p>@endif
                </article>
            @endforeach
        </section>
    @endif

    @if ($leads && $list !== null)
        <section class="note-card"><h2>{{ __('Choose the learners to review') }}</h2>
            <form method="get" action="{{ route('risk-interventions.unseen') }}" class="note-filters">
                <label for="unseen-grade">{{ __('Grade') }}<select id="unseen-grade" name="grade">
                    <option value="" @selected($grade === null)>{{ $access['principal'] ? __('Whole school') : __('All my grades') }}</option>
                    @foreach ($access['grades'] as $g)<option value="{{ $g['id'] }}" @selected($grade === $g['id'])>{{ $g['name'] }}</option>@endforeach
                </select></label>
                <button type="submit" class="m3-button m3-button--outlined">{{ __('Show') }}</button>
            </form>
        </section>

        <section class="note-card"><h2>{{ __('Learners with no recent positive contact') }}</h2>
            @if ($list['window_start'] === null)
                <p>{{ __('Unknown: there are not yet :days school days this year.', ['days' => $list['school_days']]) }}</p>
            @elseif ($list['sources'] === [])
                <p>{{ __('Unknown: no source of positive contact was used in the last :days school days, so no learner is flagged.', ['days' => $list['school_days']]) }}</p>
            @else
                <p>{{ __('Counted since :date: :sources.', ['date' => $list['window_start'], 'sources' => $kinds($list['sources'])]) }}</p>
                <p>{{ trans_choice(':count of :roster learners has no recent positive contact.|:count of :roster learners have no recent positive contact.', $list['total'], ['count' => $list['total'], 'roster' => $list['roster']]) }}</p>
            @endif
            @foreach ($list['learners'] as $row)
                <article class="note-list-item"><h3>{{ $row['name'] }}</h3>
                    <p>{{ $row['grade'] }} · {{ $since($row) }}</p>
                    <p>{{ __('Kinds of contact this year') }}: {{ $kinds($row['kinds']) }}</p>
                    @if ($row['adult'] !== null)<p>{{ __('Named adult: :name, since :date', ['name' => $row['adult']['name'], 'date' => $row['adult']['assigned_on']]) }}</p>@endif
                    @if ($canWrite)
                        <form method="post" action="{{ route('risk-interventions.unseen.assign', ['learner' => $row['learner_id']]) }}" class="note-filters">@csrf
                            <input type="hidden" name="expected_account_id" value="{{ $actorId }}">
                            <input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                            @if ($grade !== null)<input type="hidden" name="grade" value="{{ $grade }}">@endif
                            <label for="unseen-adult-{{ $row['learner_id'] }}">{{ $row['adult'] === null ? __('Name an adult to reach out') : __('Name a different adult') }}
                                <select id="unseen-adult-{{ $row['learner_id'] }}" name="adult_user_id" required><option value="">{{ __('Choose a staff member') }}</option>@foreach ($staff as $person)<option value="{{ $person['id'] }}" @selected($row['adult'] !== null && $row['adult']['id'] === $person['id'])>{{ $person['name'] }}</option>@endforeach</select>
                            </label>
                            <button type="submit" class="m3-button m3-button--filled">{{ __('Save named adult') }}</button>
                        </form>
                    @endif
                </article>
            @endforeach
            @if ($list['last_page'] > 1)<nav class="note-pages" aria-label="{{ __('Learner pages') }}">@if ($list['page'] > 1)<a href="{{ route('risk-interventions.unseen', array_filter(['grade' => $grade, 'page' => $list['page'] - 1])) }}">{{ __('Previous') }}</a>@endif @if ($list['page'] < $list['last_page'])<a href="{{ route('risk-interventions.unseen', array_filter(['grade' => $grade, 'page' => $list['page'] + 1])) }}">{{ __('Next') }}</a>@endif</nav>@endif
        </section>
    @endif

    @if ($access['principal'] && $canWrite)
        <section class="note-card"><h2>{{ __('School days without contact') }}</h2>
            <form method="post" action="{{ route('risk-interventions.unseen.configure') }}" class="note-filters">@csrf
                <input type="hidden" name="expected_account_id" value="{{ $actorId }}">
                <input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                <label for="unseen-days">{{ __('Flag a learner after this many school days without a positive contact') }}<input id="unseen-days" name="school_days" type="number" min="{{ \App\Services\Growth\PositiveContacts::MIN_SCHOOL_DAYS }}" max="{{ \App\Services\Growth\PositiveContacts::MAX_SCHOOL_DAYS }}" value="{{ $schoolDays }}" required></label>
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save') }}</button>
            </form>
        </section>
    @endif
</div>
@endsection
