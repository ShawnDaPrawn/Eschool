@extends('layouts.master')
@section('title', __('Mastery impact'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/leadership-dashboard.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content leadership-dashboard">
<x-page.header :title="__('Mastery impact')" :subtitle="__('Topic mastery at the start of the term and now, by grade')" />
<p>{{ __('Counts of learners only. No learner is named or ranked. Start and end are compared within one mastery model version, from the teacher view of marks and practice.') }}</p>
<p>{{ __('A learner without evidence is counted as having no evidence yet, never as a zero. Where the start of the term was not recorded, the start is unknown.') }}</p>
@if($report['scope'] === 'grades')<p>{{ __('You see the grades you lead as grade head.') }}</p>@endif
@if($report['terms'] !== [])
<form method="GET" action="{{ route('mastery-impact.index') }}" class="leadership-filters" aria-label="{{ __('Choose term and model') }}">
<x-page.field id="impact-term" name="term_id" type="select" :label="__('Term')" :options="collect($report['terms'])->mapWithKeys(fn ($t) => [$t['id'] => $t['name'].' ('.$t['starts_on'].' – '.$t['ends_on'].')'])->all()" :value="$report['term']['id'] ?? null" required />
@if(count($report['versions']) > 1)
<x-page.field id="impact-version" name="policy_version" type="select" :label="__('Mastery model version')" :options="array_combine($report['versions'], $report['versions'])" :value="$report['policy_version']" required />
@endif
<button type="submit" class="m3-button m3-button--filled">{{ __('View impact') }}</button>
</form>
@endif
@if($report['term'] !== null)
<p>{{ __('Mastery model version') }}: {{ $report['policy_version'] }}</p>
@endif
@forelse($report['grades'] as $grade)
<section class="page-section" aria-labelledby="impact-grade-{{ $grade['grade_id'] }}">
<h2 id="impact-grade-{{ $grade['grade_id'] }}">{{ $grade['grade_name'] }}</h2>
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ $grade['grade_name'] }}" tabindex="0">
<table class="page-table__table">
<caption>{{ $grade['grade_name'] }} · {{ $report['term']['name'] }}</caption>
<thead><tr>
<th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Topic') }}</th>
<th scope="col">{{ __('With evidence at start') }}</th><th scope="col">{{ __('With evidence now') }}</th>
<th scope="col">{{ __('Rose a level') }}</th><th scope="col">{{ __('Held their level') }}</th><th scope="col">{{ __('Lower level') }}</th>
<th scope="col">{{ __('First evidence this term') }}</th><th scope="col">{{ __('Start unknown') }}</th><th scope="col">{{ __('No evidence yet') }}</th>
</tr></thead>
<tbody>
@foreach($grade['topics'] as $row)
<tr><td>{{ $row['subject_name'] }}</td><th scope="row">{{ $row['topic_name'] }}</th>
<td>{{ $row['evidence_at_start'] }}</td><td>{{ $row['evidence_now'] }}</td><td>{{ $row['rose'] }}</td><td>{{ $row['held'] }}</td><td>{{ $row['lower'] }}</td>
<td>{{ $row['first_evidence'] }}</td><td>{{ $row['start_unknown'] }}</td><td>{{ $row['no_evidence'] }}</td></tr>
@endforeach
</tbody>
<tfoot><tr><th scope="row" colspan="2">{{ __('All topics in this grade (learner-topic results)') }}</th>
<td>{{ $grade['totals']['evidence_at_start'] }}</td><td>{{ $grade['totals']['evidence_now'] }}</td><td>{{ $grade['totals']['rose'] }}</td><td>{{ $grade['totals']['held'] }}</td><td>{{ $grade['totals']['lower'] }}</td>
<td>{{ $grade['totals']['first_evidence'] }}</td><td>{{ $grade['totals']['start_unknown'] }}</td><td>{{ $grade['totals']['no_evidence'] }}</td></tr></tfoot>
</table>
</div></div>
</section>
@empty
<x-page.empty-state icon="inbox" :title="__('No mastery snapshots for this term yet')" :message="__('Snapshots are taken each night while a term runs. Mastery appears once marks with topics or practice are recorded.')" />
@endforelse
</div>
@endsection
