@extends('layouts.master')
@section('title', __('Review a career profile'))
@section('content')
<div class="content-wrapper m3 page-content" data-pathway-editor>
<x-page.header :title="__('Review a career profile')" :subtitle="__('Describe the career from a dated public source, and list the subjects, topics and routes that lead to it.')" />
<a href="{{ route('pathways-panel.index') }}">{{ __('All pathway sources') }}</a>
@if($messages)<div role="alert" class="m3-alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<form method="POST" action="{{ $profile ? route('pathways-panel.careers.update',['profile'=>$profile['id']]) : route('pathways-panel.careers.store') }}">
@csrf
<input type="hidden" name="expected_school_id" value="{{ $school }}"><input type="hidden" name="expected_account_id" value="{{ $account }}">
<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $profile['revision'] ?? 0 }}">
<fieldset @disabled(!$configuration['writable']) style="min-width:0;border:0;padding:0">
<legend>{{ __('Career profile') }}</legend>
<x-page.field id="career-title" name="title" :label="__('Career')" :value="$profile['title'] ?? ''" :required="true" maxlength="191" :restore-old-input="false" />
<x-page.field id="career-summary" name="summary" type="textarea" :label="__('What the work involves, from the source')" :value="$profile['summary'] ?? ''" :required="true" maxlength="3000" :restore-old-input="false" />
<x-page.field id="career-source-title" name="source_title" :label="__('Source document title')" :value="$profile['source_title'] ?? ''" :required="true" maxlength="240" :restore-old-input="false" />
<x-page.field id="career-source-url" name="source_url" type="url" :label="__('Public HTTPS source address')" :value="$profile['source_url'] ?? ''" :required="true" maxlength="2048" :restore-old-input="false" />
<x-page.field id="career-source-year" name="source_year" type="number" :label="__('Source year')" :value="$profile['source_year'] ?? substr($today,0,4)" min="2000" max="2100" :required="true" :restore-old-input="false" />
<x-page.field id="career-reviewed" name="reviewed_on" type="date" :label="__('Source reviewed on')" :value="$profile['reviewed_on'] ?? $today" :required="true" :restore-old-input="false" />
<x-page.field id="career-expires" name="expires_on" type="date" :label="__('Review expires on')" :value="$profile['expires_on'] ?? ''" :required="true" :restore-old-input="false" />
<p>{{ __('Review each career profile at least annually. Profiles past their review date are marked as needing review.') }}</p>
<section aria-labelledby="career-subjects"><h2 id="career-subjects">{{ __('School subjects this career needs') }}</h2>
<p>{{ __('List only what the source states. Choose more than one subject where any of them meets the requirement.') }}</p>
<div data-pathway-rows="requirement">@foreach($profile['details']['subjects'] ?? [['subject_ids'=>[],'minimum_bp'=>null,'requirement'=>'required']] as $index=>$row)@include('pathways.career-subject-row')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" disabled data-pathway-add="requirement">{{ __('Add subject') }}</button></section>
<section aria-labelledby="career-topics"><h2 id="career-topics">{{ __('Curriculum topics to link') }}</h2>
<p>{{ __('Optional. Link published topics of the subjects above to show learning evidence in the guidance view.') }}</p>
@if($topics === [])<p>{{ __('No published curriculum topics are available yet.') }}</p>@else
<label for="career-topic-ids">{{ __('Linked topics') }}</label><select id="career-topic-ids" class="m3-text-field__input" name="topic_ids[]" multiple size="8" style="width:100%;max-width:100%">
@foreach($topics as $group=>$options)<optgroup label="{{ $group }}">@foreach($options as $id=>$name)<option value="{{ $id }}" @selected(in_array($id,$profile['details']['topic_ids']??[],true))>{{ $name }}</option>@endforeach</optgroup>@endforeach</select>@endif
</section>
<section aria-labelledby="career-routes"><h2 id="career-routes">{{ __('Routes into this career') }}</h2>
<p>{{ __('Include TVET college and learnership routes alongside university ones. Each route cites its own source.') }}</p>
<div data-pathway-rows="route">@foreach($profile['details']['routes'] ?? [['type'=>'university','title'=>'','provider'=>'','entry_note'=>'','source_title'=>'','source_url'=>'','programme_id'=>null]] as $index=>$row)@include('pathways.career-route-row')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" disabled data-pathway-add="route">{{ __('Add route') }}</button></section>
@if($profile)<x-page.field id="career-status" name="status" type="select" :label="__('Source publication status')" :value="$profile['status']" :options="['published'=>__('Published'),'withdrawn'=>__('Withdrawn')]" :restore-old-input="false" />
@else<input type="hidden" name="status" value="published">@endif
@if($configuration['writable'])<button class="m3-button m3-button--filled" type="submit">{{ $profile ? __('Save reviewed career profile') : __('Publish career profile') }}</button>@endif
</fieldset></form>
<template data-pathway-template="requirement">@include('pathways.career-subject-row',['index'=>'__INDEX__','row'=>['subject_ids'=>[],'minimum_bp'=>null,'requirement'=>'required']])</template>
<template data-pathway-template="route">@include('pathways.career-route-row',['index'=>'__INDEX__','row'=>['type'=>'university','title'=>'','provider'=>'','entry_note'=>'','source_title'=>'','source_url'=>'','programme_id'=>null]])</template>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/pathways.js') }}" defer></script>@endsection
