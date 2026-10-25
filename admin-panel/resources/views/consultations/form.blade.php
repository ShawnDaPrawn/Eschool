@extends('layouts.master')
@section('title', __('Configure a consultation'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/consultations.css') }}">@endsection
@section('content')
<div class="content-wrapper consultations" data-consultations-root data-account="{{ $access['account_id'] }}" data-school="{{ $access['school_id'] }}" data-year="{{ $year }}" data-question-limit="{{ __('At most 20 questions are allowed.') }}" data-choice-limit="{{ __('At most 50 choices are allowed per question.') }}" data-question-required="{{ __('Keep at least one question.') }}" data-choose-audience="{{ __('Choose an audience') }}" data-no-audience="{{ __('No audiences match your current search and permission.') }}" data-audience-retry="{{ __('Audience search is unavailable. Your draft is retained; try again.') }}">
<h1>{{ __('Configure a consultation') }}</h1>@include('consultations.header')
@if($configuration)<p>{{ __(ucfirst($configuration['status'])) }} · {{ __('Revision') }} {{ $configuration['revision'] }}</p><nav aria-label="{{ __('Review consultation') }}"><a href="{{ route('consultations-panel.history',['consultation'=>$id]) }}">{{ __('Configuration history') }}</a>
@if(!in_array($configuration['status'],['draft','cancelled'],true))<a href="{{ route('consultations-panel.results',['consultation'=>$id,'managed'=>1]) }}">{{ __('Results and closed responses') }}</a>@endif</nav>@endif
@if($canWrite)
<form method="GET" action="{{ $id===null?route('consultations-panel.new'):route('consultations-panel.edit',['consultation'=>$id]) }}" class="m3-card consultation-fields" data-audience-picker aria-label="{{ __('Find consultation audience') }}">
<input type="hidden" name="session_year_id" value="{{ $year }}">
<label for="audience-kind">{{ __('Audience') }}<select id="audience-kind" name="kind">@foreach(['school'=>'Whole school','grade'=>'Grade','homeroom'=>'Homeroom','group'=>'Teaching group','activity_group'=>'Activity group','custom'=>'Owned custom list'] as $key=>$label)<option value="{{ $key }}" @selected($draft['audience']['kind']===$key)>{{ __($label) }}</option>@endforeach</select></label>
<label for="audience-search">{{ __('Search audience names') }}<input id="audience-search" name="search" maxlength="100" value="{{ is_string(request('search'))?request('search'):'' }}"></label>
<button type="submit" class="m3-button m3-button--outlined">{{ __('Find audience') }}</button><p role="status" data-audience-status></p>
<button type="button" class="m3-button m3-button--text" data-audience-previous hidden>{{ __('Previous audience page') }}</button><button type="button" class="m3-button m3-button--text" data-audience-next hidden>{{ __('Next audience page') }}</button>
</form>
@endif
<form method="POST" action="{{ $id===null?route('consultations-panel.create'):route('consultations-panel.save',['consultation'=>$id]) }}" data-consultation-editor aria-label="{{ __('Consultation configuration') }}">
@include('consultations.hidden')<input type="hidden" name="revision" value="{{ $configuration['revision']??0 }}"><input type="hidden" name="session_year_id" value="{{ $year }}">
<fieldset @disabled(!$canWrite)><legend>{{ __('Configuration') }}</legend><div class="consultation-fields">
<label for="consultation-title">{{ __('Title') }}<input id="consultation-title" name="title" maxlength="191" required value="{{ $draft['title'] }}"></label>
<label for="consultation-type">{{ __('Consultation type') }}<select id="consultation-type" name="type" data-consultation-type>@foreach(['poll'=>'Poll','survey'=>'Survey','election'=>'Nonstatutory election'] as $key=>$label)<option value="{{ $key }}" @selected($draft['type']===$key)>{{ __($label) }}</option>@endforeach</select></label>
<label for="consultation-open">{{ __('Opening date and time') }}<input id="consultation-open" type="datetime-local" name="opens_at_local" required value="{{ $draft['opens_at_local'] }}"></label>
<label for="consultation-close">{{ __('Closing date and time') }}<input id="consultation-close" type="datetime-local" name="closes_at_local" required value="{{ $draft['closes_at_local'] }}"></label>
<p>{{ __('Times use the school timezone') }}: {{ $access['timezone'] }}</p>
<label for="consultation-anonymous">{{ __('Response identity') }}<select id="consultation-anonymous" name="anonymous"><option value="1" @selected($draft['anonymous'])>{{ __('Anonymous responses') }}</option><option value="0" @selected(!$draft['anonymous'])>{{ __('Named responses') }}</option></select></label>
<label for="consultation-results">{{ __('Results visibility') }}<select id="consultation-results" name="results_visibility"><option value="after_close" @selected($draft['results_visibility']==='after_close')>{{ __('Private until close') }}</option><option value="public_live" @selected($draft['results_visibility']==='public_live')>{{ __('Live counts for the authorized audience') }}</option></select></label>
<input type="hidden" name="audience[kind]" value="{{ $draft['audience']['kind'] }}" data-selected-kind>
<label for="audience-target" data-audience-target @if($draft['audience']['kind']==='school') hidden @endif>{{ __('Selected audience') }}<select id="audience-target" name="audience[target_id]" @disabled($draft['audience']['kind']==='school')>
<option value="">{{ __('Choose an audience') }}</option>
@if($choices && $choices['selected_id']!==null && !collect($choices['items'])->contains('id',$choices['selected_id']))<option value="{{ $choices['selected_id'] }}" selected>{{ $choices['selected_name'] }}</option>@endif
@foreach($choices['items']??[] as $choice)<option value="{{ $choice['id'] }}" @selected(($draft['audience']['target_id']??null)===$choice['id'])>{{ $choice['name'] }}</option>@endforeach</select></label>
<label for="audience-parents">{{ __('Family audience') }}<select id="audience-parents" name="audience[parents]"><option value="0" @selected(!($draft['audience']['parents']??false))>{{ __('Learners') }}</option><option value="1" @selected($draft['audience']['parents']??false)>{{ __('Parents and guardians') }}</option></select></label>
<input type="hidden" name="audience[include_staff]" value="0"><label class="consultation-check" data-staff-audience @if(!in_array($draft['audience']['kind'],['school','activity_group','custom'])) hidden @endif><input type="checkbox" name="audience[include_staff]" value="1" @checked($draft['audience']['include_staff']??false)>{{ __('Include eligible staff') }}</label>
</div><label for="consultation-instructions">{{ __('Instructions') }}<textarea id="consultation-instructions" name="instructions" maxlength="5000">{{ $draft['instructions'] }}</textarea></label>
<p>{{ __('Publishing freezes the audience, questions, anonymity and schedule. Anonymous answers have no returned identity or submission timestamp.') }}</p>
<div data-questions>@foreach($draft['questions'] as $qIndex=>$question)@include('consultations.question')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" data-add-question>{{ __('Add question') }}</button><button type="submit" class="m3-button m3-button--filled" data-save-consultation>{{ __('Save consultation draft') }}</button>
<p role="status" data-editor-status></p></fieldset>
</form>
@if($configuration && $canManageChange)
@if($configuration['status']==='draft')<form method="POST" action="{{ route('consultations-panel.publish',['consultation'=>$id]) }}" aria-label="{{ __('Publish consultation') }}">@include('consultations.hidden')<input type="hidden" name="revision" value="{{ $configuration['revision'] }}"><button class="m3-button m3-button--filled" type="submit">{{ __('Publish frozen consultation') }}</button></form>@endif
@if(!in_array($configuration['status'],['closed','cancelled'],true))
@foreach(['close'=>'Close consultation','cancel'=>'Cancel consultation'] as $action=>$label)<form method="POST" action="{{ route('consultations-panel.'.$action,['consultation'=>$id]) }}" aria-label="{{ __($label) }}">@include('consultations.hidden')<input type="hidden" name="revision" value="{{ $configuration['revision'] }}"><label for="reason-{{ $action }}">{{ __('Reason for this change') }}<textarea id="reason-{{ $action }}" name="reason" maxlength="2000" required></textarea></label><button class="m3-button m3-button--outlined" type="submit">{{ __($label) }}</button></form>@endforeach
@endif
@endif
<template data-question-template>@include('consultations.question',['qIndex'=>'__Q__','question'=>['kind'=>'single','label'=>'','required'=>true,'max_choices'=>1,'options'=>[]]])</template>
<template data-option-template>@include('consultations.option',['qIndex'=>'__Q__','oIndex'=>'__O__','option'=>[]])</template>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/consultations.js') }}" defer></script>@endsection
