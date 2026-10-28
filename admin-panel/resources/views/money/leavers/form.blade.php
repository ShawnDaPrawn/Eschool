@extends('layouts.master')
@section('title', __('Leaver credits'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Leaver credits')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Leaver credit history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-leavers.index')">{{ __('Back to leaver credits') }}</x-m3.button>
<p>{{ __('Choose the original tuition invoice and learner. Confirm continuous dated school departure before preparing a credit; a class move is not a departure.') }}</p>
<form method="get"><x-m3.select name="account_id" :label="__('Family account filter')" :options="$accounts->pluck('name','id')" :value="$account?->id" :placeholder="__('All family accounts')" /><x-m3.select name="invoice_id" :label="__('Original tuition invoice')" :options="$invoices->mapWithKeys(fn($i)=>[$i->id=>$i->number.' · '.$i->account_name])" :value="$invoice?->id" :placeholder="__('Choose an invoice')" /><x-m3.button variant="outlined" type="submit">{{ __('Load original invoice') }}</x-m3.button></form>
@if($invoice)
<form method="post" action="{{ route('fee-leavers.prepare') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
<fieldset @disabled(!$canWrite)><legend>{{ __('Review school departure') }}</legend>
<x-m3.select name="learner_id" :label="__('Invoiced learner')" :options="$learners" :placeholder="__('Choose a learner')" required />
<x-m3.text-field name="private_explanation" :label="__('Private Finance explanation')" minlength="5" maxlength="1200" autocomplete="off" required />
<x-m3.button type="submit">{{ __('Review leaver credit') }}</x-m3.button></fieldset></form>@endif
</div>
@endsection
