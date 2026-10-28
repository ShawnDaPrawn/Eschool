@extends('layouts.master')
@section('title', __('Original vendor order'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
    <x-page.header :title="$order->order_number" :subtitle="__('Original vendor order')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <p>{{ $order->seller_snapshot['legal_name'] }} · {{ __($state) }}</p>
    <p>{{ __('Original wallet amount') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($order->amount_cents,$order->currency)->format() }}</p>
    <p>{{ __('VAT included') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($order->vat_cents,$order->currency)->format() }}</p>
    <ul>@foreach($lines as $line)<li>{{ $line->product_name }} · {{ $line->quantity }} {{ $line->unit_label }} · {{ \App\Services\Platform\Ledger\Money::ofCents($line->gross_cents,$order->currency)->format() }}</li>@endforeach</ul>
    @if($state==='ready' && $canWrite)
        <form method="post" action="{{ route('vendor-orders.collect',['vendor'=>$order->vendor_id]) }}" data-year-safe aria-label="{{ __('Validate this order collection') }}">
            @include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="order_number" value="{{ $order->order_number }}">
            <label><input type="checkbox" name="confirmed" value="1" required> {{ __('I checked this order and am ready to hand over its listed items once.') }}</label>
            <x-m3.button type="submit">{{ __('Validate this order collection') }}</x-m3.button>
        </form>
    @endif
    @if($canCancel)
        <form method="post" action="{{ route('vendor-orders.cancel',['order'=>$order->id]) }}" data-year-safe aria-label="{{ __('Cancel uncollected original order') }}">
            @include('money.fee-plans.hidden',['revision'=>0])
            <x-page.field id="order-cancel-reason" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private cancellation reason')" required minlength="3" maxlength="2000" />
            <label><input type="checkbox" name="verified_uncollected" value="1" required> {{ __('I verified that no goods were collected or handed over for this order.') }}</label>
            <label><input type="checkbox" name="confirmed" value="1" required> {{ __('Return the original amount only to its original wallet and restore its counted units once.') }}</label>
            <x-m3.button type="submit">{{ __('Cancel uncollected original order') }}</x-m3.button>
        </form>
    @endif
    <p><a href="{{ route('vendor-orders.index',['vendor_id'=>$order->vendor_id]) }}">{{ __('Back to vendor counter') }}</a></p>
</div>
@endsection
