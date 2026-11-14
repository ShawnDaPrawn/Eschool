@extends('layouts.master')
@section('title', __('School year activated'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('School year activated')" :subtitle="$receipt['previous'].' → '.$receipt['current']" />
<p role="status">{{ __('The school-wide transition completed.') }} {{ \Carbon\Carbon::parse($receipt['completed_at'])->format('d M Y H:i') }}</p>
<div class="panel-workspace__grid">
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Carried forward') }}</h2><ul>@forelse($receipt['result']['copied'] as $key)<li>{{ __(\App\Services\Academics\NewYearService::CARRY[$key][0] ?? $key) }}</li>@empty<li>{{ __('No configuration was copied.') }}</li>@endforelse</ul></div></section>
<section class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ __('Existing target configuration retained') }}</h2><ul>@forelse($receipt['result']['skipped'] as $key)<li>{{ __(\App\Services\Academics\NewYearService::CARRY[$key][0] ?? $key) }}</li>@empty<li>{{ __('No selected transfers were skipped.') }}</li>@endforelse</ul></div></section>
</div>
<p>{{ __('Welcome notices prepared') }}: {{ $receipt['result']['welcomed'] }}</p>
<p>{{ __('Recorded learner promotions were applied by the global transition. Historical years remain available through the viewing-year selector.') }}</p>
<div class="page-form-actions"><a class="m3-button m3-button--filled" href="{{ route('home') }}">{{ __('Open Home') }}</a><a class="m3-button m3-button--outlined" href="{{ route('session-years.index') }}">{{ __('School years') }}</a></div>
</div>
@endsection
