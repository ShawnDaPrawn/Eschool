@extends('layouts.master')
@section('title', __('School VAT details'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('School VAT details')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-items.index')">{{ __('Back to fee items') }}</x-m3.button>
<p>{{ __('Enable registered VAT only when the school is registered in the declared country. Enter its legal seller details. Each new tax invoice retains these details and the explicitly selected item rate.') }}</p>
<p>{{ __('No-VAT and exempt charges can continue independently. Turning this off stops new registered-VAT charges; original invoices and their VAT credit corrections remain available.') }}</p>
@if(!$canWrite)<p>{{ __('School VAT details are read-only while fees are disabled.') }}</p>@endif
<form method="post" action="{{ route('fee-items.tax-save') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$setting?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Declared VAT registration') }}</legend>
<input type="hidden" name="enabled" value="0"><x-m3.checkbox name="enabled" value="1" :checked="$setting?->enabled??false" :label="__('Enable registered VAT for new fee item charges')" />
<x-m3.text-field name="country_code" :label="__('Registered country code')" :hint="__('Use the registered two-letter country code, for example ZA.')" maxlength="2" pattern="[A-Z]{2}" :value="$setting?->country_code" />
<x-m3.text-field name="tax_registration" :label="__('VAT registration number')" maxlength="40" :value="$setting?->tax_registration" />
<x-m3.text-field name="seller_name" :label="__('Legal seller name')" maxlength="160" :value="$setting?->seller_name" />
<x-m3.text-field type="textarea" name="seller_address" :label="__('Legal seller address')" maxlength="1000" :value="$setting?->seller_address" />
<p>{{ __('Registration number, country and legal seller details are required when registered VAT is enabled. Item VAT rates are entered explicitly; no statutory rate is assumed.') }}</p>
<x-m3.button type="submit">{{ __('Save school VAT details') }}</x-m3.button>
</fieldset></form>
</div>
@endsection
