@extends('layouts.master')
@section('title', __('Import attendance'))
@section('content')
@php($importView = request('view') === 'upload' ? 'upload' : 'template')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Import attendance')" :subtitle="__('Download the learner template for one class and date range, enter attendance, then upload the completed CSV.')" />
<x-panel-view-nav :current="$importView" :items="['template'=>['label'=>'1. Download template','href'=>route('attendance.add-bulk-data')], 'upload'=>['label'=>'2. Upload attendance','href'=>route('attendance.add-bulk-data',['view'=>'upload'])]]" />
@if($importView === 'template')
<section class="m3-card m3-card--outlined"><div class="m3-card__content">
<h2>{{ __('Download attendance template') }}</h2><p>{{ __('Use 0 for absent, 1 for present, 4 for first-half absence or 5 for second-half absence in the type column. Keep learner identifiers unchanged.') }}</p>
<form action="{{ route('student-export') }}" method="POST">@csrf
<x-page.field id="template-class" name="class_section_id" type="select" :label="__('Class and section')" :options="$class_sections->mapWithKeys(fn($section)=>[$section->id=>$section->class->name.' – '.$section->section->name.' '.$section->class->medium->name])->all()" :placeholder="__('Choose a class')" required />
<x-page.field id="template-from" name="from_date" type="date" :label="__('From date')" :max="now()->toDateString()" required />
<x-page.field id="template-to" name="to_date" type="date" :label="__('To date')" :max="now()->toDateString()" required />
<button class="m3-button m3-button--filled" type="submit">{{ __('Download CSV template') }}</button>
</form></div></section>
@else
<x-page.form-card id="attendance-import-form" :action="route('attendance.store-bulk-data')" :title="__('Upload attendance')" :submit-label="__('Import attendance')" :draft="false">
<x-page.field id="upload-class" name="class_section_id" type="select" :label="__('Class and section')" :options="$class_sections->mapWithKeys(fn($section)=>[$section->id=>$section->class->name.' – '.$section->section->name.' '.$section->class->medium->name])->all()" :placeholder="__('Choose a class')" required />
<x-page.field id="attendance-file" name="file" type="file" :label="__('Completed attendance CSV')" accept=".csv,.txt,text/csv,text/plain" required />
<p>{{ __('The import validates each row and reports errors without clearing your selected class.') }}</p>
</x-page.form-card>
@endif
</div>
@endsection
