@extends('layouts.master')
@section('title', __('Learner passports'))
@section('content')
<div class="content-wrapper m3 page-content" style="overflow-wrap:anywhere">
<x-page.header :title="__('Learner passports')" :subtitle="__('A read-only record of a learner\'s awards, activities, service and the results that arrived with a transfer.')" />
<p>{{ __('You see what this school holds and what arrived in a signed transfer addressed to this school. Records at other schools stay with them.') }}</p>
<form method="GET" action="{{ route('passport-panel.index') }}" role="search">
<x-page.field id="passport-search" name="q" type="search" :label="__('Find a learner by name')" :value="$search" maxlength="80" :restore-old-input="false" />
<button class="m3-button m3-button--outlined" type="submit">{{ __('Search') }}</button></form>
@if($search !== '')
@forelse($learners as $row)<p><a href="{{ route('passport-panel.show', $row['id']) }}">{{ $row['name'] }}</a>@if($row['admission_no'] !== '') · {{ __('Admission number') }} {{ $row['admission_no'] }}@endif</p>
@empty<x-page.empty-state icon="search" :title="__('No learners match that name.')" :message="__('Search by first name or surname.')" />@endforelse
@endif
</div>
@endsection
