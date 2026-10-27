@extends('layouts.master')
@section('title', __('Arrears and accounting'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Arrears and accounting')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-reporting.index')">{{ __('Back to arrears and accounting') }}</x-m3.button>
<p>{{ __('Use your accounting organisation chart of accounts and date format. Package columns must be checked against your accounting import template before use.') }}</p>
<p>{{ __('These settings map exported ledger postings. They do not change invoices, tax calculations or financial balances.') }}</p>
@if(!$canWrite)<p>{{ __('Accounting settings are read-only while fees are disabled.') }}</p>@endif
<form method="post" action="{{ route('fee-reporting.settings-save') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>$settings?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Accounting package mapping') }}</legend>
@foreach(['xero'=>__('Xero'),'quickbooks'=>__('QuickBooks')] as $layout=>$label)
<x-m3.text-field :name="$layout.'_currency'" :label="$label.' '.__('organisation base currency')" :value="$settings?->getAttribute($layout.'_currency')" maxlength="3" pattern="[A-Z]{3}" autocomplete="off" />
<x-m3.select :name="$layout.'_date_format'" :label="$label.' '.__('date format')" :value="$settings?->getAttribute($layout.'_date_format')??'d/m/Y'" :options="['d/m/Y'=>'DD/MM/YYYY','m/d/Y'=>'MM/DD/YYYY','Y-m-d'=>'YYYY-MM-DD']" required />
@endforeach
<x-m3.text-field name="xero_tax_label" :label="__('Exact Xero zero-tax label')" :value="$settings?->xero_tax_label" maxlength="80" autocomplete="off" />
@foreach($types as $type=>$label)<fieldset><legend>{{ __($label) }}</legend>
<x-m3.text-field :name="'mappings['.$type.'][xero]'" :label="__('Xero account code')" :value="$settings?->mappings[$type]['xero']??''" maxlength="100" autocomplete="off" />
<x-m3.text-field :name="'mappings['.$type.'][quickbooks]'" :label="__('QuickBooks account name')" :value="$settings?->mappings[$type]['quickbooks']??''" maxlength="100" autocomplete="off" />
</fieldset>@endforeach
<x-m3.button type="submit">{{ __('Save accounting mapping') }}</x-m3.button></fieldset></form>

</div>
@endsection
