{{-- Anonymous benchmarking (Action Plan 5.5): own figures next to the comparison group's median and quartiles. --}}
@php
    $pct = fn (?int $bp): string => $bp === null ? '–' : intdiv($bp, 100).'%';
    $metricLabels = ['attendance' => __('Recorded attendance'), 'results_movement' => __('Results movement'), 'reach' => __('Guardian reach')];
    $metricHints = ['attendance' => __('Recorded school days attended over the four weeks to the end of the week.'), 'results_movement' => __('Learner-topics this term that rose a mastery level.'), 'reach' => __('Learners with a guardian the school can reach by email or app.')];
    $positions = ['lower_quarter' => __('Lower quarter'), 'middle_half' => __('Middle half'), 'upper_quarter' => __('Upper quarter')];
    $sectors = ['public' => __('Public schools'), 'independent' => __('Independent schools')];
    $stageNames = ['early_years' => __('Early years'), 'junior' => __('Junior'), 'high_school' => __('High school')];
@endphp
<section class="page-section leadership-benchmarks" id="benchmarks" aria-labelledby="benchmarks-title">
<h2 id="benchmarks-title">{{ __('Comparable schools') }}</h2>
<p>{{ __('See how your school compares with similar schools on NavigateEd: the same sector, the same mix of school stages and a similar number of learners. No school is named, and no learner information leaves your school.') }}</p>
<p>{{ __('Only schools that take part are compared, and only a school that takes part sees the comparison. A comparison is shown only when at least :count comparable schools take part.', ['count' => $benchmarks['minimum_group_size']]) }}</p>
@if($errors->has('participate'))<p class="leadership-benchmarks__error" role="alert">{{ $errors->first('participate') }}</p>@endif
@if(! $benchmarks['participating'])
<p>{{ __('Your school does not take part in benchmarking, so it sees no comparison.') }}</p>
@if($benchmarks['rejoin_on'])<p>{{ __('Your school can join again from :date.', ['date' => $benchmarks['rejoin_on']]) }}</p>@endif
<form method="POST" action="{{ route('leadership-benchmarking.update') }}">@csrf<input type="hidden" name="participate" value="1">
<x-m3.button type="submit" :disabled="$benchmarks['rejoin_on'] !== null">{{ __('Take part in benchmarking') }}</x-m3.button></form>
@else
@if($benchmarks['period_end'] === null)
<p>{{ __('Your school takes part. Its first comparison appears after the next weekly update.') }}</p>
@else
<p>{{ __('Week ending :date', ['date' => $benchmarks['period_end']]) }}@if($benchmarks['group']) · {{ __('Compared with') }}: {{ $sectors[$benchmarks['group']['sector']] ?? '' }}, {{ collect($benchmarks['group']['stages'])->map(fn ($s) => $stageNames[$s] ?? '')->implode(', ') }}, @if($benchmarks['group']['learners_to'] === null){{ __(':from or more learners', ['from' => $benchmarks['group']['learners_from']]) }}@else{{ __(':from to :to learners', ['from' => $benchmarks['group']['learners_from'], 'to' => $benchmarks['group']['learners_to']]) }}@endif @endif</p>
@if($benchmarks['group'] === null)
<p>{{ __('Your school could not be placed in a comparison group yet. NavigateEd needs its sector and its current enrolments.') }}</p>
@else
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Comparable schools') }}" tabindex="0">
<table class="page-table__table">
<caption>{{ __('Your school next to the middle of comparable schools') }}</caption>
<thead><tr><th scope="col">{{ __('Figure') }}</th><th scope="col">{{ __('Your school') }}</th><th scope="col">{{ __('Lower quartile') }}</th><th scope="col">{{ __('Median') }}</th><th scope="col">{{ __('Upper quartile') }}</th><th scope="col">{{ __('Where your school falls') }}</th></tr></thead>
<tbody>
@foreach($benchmarks['metrics'] as $key => $metric)
<tr data-benchmark="{{ $key }}">
<th scope="row">{{ $metricLabels[$key] }}<br><small>{{ $metricHints[$key] }}</small></th>
<td>{{ $metric['own_bp'] === null ? __('Not enough data at your school') : $pct($metric['own_bp']) }}</td>
@if($metric['comparison'] === null)
<td colspan="4">{{ __('Not enough comparable schools yet') }}</td>
@else
<td>{{ $pct($metric['comparison']['lower_quartile_bp']) }}</td><td>{{ $pct($metric['comparison']['median_bp']) }}</td><td>{{ $pct($metric['comparison']['upper_quartile_bp']) }}</td>
<td>{{ $metric['position'] === null ? '–' : $positions[$metric['position']] }}@if($metric['comparison']['held'])<br><small>{{ __('Kept from the week ending :date until more schools join or leave.', ['date' => $metric['comparison']['as_of']]) }}</small>@endif</td>
@endif
</tr>
@endforeach
</tbody>
</table>
</div></div>
@endif
@endif
<form method="POST" action="{{ route('leadership-benchmarking.update') }}">@csrf<input type="hidden" name="participate" value="0">
<x-m3.button type="submit" variant="outlined">{{ __('Stop taking part') }}</x-m3.button></form>
@endif
</section>
