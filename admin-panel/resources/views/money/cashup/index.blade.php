@extends('layouts.master')
@section('title', __('Daily cash-up'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Daily cash-up')" :subtitle="__('Compare actual posted cash receipts and payouts with the notes and coins counted at your desk.')" />
<p><a href="{{ route('cashups.handovers.index') }}">{{ __('Cash custody handovers') }}</a></p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($currencies===[])<p>{{ __('No financial currency has been recorded for this school yet.') }}</p>@else
<form method="get" action="{{ route('cashups.index') }}" aria-label="{{ __('Choose cash-up report') }}">
<x-page.field id="cashup-operator" type="select" :restore-old-input="false" name="operator_id" :label="__('Cash custodian')" :options="$operators" :value="$operator" required />
<x-page.field id="cashup-currency" type="select" :restore-old-input="false" name="currency" :label="__('Currency')" :options="array_combine($currencies,$currencies)" :value="$currency" required />
<x-page.field id="cashup-date" type="date" :restore-old-input="false" name="counted_on" :label="__('Cash count date')" :value="$date" required />
<x-m3.button type="submit">{{ __('Review actual cash report') }}</x-m3.button></form>
@if($report)
@if(!$report['healthy'])<p role="alert">{{ __('Cash postings or projections need reconciliation. Keep the original records; a count cannot adjust or repair them.') }}</p>@endif
@if($report['needs_review'])<p role="status">{{ __('An original cash record was added after this count. Review the new report and retain a corrected count with a reason.') }}</p>@endif
<dl>@foreach(['opening_cents'=>'Opening cash carried forward','receipts_cents'=>'Actual cash receipts','payouts_cents'=>'Actual cash payouts','closing_cents'=>'Expected cash in custody'] as $key=>$label)<dt>{{ __($label) }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($report[$key],$currency)->format() }}</dd>@endforeach</dl>
<p>{{ __('Wallet grants, transfers, purchases and EFT receipts are not physical cash and are excluded.') }}</p>
<h2>{{ __('Original cash records for this day') }}</h2>
<ul>@forelse($report['events'] as $event)<li>{{ __(['fee_receipt'=>'Fee cash receipt','school_wallet_funding'=>'School-owned wallet cash receipt','parent_wallet_receipt'=>'Verified parent cash receipt','fee_refund'=>'Fee cash refund','adult_wallet_payout'=>'Adult wallet cash payout','vendor_payout'=>'Vendor cash payout','handover_in'=>'Confirmed cash handover received','handover_out'=>'Confirmed cash handover given'][$event['kind']]) }} {{ $event['record_id'] }} · {{ \App\Services\Platform\Ledger\Money::ofCents($event['amount_cents'],$currency)->format() }} · {{ $event['effective_on'] }} ({{ __($event['date_basis']==='actual'?'Actual receipt or payout date':'Payout recording date') }})</li>@empty<li>{{ __('No cash receipts or payouts are recorded for this operator on this day.') }}</li>@endforelse</ul>
@if($operator===(int)auth()->id())
<form method="post" action="{{ route('cashups.record') }}" aria-label="{{ __('Record actual physical cash count') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>$report['latest']?->revision??0])
<input type="hidden" name="source_hash" value="{{ $report['source_hash'] }}"><input type="hidden" name="counted_on" value="{{ $date }}"><input type="hidden" name="currency" value="{{ $currency }}">
<fieldset @disabled(!$report['healthy'])><legend>{{ __('Record actual physical cash count') }}</legend>
<x-page.field id="cashup-counted" :restore-old-input="false" name="counted_amount" :label="__('Actual notes and coins counted')" inputmode="decimal" pattern="[0-9]{1,10}(\.[0-9]{1,2})?" required maxlength="13" />
<x-page.field id="cashup-evidence" type="textarea" :restore-old-input="false" name="private_evidence" :label="__('Private physical count evidence')" minlength="10" maxlength="4000" required />
<x-page.field id="cashup-reason" type="textarea" :restore-old-input="false" name="private_reason" :label="__('Private explanation for a difference or correction')" minlength="10" maxlength="4000" />
<label><input type="checkbox" name="confirmed_actual_count" value="1" required> {{ __('I counted the actual cash in my custody. A difference needs an explanation and will not change the ledger.') }}</label>
<x-m3.button type="submit">{{ __('Record actual physical cash count') }}</x-m3.button></fieldset></form>
@endif
<h2>{{ __('Retained physical counts') }}</h2>
@forelse($history as $count)<section><h3>{{ __('Count revision') }} {{ $count->revision }}</h3><p>{{ __('Actual counted cash') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($count->counted_cents,$currency)->format() }} · {{ __('Difference from original report') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($count->variance_cents,$currency)->format() }}</p><p>{{ $count->private_evidence }}</p>@if($count->private_reason)<p>{{ $count->private_reason }}</p>@endif</section>@empty<p>{{ __('No physical cash count has been recorded for this day.') }}</p>@endforelse
<p><a href="{{ route('cashups.export',['operator_id'=>$operator,'currency'=>$currency,'counted_on'=>$date]) }}">{{ __('Download private cash-up CSV') }}</a></p>
@endif
@endif
</div>
@endsection
