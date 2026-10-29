@extends('layouts.master')
@section('title', __('Vendor orders'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
    <x-page.header :title="__('Vendor orders')" :subtitle="__('Review paid orders and validate collection once at the correct counter.')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if(!$canWrite)<p role="status">{{ __('Vendor order history is read-only while commerce is paused.') }}</p>@endif
    <form method="get" action="{{ route('vendor-orders.index') }}" aria-label="{{ __('Choose vendor counter') }}">
        <x-page.field id="counter-vendor" :restore-old-input="false" type="select" name="vendor_id" :label="__('Vendor counter')" :options="$options" :value="$selected?->id" :placeholder="__('Choose a vendor')" required />
        <x-m3.button type="submit">{{ __('Open vendor counter') }}</x-m3.button>
    </form>
    @if($selected)
        <form method="post" action="{{ route('vendor-orders.collect',['vendor'=>$selected->id]) }}" aria-label="{{ __('Validate order collection') }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>0])
            <fieldset @disabled(!$canWrite)><legend>{{ __('Validate one order at this vendor') }}</legend>
                <p>{{ __('Enter the order number or scan its wallet QR. Hand over goods only after successful validation.') }}</p>
                <x-page.field id="counter-order-number" :restore-old-input="false" name="order_number" :label="__('Order number')" maxlength="32" autocomplete="off" />
                <x-page.field id="counter-order-qr" :restore-old-input="false" name="qr_token" :label="__('Scanned wallet order QR')" maxlength="81" autocomplete="off" />
                <label><input type="checkbox" name="confirmed" value="1" required> {{ __('I checked this order and am ready to hand over its listed items once.') }}</label>
                <x-m3.button type="submit">{{ __('Validate order collection') }}</x-m3.button>
            </fieldset>
        </form>
    @endif
    <h2>{{ __('Recent original orders') }}</h2>
    <ul>@foreach($rows as $order)<li><a href="{{ route('vendor-orders.show',['order'=>$order->id]) }}">{{ $order->order_number }}</a> · {{ $options[$order->vendor_id]??'' }} · {{ \App\Services\Platform\Ledger\Money::ofCents($order->amount_cents,$order->currency)->format() }} · {{ __($actions->get($order->id)?->action??'ready') }}</li>@endforeach</ul>
    {{ $rows->withQueryString()->links() }}
</div>
@endsection
