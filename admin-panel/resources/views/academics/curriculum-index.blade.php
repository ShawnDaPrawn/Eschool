@extends('layouts.master')
@section('title', __('Curriculum plans'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Curriculum plans')" :subtitle="__('Prepare topics, tasks and question maxima for each subject and term.')" />
    @can('curriculum-manage')<a class="m3-button m3-button--filled" href="{{ route('curriculum.create') }}">{{ __('New curriculum draft') }}</a>@endcan
    @forelse ($plans as $plan)
        <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h2><a href="{{ route('curriculum.edit', $plan->id) }}">{{ $plan->title }}</a></h2>
            <p>{{ __('Version') }} {{ $plan->version }} · {{ __($plan->state) }} · {{ $plan->year->name }}</p>
        </div></section>
    @empty<p role="status">{{ __('No curriculum plans are available for your allocations yet.') }}</p>@endforelse
    <nav aria-label="{{ __('Curriculum pages') }}">
        @if ($plans->previousPageUrl())<a href="{{ $plans->previousPageUrl() }}">{{ __('Previous') }}</a>@endif
        @if ($plans->nextPageUrl())<a href="{{ $plans->nextPageUrl() }}">{{ __('Next') }}</a>@endif
    </nav>
</div>
@endsection
