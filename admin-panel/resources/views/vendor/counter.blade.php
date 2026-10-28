@extends('vendor.layout')
@section('content')
<h1>{{ __('Vendor order queue') }}</h1>
<form method="get" action="{{ route('vendor.index') }}" aria-label="{{ __('Choose assigned counter') }}">
<x-page.field id="assigned-vendor" :restore-old-input="false" type="select" name="vendor_id" :label="__('Assigned counter')" :options="array_column($profile['counters'],'name','id')" :value="$vendor" required />
<x-page.field id="counter-order-state" :restore-old-input="false" type="select" name="status" :label="__('Order status')" :options="['ready'=>__('Ready for collection'),'collected'=>__('Collected'),'cancelled'=>__('Cancelled')]" :value="$listing['status']" required />
<button type="submit" class="m3-button m3-button--outlined">{{ __('Open assigned counter') }}</button></form>
<form method="post" action="{{ route('vendor.lookup',['vendor'=>$vendor]) }}" aria-label="{{ __('Review order before handover') }}">@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}">
<fieldset><legend>{{ __('Scan or type an order') }}</legend>
<x-page.field id="vendor-lookup-number" :restore-old-input="false" name="order_number" :label="__('Order number')" maxlength="32" autocomplete="off" />
<x-page.field id="vendor-lookup-qr" :restore-old-input="false" name="qr_token" :label="__('Scanned order QR')" maxlength="81" autocomplete="off" data-vendor-qr />
<button type="button" class="m3-button m3-button--outlined" data-vendor-camera disabled>{{ __('Scan order QR with camera') }}</button>
<video data-vendor-video hidden autoplay playsinline muted aria-label="{{ __('Order QR camera preview') }}"></video>
<button type="button" class="m3-button m3-button--text" data-vendor-stop hidden>{{ __('Stop camera') }}</button>
<p role="status" data-vendor-camera-status
 data-unavailable="{{ __('Camera scanning is unavailable here. Type the order number or paste its QR code.') }}"
 data-ready="{{ __('QR ready. Review the order and its goods before confirming handover.') }}"
 data-stopped="{{ __('Camera scanning stopped. Type the order number or paste its QR code.') }}"></p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Review order before handover') }}</button>
</fieldset></form>
<h2>{{ __('Orders at this counter') }}</h2>
@if(!$profile['counters'][array_search($vendor,array_column($profile['counters'],'id'))]['can_collect'])<p role="status">{{ __('This counter is read-only. Keep history; do not hand over goods.') }}</p>@endif
<ul>@forelse($listing['items'] as $order)<li><a href="{{ route('vendor.order',['vendor'=>$vendor,'order'=>$order['id']]) }}">{{ $order['order_number'] }}</a> · {{ \App\Services\Platform\Ledger\Money::ofCents($order['amount_cents'],$order['currency'])->format() }}</li>@empty<li>{{ __('No orders are waiting at this counter.') }}</li>@endforelse</ul>
@if($listing['page']>1)<a href="{{ route('vendor.index',['vendor_id'=>$vendor,'status'=>$listing['status'],'page'=>$listing['page']-1]) }}">{{ __('Previous orders') }}</a>@endif
@if($listing['page']<$listing['last_page'])<a href="{{ route('vendor.index',['vendor_id'=>$vendor,'status'=>$listing['status'],'page'=>$listing['page']+1]) }}">{{ __('More orders') }}</a>@endif
@endsection
