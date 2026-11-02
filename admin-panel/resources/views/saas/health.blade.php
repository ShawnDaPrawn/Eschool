@extends('saas.layout')

@section('title', __('School health'))

@section('content')
@php
    $orders = ['flags' => __('Flagged first'), 'name' => __('Name'), 'active' => __('Least active families first'), 'reach' => __('Lowest reach first'), 'bounce' => __('Most bounces first')];
    $pct = fn ($value): string => $value === null ? '–' : $value.'%';
@endphp
<h1>{{ __('School health') }}</h1>
<p class="saas-lead">{{ __('Every school\'s latest nightly figures, with its at-risk flags. Counts and shares only: no learner, parent or staff record is shown here. Open a school for its figures as of now.') }}</p>

<form method="GET" action="{{ route('saas.health') }}" class="saas-inline" aria-label="{{ __('Order the schools') }}">
    <label for="health-sort">{{ __('Order') }}</label>
    <select id="health-sort" name="sort">
        @foreach ($sorts as $key)<option value="{{ $key }}" @selected($key === $sort)>{{ $orders[$key] }}</option>@endforeach
    </select>
    <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Show') }}</button>
</form>

@if ($schools === [])
    <p class="saas-card">{{ __('No schools yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Schools compared') }}" tabindex="0">
    <table class="saas-table">
        <thead>
            <tr>
                <th scope="col">{{ __('School') }}</th>
                <th scope="col">{{ __('Flags') }}</th>
                <th scope="col" class="num">{{ __('Weekly active families') }}</th>
                <th scope="col" class="num">{{ __('Guardian reach') }}</th>
                <th scope="col" class="num">{{ __('Bounce rate') }}</th>
                <th scope="col" class="num">{{ __('Push failures') }}</th>
                <th scope="col" class="num">{{ __('AI cap used') }}</th>
                <th scope="col">{{ __('Computed') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($schools as $row)
            <tr data-health-school="{{ $row['id'] }}">
                <th scope="row"><a href="{{ route('saas.schools.health', $row['id']) }}">{{ $row['name'] }}</a> @if ($row['status'] !== 'active')<span class="saas-badge saas-badge--warn">{{ __(ucfirst($row['status'])) }}</span>@endif</th>
                @if ($row['headline'] === null)
                    <td colspan="6">{{ __('Not computed yet: the figures are worked out every night.') }}</td>
                    <td>–</td>
                @else
                    <td>@forelse ($row['flags'] as $flag)<span class="saas-badge saas-badge--warn" title="{{ $flag['detail'] }}">{{ $flag['label'] }}</span> @empty{{ __('None') }}@endforelse</td>
                    <td class="num">{{ $row['headline']['active'] }} / {{ $row['headline']['guardians'] }} ({{ $pct($row['headline']['active_share']) }})</td>
                    <td class="num">{{ $pct($row['headline']['reach_share']) }}</td>
                    <td class="num">{{ $row['headline']['mail_sent'] === 0 ? '–' : $row['headline']['bounce_rate'].'%' }}</td>
                    <td class="num">{{ $row['headline']['push_rate'] }}%</td>
                    <td class="num">{{ $pct($row['headline']['ai_share']) }}</td>
                    <td>{{ $row['computed_at'] }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
