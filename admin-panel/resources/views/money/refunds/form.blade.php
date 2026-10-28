@extends('layouts.master')
@section('title', __('Fee refunds'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Fee refunds')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Refund history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-refunds.index')">{{ __('Back to fee refunds') }}</x-m3.button>
<form method="get"><x-m3.select name="payment_id" :label="__('Original receipt')" :options="$payments->mapWithKeys(fn($p)=>[$p->id=>'FEE-PAY-'.$p->id.' · '.$p->source_reference])" :value="$payment?->id" :placeholder="__('Choose a receipt')" required /><x-m3.button type="submit" variant="outlined">{{ __('Load original receipt') }}</x-m3.button></form>
@if($payment)<h2>FEE-PAY-{{ $payment->id }} · {{ $account->name }}</h2><p>{{ __('Available original receipt credit') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($available,$payment->currency)->format() }}</p>
@if(!$policy)<p>{{ __('Configure the school manual refund policy before preparing a refund.') }}</p>@endif
<form method="post" action="{{ route('fee-refunds.prepare') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="payment_id" value="{{ $payment->id }}">
<fieldset @disabled(!$canWrite || !$policy)><legend>{{ __('Review original-holder refund') }}</legend><x-m3.text-field name="amount" :label="__('Refund amount')" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-m3.select name="method" :label="__('Refund method')" :options="array_filter(['cash'=>$policy?->cash_allowed?__('Cash'):null,'eft'=>$policy?->eft_allowed?__('EFT'):null])" :placeholder="__('Choose explicitly')" required />
<x-m3.text-field name="private_explanation" :label="__('Private Finance explanation')" minlength="5" maxlength="1200" autocomplete="off" required /><x-m3.button type="submit">{{ __('Review receipt refund') }}</x-m3.button></fieldset></form>@endif
</div>@endsection
