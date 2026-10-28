@extends('layouts.master')
@section('title', __('Prepare automatic item batch'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Prepare automatic item batch')" />
<x-m3.button variant="text" :href="route('fee-item-batches.index')">{{ __('Back to automatic item batches') }}</x-m3.button>
<p>{{ __('An occurrence is charged once per item, school year and learner or family. Use the same reference for the same term, trip or service occurrence, even when resolving exceptions later.') }}</p>
@if(!$canWrite)<p>{{ __('Automatic item batches are read-only while fees are disabled.') }}</p>@endif
<form method="post" action="{{ route('fee-item-batches.review') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Batch details') }}</legend>
<x-m3.select name="fee_item_id" :label="__('Fee item')" :options="$items->pluck('name','id')" :value="$selected?->id" :placeholder="__('Choose a fee item')" required />
<x-m3.select name="session_year_id" :label="__('School year')" :options="$years->pluck('name','id')" required />
<x-m3.text-field name="charged_on" :label="__('Item charge date')" :value="now(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->toDateString()" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" required />
<x-m3.text-field name="due_on" :label="__('Invoice due date')" :value="now(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->toDateString()" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" required />
<x-m3.text-field name="source_reference" :label="__('Unique occurrence reference')" maxlength="80" required />
<x-m3.button type="submit">{{ __('Review automatic item batch') }}</x-m3.button>
</fieldset></form>
</div>
@endsection
