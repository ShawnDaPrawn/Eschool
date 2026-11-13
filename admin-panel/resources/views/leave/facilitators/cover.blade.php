@extends('layouts.master')
@section('title', __('My assigned session cover'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('My assigned session cover')" :subtitle="$year->name"><x-slot:actions><a class="m3-button m3-button--text" href="{{ route('facilitator-leave.index') }}">{{ __('Facilitator leave requests') }}</a></x-slot:actions></x-page.header>
@forelse($covers as $cover)
@php($session=$sessions->get($cover->activity_session_id))
<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h2>{{ $groups->get($cover->activity_group_id)?->name }} · {{ $session?->name }}</h2><p>{{ $cover->on_date->toDateString() }} · {{ $cover->starts_at ? substr($cover->starts_at,0,5) : __('Time not configured') }} – {{ $cover->ends_at ? substr($cover->ends_at,0,5) : '' }} · {{ $session?->venue }}</p>
@if($cover->needsReview($session?->getAttributes()))<p role="status">{{ __('The activity session date or time changed, or it was cancelled. Ask the cover manager to review this assignment.') }}</p>@else<p>{{ __('Temporary cover confirmed for this session. Your permanent group appointments are unchanged.') }}</p>@endif
</div></article>
@empty<p>{{ __('No activity session cover is assigned to you for this school year.') }}</p>@endforelse
{{ $covers->links() }}
</div>
@endsection
