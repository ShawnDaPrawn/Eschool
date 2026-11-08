@extends('saas.layout')

@section('title', __('Marking accuracy'))

@php
    $percent = fn ($value) => $value === null ? '—' : number_format((float) $value, 1).'%';
@endphp

@section('content')
<h1>{{ __('Marking accuracy') }}</h1>
<p class="saas-lead">{{ __('Learners receive automatic marking in a subject only after the marking engine agrees with teachers on enough de-identified, teacher-marked scripts, and an approver switches the subject on. A new engine or profile version needs a new passing run. Teachers\' own memo preview is not affected.') }}</p>

@unless ($settings['owner_confirmed'])
    <p class="saas-card" role="status"><span class="saas-badge saas-badge--warn">{{ __('Placeholders') }}</span> {{ __('The threshold, tolerance and minimum number of scripts below are placeholders until the owner confirms them. Runs are measured against them, but no subject can be switched on until then.') }}</p>
@endunless

<h2>{{ __('What a run must reach') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('What a run must reach') }}" tabindex="0">
    <table class="saas-table">
        <tbody>
            <tr><th scope="row">{{ __('Engine version') }}</th><td>{{ $engine_version }}</td></tr>
            <tr><th scope="row">{{ __('Fewest teacher-marked scripts') }}</th><td>{{ $settings['min_scripts'] }}</td></tr>
            <tr><th scope="row">{{ __('Mark point tolerance') }}</th><td>{{ __(':marks marks', ['marks' => $settings['point_marks']]) }}</td></tr>
            <tr><th scope="row">{{ __('Script total tolerance') }}</th><td>{{ __(':percent% of the script\'s marks', ['percent' => $settings['script_percent']]) }}</td></tr>
            <tr><th scope="row">{{ __('Mark points that must agree') }}</th><td>{{ $settings['point_agreement_percent'] }}%</td></tr>
            <tr><th scope="row">{{ __('Script totals that must agree') }}</th><td>{{ $settings['script_agreement_percent'] }}%</td></tr>
            <tr><th scope="row">{{ __('Largest systematic bias') }}</th><td>{{ $settings['max_bias_percent'] }}%</td></tr>
        </tbody>
    </table>
</div>

@if ($report)
    <div class="saas-card" role="status" aria-label="{{ __('Import result') }}">
        <h2>{{ ($report['dry_run'] ?? false) ? __('Dry run result') : __('Import result') }}</h2>
        @php($summary = ($report['summary'] ?? []) + ['code' => '', 'questions' => 0, 'points' => 0, 'max_marks' => '', 'scripts' => 0, 'paper' => ''])
        @if ($report['ok'] ?? false)
            <p>
                @if ($report['kind'] === 'paper')
                    {{ $report['dry_run'] ? __('The paper :code is ready to import: :questions questions, :points mark points, :marks marks. Nothing was saved.', ['code' => $summary['code'], 'questions' => $summary['questions'], 'points' => $summary['points'], 'marks' => $summary['max_marks']])
                        : __('The paper :code was imported.', ['code' => $summary['code']]) }}
                @else
                    {{ $report['dry_run'] ? __(':count scripts for :paper are ready to import. Nothing was saved.', ['count' => $summary['scripts'], 'paper' => $summary['paper']])
                        : __(':count scripts for :paper were imported.', ['count' => $summary['scripts'], 'paper' => $summary['paper']]) }}
                @endif
            </p>
        @else
            <p>{{ __('Nothing was imported. Fix these problems and try again:') }}</p>
            <ul>@foreach ($report['errors'] ?? [] as $message)<li>{{ $message }}</li>@endforeach</ul>
        @endif
    </div>
@endif

<h2>{{ __('Subjects') }}</h2>
@foreach ($subjects as $subject)
    @php($gate = $subject['gate'])
    <section class="saas-card" aria-labelledby="subject-{{ $subject['key'] }}">
        <h3 id="subject-{{ $subject['key'] }}">{{ $subject['label'] }}
            @if ($gate['open'])<span class="saas-badge">{{ __('Learner marking on') }}</span>
            @elseif ($gate['stale'])<span class="saas-badge saas-badge--warn">{{ __('Switched on, but closed: the versions changed') }}</span>
            @elseif ($gate['settings_changed'])<span class="saas-badge saas-badge--warn">{{ __('Switched on, but closed: the acceptance settings changed') }}</span>
            @elseif ($gate['enabled'])<span class="saas-badge saas-badge--warn">{{ __('Switched on, but closed: the latest run did not pass') }}</span>
            @else<span class="saas-badge saas-badge--warn">{{ __('Learner marking off') }}</span>@endif
        </h3>
        <p>{{ __('Profile version :version. :papers papers, :scripts teacher-marked scripts.', ['version' => $subject['profile_version'], 'papers' => $subject['papers'], 'scripts' => $subject['scripts']]) }}</p>
        @if ($subject['latest'])
            <p>{{ __('Latest run on the current versions: :result, :scripts scripts, mark points :points agree, script totals :totals agree.', ['result' => $subject['latest']['passed'] ? __('passed') : __('did not pass'), 'scripts' => $subject['latest']['scripts'], 'points' => $percent($subject['latest']['point_agreement']), 'totals' => $percent($subject['latest']['script_agreement'])]) }}
                <a href="{{ route('saas.marking-accuracy.runs.show', $subject['latest']['id']) }}">{{ __('See the run') }}</a></p>
        @else
            <p>{{ __('No run yet on the current engine and profile versions.') }}</p>
        @endif
        @if ($gate['enabled'] && $gate['approved_at'])
            <p>{{ __('Switched on :date against run :run (engine :engine, profile :profile).', ['date' => $gate['approved_at']->format('Y-m-d H:i'), 'run' => $gate['run_id'], 'engine' => $gate['engine_version'], 'profile' => $gate['profile_version']]) }}</p>
        @endif
        @if ($gate['blockers'] !== [] && ! $gate['open'])
            <ul>@foreach ($gate['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>
        @endif
        <div class="saas-actions">
            @if ($can_evaluate)
                <form method="POST" action="{{ route('saas.marking-accuracy.runs.store', $subject['key']) }}">
                    @csrf
                    <button class="saas-btn" type="submit">{{ __('Run the evaluation now') }}</button>
                </form>
            @endif
        </div>
        @if ($can_approve && ! $gate['open'] && $gate['eligible_run_id'])
            <form method="POST" action="{{ route('saas.marking-accuracy.gate.on', $subject['key']) }}" aria-label="{{ __('Switch learner marking on') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="run_id" value="{{ $gate['eligible_run_id'] }}">
                <input type="hidden" name="expected_revision" value="{{ $gate['revision'] }}">
                <div class="saas-field">
                    <label for="on-reason-{{ $subject['key'] }}">{{ __('Why you are switching it on') }}</label>
                    <input id="on-reason-{{ $subject['key'] }}" name="reason" required minlength="10" maxlength="500">
                </div>
                <div class="saas-field">
                    <label><input type="checkbox" name="confirmed" value="1" required> {{ __('I reviewed run :run and its worst mark points.', ['run' => $gate['eligible_run_id']]) }}</label>
                </div>
                <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Switch learner marking on') }}</button></div>
            </form>
        @endif
        @if ($gate['enabled'])
            <form method="POST" action="{{ route('saas.marking-accuracy.gate.off', $subject['key']) }}" aria-label="{{ __('Switch learner marking off') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="expected_revision" value="{{ $gate['revision'] }}">
                <div class="saas-field">
                    <label for="off-reason-{{ $subject['key'] }}">{{ __('Why you are switching it off') }}</label>
                    <input id="off-reason-{{ $subject['key'] }}" name="reason" required minlength="10" maxlength="500">
                </div>
                <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Switch learner marking off') }}</button></div>
            </form>
        @endif
    </section>
@endforeach

<h2>{{ __('Recent runs') }}</h2>
@if ($runs === [])
    <p class="saas-card">{{ __('No runs yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Recent runs') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Run') }}</th><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Versions') }}</th><th scope="col">{{ __('Scripts') }}</th><th scope="col">{{ __('Mark points agree') }}</th><th scope="col">{{ __('Script totals agree') }}</th><th scope="col">{{ __('Bias') }}</th><th scope="col">{{ __('Result') }}</th></tr></thead>
        <tbody>
        @foreach ($runs as $run)
            <tr>
                <th scope="row"><a href="{{ route('saas.marking-accuracy.runs.show', $run['id']) }}">{{ $run['id'] }}</a></th>
                <td>{{ $run['label'] }}</td>
                <td>{{ $run['engine_version'] }} · {{ $run['profile_version'] }}</td>
                <td>{{ $run['scripts'] }}</td>
                <td>{{ $percent($run['point_agreement']) }}</td>
                <td>{{ $percent($run['script_agreement']) }}</td>
                <td>{{ $percent($run['bias_percent']) }}</td>
                <td><span class="saas-badge {{ $run['passed'] ? '' : 'saas-badge--warn' }}">{{ $run['passed'] ? __('Passed') : __('Did not pass') }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2>{{ __('Evaluation papers') }}</h2>
@if ($papers === [])
    <p class="saas-card">{{ __('No evaluation papers yet. Import one below.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Evaluation papers') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Code') }}</th><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Title') }}</th><th scope="col">{{ __('Source') }}</th><th scope="col">{{ __('Questions') }}</th><th scope="col">{{ __('Marks') }}</th><th scope="col">{{ __('Scripts') }}</th></tr></thead>
        <tbody>
        @foreach ($papers as $paper)
            <tr><th scope="row">{{ $paper['code'] }}</th><td>{{ $paper['subject'] }}</td><td>{{ $paper['title'] }}</td><td>{{ $paper['source'] }}</td><td>{{ $paper['questions'] }}</td><td>{{ $paper['max_marks'] }}</td><td>{{ $paper['scripts'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($can_evaluate)
    @if ($papers !== [])
    <form method="POST" action="{{ route('saas.marking-accuracy.scripts.store') }}" enctype="multipart/form-data" class="saas-card" aria-label="{{ __('Import scripts') }}">
        @csrf
        <h3>{{ __('Import scripts') }}</h3>
        <p>{{ __('De-identified, teacher-marked scripts only: no names, learner or school numbers, contact details or anything else that identifies a person or a school. The reference is the source\'s own anonymous script code; only a keyed hash of it is kept. CSV: one row per answered question, with the columns reference, marker, source, question, answer and marks (the teacher\'s mark for each mark point in order, separated by semicolons), and optionally question_total. JSON: {"scripts": [{"reference", "marker", "source", "total", "questions": [{"number", "answer", "marks": []}]}]}.') }}</p>
        <div class="saas-grid">
            <div class="saas-field">
                <label for="scripts-paper">{{ __('Paper') }}</label>
                <select id="scripts-paper" name="paper_id" required>@foreach ($papers as $paper)<option value="{{ $paper['id'] }}">{{ $paper['code'] }}</option>@endforeach</select>
            </div>
            <div class="saas-field">
                <label for="scripts-format">{{ __('Format') }}</label>
                <select id="scripts-format" name="format" required><option value="csv">CSV</option><option value="json">JSON</option></select>
            </div>
            <div class="saas-field">
                <label for="scripts-file">{{ __('File') }}</label>
                <input type="file" id="scripts-file" name="file" required accept=".csv,.json,.txt,text/csv,application/json">
            </div>
            <div class="saas-field">
                <input type="hidden" name="dry_run" value="0">
                <label><input type="checkbox" name="dry_run" value="1" checked> {{ __('Dry run: check everything and save nothing') }}</label>
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Check or import the scripts') }}</button></div>
    </form>
    @endif

    <form method="POST" action="{{ route('saas.marking-accuracy.papers.store') }}" enctype="multipart/form-data" class="saas-card" aria-label="{{ __('Import a paper') }}">
        @csrf
        <h3>{{ __('Import a paper') }}</h3>
        <p>{{ __('A JSON file with the subject, a code, a title, the source and its questions, each with its marks and mark points in the memo editor\'s shape (earns, type, marks, rule_kind, rule_value, rule_tolerance, rule_unit). Papers do not change once imported: a corrected memo gets a new code.') }}</p>
        <div class="saas-grid">
            <div class="saas-field">
                <label for="paper-file">{{ __('File') }}</label>
                <input type="file" id="paper-file" name="file" required accept=".json,application/json">
            </div>
            <div class="saas-field">
                <input type="hidden" name="dry_run" value="0">
                <label><input type="checkbox" name="dry_run" value="1" checked> {{ __('Dry run: check everything and save nothing') }}</label>
            </div>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Check or import the paper') }}</button></div>
    </form>
@endif
@endsection
