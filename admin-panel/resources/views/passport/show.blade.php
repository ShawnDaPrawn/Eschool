@extends('layouts.master')
@section('title', __('Learner passport'))
@section('content')
<div class="content-wrapper m3 page-content" style="overflow-wrap:anywhere">
<x-page.header :title="$passport['learner']['name']" :subtitle="__('Learner passport')" />
<x-page.learner-identifiers :identifier="$passport['learner']['primary_identifier']" :admission-no="$admissionNo" />
<div role="status" class="m3-card m3-card--outlined" style="margin-block:16px"><div class="m3-card__content">
<p>{{ __('This shows what this school holds and what arrived in a signed transfer addressed to this school. Records at other schools stay with them.') }}</p>
<p>{{ __('Health, counselling, wellbeing, discipline, support plans and safeguarding records are never part of a passport.') }}</p>
</div></div>
<p><a href="{{ route('passport-panel.index') }}">{{ __('Find another learner') }}</a></p>
@include('passport.partials.sections', ['passport' => $passport])
</div>
@endsection
