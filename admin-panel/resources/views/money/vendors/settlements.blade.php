@extends('layouts.master')
@section('title', __('Vendor settlements'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
<x-page.header :title="__('Vendor settlements')" :subtitle="__('Review collected orders, original merchant terms and verified settlement outcomes.')" />
@php
$section=request('section',request()->filled('vendor_id')?'prepare':'history');
if(!in_array($section,['history','prepare'],true)) $section='history';
$tabs=[];
$tabs['history']=['label'=>__('Settlement history'),'url'=>request()->fullUrlWithQuery(['section'=>'history','page'=>null])];
$tabs['prepare']=['label'=>__('Prepare settlement'),'url'=>request()->fullUrlWithQuery(['section'=>'prepare','page'=>null])];
@endphp
@include('money.billing.tabs')

@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($section==='prepare')
<form method="get" action="{{ route('vendor-settlements.index') }}" aria-label="{{ __('Choose vendor settlement period') }}"><input type="hidden" name="section" value="{{ $section }}">
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
<div class="page-table__scroll" role="region" aria-label="{{ __('Eligible original orders') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Eligible original orders') }}</caption><thead><tr><th scope="col">{{ __('Select') }}</th><th scope="col">{{ __('Original order') }}</th><th scope="col">{{ __('Collected at') }}</th><th scope="col">{{ __('Gross amount') }}</th><th scope="col">{{ __('VAT included') }}</th><th scope="col">{{ __('State') }}</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td><x-m3.checkbox name="order_ids[]" :value="$order->id" :label="__('Select order').' '.$order->order_number" /></td><td><a href="{{ route('vendor-orders.show',['order'=>$order->id]) }}">{{ $order->order_number }}</a></td><td>{{ $collections->get($order->id)?->created_at?->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone)->format('Y-m-d H:i') }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($order->amount_cents,$order->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($order->vat_cents,$order->currency)->format() }}</td><td>{{ __('Collected and due') }}</td></tr>@empty<tr><td colspan="6">{{ __('No collected orders are due for this settlement period.') }}</td></tr>@endforelse
</tbody></table></div>
<label><input type="checkbox" name="confirmed_original_terms" value="1" required> {{ __('I reviewed these selected original orders and the merchant settlement and liability terms.') }}</label>
<x-m3.button type="submit">{{ __('Prepare reviewed vendor settlement') }}</x-m3.button></fieldset></form>
@endif

@endif
@if($section==='history')
<form method="get" action="{{ route('vendor-settlements.index') }}" class="workspace-filters" aria-label="{{ __('Filter settlement history') }}"><input type="hidden" name="section" value="history">
<x-page.field name="vendor_id" type="select" :label="__('Vendor counter')" :options="$options" :placeholder="__('All vendors')" :value="$selected?->id" :restore-old-input="false" />
<x-page.field name="period_from" type="date" :label="__('Collection period from')" :value="request('period_from')" :restore-old-input="false" />
<x-page.field name="period_to" type="date" :label="__('Collection period to')" :value="request('period_to')" :restore-old-input="false" />
<x-m3.button type="submit" variant="outlined">{{ __('Filter settlement history') }}</x-m3.button>
<x-m3.button variant="text" :href="route('vendor-settlements.index')">{{ __('Clear filters') }}</x-m3.button></form>
<h2>{{ __('Retained vendor settlements') }}</h2>
<div class="page-table__scroll" role="region" aria-label="{{ __('Retained vendor settlements') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Retained vendor settlements') }}</caption><thead><tr><th scope="col">{{ __('Settlement') }}</th><th scope="col">{{ __('Vendor') }}</th><th scope="col">{{ __('Collection period') }}</th><th scope="col">{{ __('Orders') }}</th><th scope="col">{{ __('Gross amount') }}</th><th scope="col">{{ __('State') }}</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{ route('vendor-settlements.show',['settlement'=>$row->id]) }}">{{ __('Settlement') }} {{ $row->id }}</a></td><td>{{ $row->private_vendor_snapshot['name'] }}</td><td>{{ $row->period_from }} – {{ $row->period_to }}</td><td>{{ $row->order_count }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row->gross_cents,$row->currency)->format() }}</td><td>{{ in_array($row->id,$settled,true)?__('Recorded'):(in_array($row->id,$approved,true)?__('Approved, awaiting outcome'):__('Awaiting approval')) }}</td></tr>@empty<tr><td colspan="6">{{ __('No vendor settlement batches have been prepared.') }}</td></tr>@endforelse
</tbody></table></div>
{{ $rows->withQueryString()->links() }}
<p><a href="{{ route('vendor-settlements.export',array_filter(['vendor_id'=>$selected?->id]+request()->only(['period_from','period_to']))) }}">{{ __('Download vendor settlement CSV') }}</a></p>
@endif

</div>
@endsection
