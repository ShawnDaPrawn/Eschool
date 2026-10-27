@extends('layouts.master')
@section('title', __('Fee items and charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">

<x-page.header :title="$item?__('Edit fee item'):__('New fee item')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-items.index')">{{ __('Back to fee items') }}</x-m3.button>
<p>{{ __('The item name appears on family invoices and statements. Editing an item never changes a posted charge.') }}</p>
@if(!$canWrite)<p>{{ __('Fee item history is read-only while fees are disabled.') }}</p>@endif
<form method="post" action="{{ $item?route('fee-items.save',['item'=>$item->id]):route('fee-items.create') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$item?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Item details') }}</legend>
<x-m3.text-field name="name" :label="__('Item name')" :value="$item?->name" maxlength="160" required />
<x-m3.select name="scope" :label="__('Charged once per')" :options="['learner'=>__('Learner'),'family'=>__('Family')]" :value="$item?->scope??'learner'" required />
<x-m3.text-field name="amount" :label="__('Item amount')" :value="$item?\App\Services\Platform\Ledger\Money::ofCents($item->amount_cents,$item->currency)->toDecimalString():''" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" required />
<x-m3.text-field name="currency" :label="__('Currency')" :value="$item?->currency??'ZAR'" maxlength="3" pattern="[A-Z]{3}" required />
<x-m3.select name="tax_treatment" :label="__('Tax treatment')" :options="['no_vat'=>__('No VAT charged')]" :value="$item?->tax_treatment??''" :placeholder="__('Choose the applicable tax treatment')" required />
<x-m3.select name="state" :label="__('Status')" :options="['draft'=>__('draft'),'active'=>__('active'),'archived'=>__('archived')]" :value="$item?->state??'draft'" required />
<p>{{ __('An item with charge history keeps its learner or family scope and currency.') }}</p>
<x-m3.button type="submit">{{ __('Save fee item') }}</x-m3.button>
</fieldset></form>

</div>
@endsection
