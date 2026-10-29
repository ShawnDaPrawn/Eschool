@extends('layouts.master')
@section('title', __('Vendor settlements'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
<x-page.header :title="__('Vendor settlements')" :subtitle="__('Review collected orders, original merchant terms and verified settlement outcomes.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<form method="get" action="{{ route('vendor-settlements.index') }}" aria-label="{{ __('Choose vendor settlement period') }}">
<x-page.field id="settlement-vendor" type="select" :restore-old-input="false" name="vendor_id" :label="__('Vendor counter')" :options="$options" :value="$selected?->id" required />
<x-page.field id="settlement-from" type="date" :restore-old-input="false" name="period_from" :label="__('Collection period from')" :value="$period['period_from']" required />
<x-page.field id="settlement-to" type="date" :restore-old-input="false" name="period_to" :label="__('Collection period to')" :value="$period['period_to']" required />
<x-m3.button type="submit">{{ __('Review eligible collected orders') }}</x-m3.button></form>
@if($selected)
<h2>{{ $selected->name }}</h2><p>{{ $selected->settlement_terms }}</p>
<p>{{ __('Only collected, due and previously unbatched orders are eligible. Preparation reserves the selected orders and does not move money.') }}</p>
@if(count($orders)>500)<p role="status">{{ __('More than 500 orders are eligible. Select no more than 500 for this batch.') }}</p>@endif
<form method="post" action="{{ route('vendor-settlements.prepare') }}" aria-label="{{ __('Prepare reviewed vendor settlement') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<input type="hidden" name="vendor_id" value="{{ $selected->id }}"><input type="hidden" name="vendor_revision" value="{{ $selected->revision }}">
<input type="hidden" name="period_from" value="{{ $period['period_from'] }}"><input type="hidden" name="period_to" value="{{ $period['period_to'] }}">
<fieldset @disabled(!$canWrite || count($orders)===0)><legend>{{ __('Select original collected orders') }}</legend>
<ul>@forelse($orders as $order)<li><label><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <a href="{{ route('vendor-orders.show',['order'=>$order->id]) }}">{{ $order->order_number }}</a> · {{ \App\Services\Platform\Ledger\Money::ofCents($order->amount_cents,$order->currency)->format() }}</label></li>@empty<li>{{ __('No collected orders are due for this settlement period.') }}</li>@endforelse</ul>
<label><input type="checkbox" name="confirmed_original_terms" value="1" required> {{ __('I reviewed these selected original orders and the merchant settlement and liability terms.') }}</label>
<x-m3.button type="submit">{{ __('Prepare reviewed vendor settlement') }}</x-m3.button></fieldset></form>
@endif
<h2>{{ __('Retained vendor settlements') }}</h2>
<ul>@forelse($rows as $row)<li><a href="{{ route('vendor-settlements.show',['settlement'=>$row->id]) }}">{{ __('Settlement') }} {{ $row->id }} · {{ $row->period_from }}–{{ $row->period_to }}</a> · {{ \App\Services\Platform\Ledger\Money::ofCents($row->gross_cents,$row->currency)->format() }}</li>@empty<li>{{ __('No vendor settlement batches have been prepared.') }}</li>@endforelse</ul>
{{ $rows->withQueryString()->links() }}
<p><a href="{{ route('vendor-settlements.export',array_filter(['vendor_id'=>$selected?->id]+$period)) }}">{{ __('Download vendor settlement CSV') }}</a></p>
</div>
@endsection
