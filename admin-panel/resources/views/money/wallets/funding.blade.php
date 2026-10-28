@extends('layouts.master')
@section('title', __('School wallet funding'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('School wallet funding')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Wallet history is read-only while wallets are disabled.') }}</p>@endif
<p>{{ __('Record only verified school-owned funds. Parent money belongs to the original payer and must use the parent funding flow.') }}</p>
<p><a href="{{ route('wallet-finance.index') }}">{{ __('School wallet spending rules') }}</a></p>
<h2>{{ __('Master school wallet') }}</h2>
@if($balance['currency'])<p>{{ \App\Services\Platform\Ledger\Money::ofCents($balance['balance_cents'],$balance['currency'])->format() }}</p>@else<p>{{ __('Configure wallet currency and spending rules first.') }}</p>@endif
<h2>{{ __('Manual funding methods') }}</h2>
<form method="post" action="{{ route('wallet-funding.policy') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Choose supported school funding methods') }}</legend>
<x-m3.select name="cash_allowed" :label="__('Accept verified school cash funding')" :options="['1'=>__('Yes'),'0'=>__('No')]" :value="$policy ? ($policy->cash_allowed?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="eft_allowed" :label="__('Accept verified school EFT funding')" :options="['1'=>__('Yes'),'0'=>__('No')]" :value="$policy ? ($policy->eft_allowed?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.button type="submit">{{ __('Save funding methods') }}</x-m3.button></fieldset></form>
<h2>{{ __('Record verified school funding') }}</h2>
@php $methods=[];if($policy?->cash_allowed){$methods['cash']=__('Cash');}if($policy?->eft_allowed){$methods['eft']=__('EFT');} @endphp
<form method="post" action="{{ route('wallet-funding.record') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite||!$balance['currency']||!$policy)><legend>{{ __('Funding already received by the school') }}</legend>
<x-m3.select name="method" :label="__('Funding method')" :options="$methods" :placeholder="__('Choose explicitly')" required />
<x-m3.text-field name="amount" :label="__('Funding amount')" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-m3.text-field name="source_reference" :label="__('Unique cash receipt or bank transaction reference')" maxlength="96" required />
<x-m3.text-field name="received_on" type="date" :label="__('Date funding received')" required />
<x-m3.text-field name="evidence" type="textarea" rows="3" :label="__('Private verification evidence')" minlength="10" maxlength="2000" required />
<label><input type="checkbox" name="school_owned" value="1" required> {{ __('I verified this money has been received and belongs to the school, not a parent or learner.') }}</label>
<x-m3.button type="submit">{{ __('Record school funding') }}</x-m3.button></fieldset></form>
<h2>{{ __('School funding receipts') }}</h2>
<div class="page-table__scroll" role="region" aria-label="{{ __('School funding receipts') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('School funding receipts') }}</caption><thead><tr><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Funding method') }}</th><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead><tbody>
@foreach($receipts as $receipt)<tr><td><a href="{{ route('wallet-funding.receipt',['funding'=>$receipt->id]) }}">{{ __('Receipt') }} {{ $receipt->id }}</a></td><td>{{ $receipt->method==='cash'?__('Cash'):__('EFT') }}</td><td>{{ $receipt->source_reference }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($receipt->amount_cents,$receipt->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>{{ $receipts->links() }}</div>@endsection
