@extends('layouts.master')
@section('title',__('Review the changed content draft'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/content-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content content-workbench">
<x-page.header :title="__('Review the changed content draft')" :subtitle="__('Another change arrived before this save. Your submitted text is kept below for comparison.')" />
<div class="content-columns">
<section class="content-panel"><h2>{{ __('Your submitted draft') }}</h2><x-page.field id="content-submitted-markdown" :restore-old-input="false" type="textarea" name="submitted_markdown" :label="__('Submitted Markdown to copy')" :value="$submitted" readonly rows="18" />
<p>{{ $submittedTitle }}</p><p>{{ __('Teaching groups') }}: {{ implode(', ',$submittedGroups) }}</p><p>{{ __('Curriculum topic tags') }}: {{ implode(', ',$submittedTopics) }}</p></section>
<section class="content-panel"><h2>{{ __('Current saved draft') }}</h2><x-page.field id="content-current-markdown" :restore-old-input="false" type="textarea" name="current_markdown" :label="__('Current saved Markdown')" :value="$item['draft']['markdown'] ?? ''" readonly rows="18" />
<p>{{ __('Copy any changes you want to keep, reopen the current material and review the combined draft before saving.') }}</p><a class="m3-button m3-button--tonal" href="{{ route('academic-content.index',['item_id'=>$item['id']]) }}">{{ __('Open current content') }}</a></section>
</div></div>
@endsection
