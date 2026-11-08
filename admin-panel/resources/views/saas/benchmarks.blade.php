@extends('saas.layout')

@section('title', __('Benchmarks'))

@section('content')
@php
    $pct = fn (int $bp): string => intdiv($bp, 100).'%';
    $metricLabels = ['attendance' => __('Recorded attendance'), 'results_movement' => __('Results movement'), 'reach' => __('Guardian reach')];
    $sectors = ['public' => __('Public schools'), 'independent' => __('Independent schools')];
    $stageNames = ['early_years' => __('Early years'), 'junior' => __('Junior'), 'high_school' => __('High school')];
@endphp
<h1>{{ __('Benchmarks') }}</h1>
<p class="saas-lead">{{ __('The same medians and quartiles that schools taking part see, for each comparison group. No school is named or counted here, and groups with fewer than :count schools are not shown.', ['count' => $minimum_group_size]) }}</p>

@if ($period_end === null)
    <p class="saas-card">{{ __('No benchmarks published yet: they are worked out every week once enough comparable schools take part.') }}</p>
@elseif ($groups === [])
    <p class="saas-card">{{ __('No comparison group had enough comparable schools in the week ending :date, so nothing is shown.', ['date' => $period_end]) }}</p>
@else
<p>{{ __('Week ending :date', ['date' => $period_end]) }}</p>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Benchmarks') }}" tabindex="0">
    <table class="saas-table">
        <thead>
            <tr>
                <th scope="col">{{ __('Comparison group') }}</th>
                <th scope="col">{{ __('Figure') }}</th>
                <th scope="col" class="num">{{ __('Lower quartile') }}</th>
                <th scope="col" class="num">{{ __('Median') }}</th>
                <th scope="col" class="num">{{ __('Upper quartile') }}</th>
                <th scope="col">{{ __('Figures from') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($groups as $group)
            @foreach ($group['metrics'] as $key => $metric)
            <tr>
                <th scope="row">@if ($group['group']){{ $sectors[$group['group']['sector']] ?? '' }} · {{ collect($group['group']['stages'])->map(fn ($s) => $stageNames[$s] ?? '')->implode(', ') }} · @if ($group['group']['learners_to'] === null){{ __(':from or more learners', ['from' => $group['group']['learners_from']]) }}@else{{ __(':from to :to learners', ['from' => $group['group']['learners_from'], 'to' => $group['group']['learners_to']]) }}@endif @endif</th>
                <td>{{ $metricLabels[$key] ?? $key }}</td>
                <td class="num">{{ $pct($metric['lower_quartile_bp']) }}</td>
                <td class="num">{{ $pct($metric['median_bp']) }}</td>
                <td class="num">{{ $pct($metric['upper_quartile_bp']) }}</td>
                <td>{{ $metric['as_of'] }}@if ($metric['held']) <span class="saas-badge">{{ __('Kept') }}</span>@endif</td>
            </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
</div>
@endif
<p class="saas-actions"><a class="saas-btn saas-btn--quiet" href="{{ route('saas.health') }}">{{ __('School health') }}</a></p>
@endsection
