@extends('layouts.master')
@section('title', __('Review a pathway source'))
@section('content')
<div class="content-wrapper m3 page-content" data-pathway-editor>
<x-page.header :title="__('Review a pathway source')" :subtitle="__('Use the source institution scoring policy. No private learner marks are edited here.')" />
<a href="{{ route('pathways-panel.index') }}">{{ __('All pathway sources') }}</a>
@if($messages)<div role="alert" class="m3-alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
<form method="POST" action="{{ $source ? route('pathways-panel.update',['reference'=>$source['id']]) : route('pathways-panel.store') }}">
@csrf
<input type="hidden" name="expected_school_id" value="{{ $school }}"><input type="hidden" name="expected_account_id" value="{{ $account }}">
<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $source['revision'] ?? 0 }}"><input type="hidden" name="kind" value="{{ $kind }}">
<fieldset @disabled(!$configuration['writable']) style="min-width:0;border:0;padding:0">
<legend>{{ $kind === 'programme' ? __('Programme requirements') : __('Grade 9 subject-choice guide') }}</legend>
<x-page.field id="pathway-title" name="title" :label="__('Source entry title')" :value="$source['title'] ?? ''" :required="true" maxlength="191" :restore-old-input="false" />
@if($kind === 'programme')<x-page.field id="pathway-institution" name="institution" :label="__('Institution')" :value="$source['institution'] ?? ''" :required="true" maxlength="191" :restore-old-input="false" />@endif
<x-page.field id="pathway-source-title" name="source_title" :label="__('Source document title')" :value="$source['source_title'] ?? ''" :required="true" maxlength="240" :restore-old-input="false" />
<x-page.field id="pathway-source-url" name="source_url" type="url" :label="__('Public HTTPS source address')" :value="$source['source_url'] ?? ''" :required="true" maxlength="2048" :restore-old-input="false" />
<x-page.field id="pathway-source-year" name="source_year" type="number" :label="__('Source year')" :value="$source['source_year'] ?? substr($today,0,4)" min="2000" max="2100" :required="true" :restore-old-input="false" />
<x-page.field id="pathway-reviewed" name="reviewed_on" type="date" :label="__('Source reviewed on')" :value="$source['reviewed_on'] ?? $today" :required="true" :restore-old-input="false" />
<x-page.field id="pathway-expires" name="expires_on" type="date" :label="__('Review expires on')" :value="$source['expires_on'] ?? ''" :required="true" :restore-old-input="false" />
<p>{{ __('Review each published source at least annually. Expired or withdrawn sources cannot produce new family projections.') }}</p>
<x-page.field id="pathway-guidance" name="guidance" type="textarea" :label="__('Guidance and source limitations')" :value="$source['guidance'] ?? ''" :required="true" maxlength="6000" :restore-old-input="false" />
@if($kind === 'programme')
<section aria-labelledby="pathway-subjects"><h2 id="pathway-subjects">{{ __('Subjects in this programme source') }}</h2>
<p>{{ __('Set minimum percentages only where the source requires them. Explicitly choose which subjects count toward its score.') }}</p>
<div data-pathway-rows="subject">@foreach($source['rules']['subjects'] ?? [['subject_id'=>null,'minimum_bp'=>null,'counts_for_score'=>true]] as $index=>$row)@include('pathways.subject-row')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" data-pathway-add="subject">{{ __('Add subject rule') }}</button></section>
<section aria-labelledby="pathway-bands"><h2 id="pathway-bands">{{ __('Score bands stated by the source') }}</h2><p>{{ __('Include a band beginning at zero percent. Higher percentages cannot receive fewer points.') }}</p>
<div data-pathway-rows="band">@foreach($source['rules']['bands'] ?? [['minimum_bp'=>0,'points'=>null]] as $index=>$row)@include('pathways.band-row')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" data-pathway-add="band">{{ __('Add score band') }}</button></section>
<x-page.field id="pathway-best" name="best_n" type="number" :label="__('Number of best subjects to count')" :value="$source['rules']['best_n'] ?? ''" min="1" max="20" :required="true" :restore-old-input="false" />
<x-page.field id="pathway-minimum" name="minimum_score" type="number" :label="__('Minimum points stated by the source')" :value="$source['rules']['minimum_score'] ?? ''" min="0" max="400" :required="true" :restore-old-input="false" />
@else
<label for="pathway-compulsory">{{ __('Compulsory subjects') }}</label><select id="pathway-compulsory" class="m3-text-field__input" name="compulsory_subject_ids[]" multiple size="5" style="width:100%;max-width:100%">
@foreach($subjects as $id=>$name)<option value="{{ $id }}" @selected(in_array($id,$source['rules']['compulsory_subject_ids']??[],true))>{{ $name }}</option>@endforeach</select>
<section aria-labelledby="pathway-electives"><h2 id="pathway-electives">{{ __('Elective subject groups') }}</h2><p>{{ __('Put each elective in one group and state how many choices the source requires.') }}</p>
<div data-pathway-rows="elective">@foreach($source['rules']['elective_groups'] ?? [['title'=>'','choose'=>1,'subject_ids'=>[]]] as $index=>$row)@include('pathways.elective-row')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" data-pathway-add="elective">{{ __('Add elective group') }}</button></section>
@endif
@if($source)<x-page.field id="pathway-status" name="status" type="select" :label="__('Source publication status')" :value="$source['status']" :options="['published'=>__('Published'),'withdrawn'=>__('Withdrawn')]" :restore-old-input="false" />
@else<input type="hidden" name="status" value="published">@endif
@if($configuration['writable'])<button class="m3-button m3-button--filled" type="submit">{{ $source ? __('Save reviewed source revision') : __('Publish reviewed source') }}</button>@endif
</fieldset></form>
@if($kind === 'programme')
<template data-pathway-template="subject">@include('pathways.subject-row',['index'=>'__INDEX__','row'=>['subject_id'=>null,'minimum_bp'=>null,'counts_for_score'=>true]])</template>
<template data-pathway-template="band">@include('pathways.band-row',['index'=>'__INDEX__','row'=>['minimum_bp'=>null,'points'=>null]])</template>
@else<template data-pathway-template="elective">@include('pathways.elective-row',['index'=>'__INDEX__','row'=>['title'=>'','choose'=>1,'subject_ids'=>[]]])</template>@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/pathways.js') }}" defer></script>@endsection
