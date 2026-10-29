@extends('layouts.master')
@section('title', __('Leadership dashboard'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/leadership-dashboard.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content leadership-dashboard">
<x-page.header :title="__('Leadership dashboard')" :subtitle="__('School trends by grade')" />
<p>{{ __('Attendance is based on recorded school days. Activity counts show observed signed-in learners. Missing sources are shown as unavailable.') }}</p>
<p>{{ __('Allocated learner fees show current applied receipts by receipt date, after credit releases. Held credit and family charges are excluded; mixed currencies or unreconciled amounts are unavailable.') }}</p>
<form method="GET" action="{{ route('leadership-dashboard.index') }}" class="leadership-filters" aria-label="{{ __('Choose dashboard period') }}">
<x-page.field id="leadership-year" name="session_year_id" type="select" :label="__('School year')" :options="$years->pluck('name','id')->all()" :value="$dashboard['session_year_id']" required />
<x-page.field id="leadership-weeks" name="weeks" type="number" :label="__('Weeks to show')" :value="$weeks" min="1" max="12" required />
<x-page.field id="leadership-through" name="through" type="date" :label="__('Through date')" :value="$dashboard['ends_on']" required />
<button type="submit" class="m3-button m3-button--filled">{{ __('View trends') }}</button>
</form>
@if($dashboard['activity_observed_since'])<p>{{ __('Learner activity observed since') }} {{ $dashboard['activity_observed_since'] }}</p>@endif
@forelse(array_reverse($dashboard['periods']) as $period)
<section class="page-section" aria-labelledby="leadership-period-{{ $loop->index }}">
<h2 id="leadership-period-{{ $loop->index }}">{{ $period['starts_on'] }} – {{ $period['ends_on'] }}</h2>
<div class="leadership-grades">
@forelse($period['grades'] as $row)
<article class="m3-card m3-card--outlined"><div class="m3-card__content">
<h3>{{ $row['grade_name'] }}</h3>
<dl class="leadership-values">
<dt>{{ __('Enrolled learners at period end') }}</dt><dd>{{ $row['enrolled_learners'] }}</dd>
<dt>{{ __('Recorded attendance') }}</dt><dd>@if($row['attendance']['percentage_bp'] !== null){{ number_format($row['attendance']['percentage_bp']/100,2) }}% · {{ $row['attendance']['recorded_days'] }} {{ __('learner days') }}@else{{ __('Unavailable') }}@endif</dd>
@foreach(['academic_risk' => __('Learners requiring academic follow-up'), 'discipline' => __('Learners with discipline incidents'), 'fee_collection_cents' => __('Allocated learner fees'), 'active_learners' => __('Observed active learners')] as $key => $label)
<dt>{{ $label }}</dt><dd>@if($row[$key]['value'] === null){{ __('Unavailable') }}@elseif($key === 'fee_collection_cents')@if($row['fee_collection_currency'] !== null){{ \App\Services\Platform\Ledger\Money::ofCents($row[$key]['value'], $row['fee_collection_currency'])->format() }}@else{{ $row[$key]['value'] }}@endif@else{{ $row[$key]['value'] }}@endif @if($row[$key]['status'] === 'partial') · {{ __('Partial observation') }}@endif</dd>
@endforeach
</dl>
</div></article>
@empty<p>{{ __('No dated enrolments in this period.') }}</p>@endforelse
</div>
</section>
@empty<p>{{ __('No dated enrolments in this period.') }}</p>@endforelse
</div>
@endsection
