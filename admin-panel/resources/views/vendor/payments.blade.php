@extends('vendor.layout')
@section('title', __('Wallet payments'))
@section('content')
<div data-wallet-payment-counter data-school="{{ $profile['school_id'] }}" data-actor="{{ $profile['user_id'] }}" data-vendor="{{ $vendor }}" data-index="{{ route('vendor.payments.index',['vendor'=>$vendor]) }}">
<h1>{{ __('Wallet payments') }}</h1>
<p><a href="{{ route('vendor.index',['vendor_id'=>$vendor]) }}">{{ __('Return to this counter order queue') }}</a></p>
@if(!$canWrite)<p role="status">{{ __('Wallet payments are paused. Original order receipts remain available in the counter queue.') }}</p>@endif
<p role="status" data-wallet-payment-recovery-status data-storage-error="{{ __('This browser cannot retain the original request. Use the Vendor app or another browser.') }}"></p>
<form method="post" action="{{ route('vendor.payments.resolve',['vendor'=>$vendor]) }}" data-wallet-payment-recover hidden autocomplete="off">@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="">
<p>{{ __('Check the original counter request before another payment or handover.') }}</p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Check original counter outcome') }}</button></form>
@if($outcome)
<section data-wallet-payment-outcome="{{ $outcome['operation_id'] }}">
@if($outcome['abandoned']??false)<p role="status">{{ __('No payment was recorded by that request. Review a current instruction.') }}</p>
@else
<p role="status">{{ ($outcome['replayed']??false)?__('Payment was already recorded. Check the original receipt; do not hand over goods again.'):__('Payment recorded. Hand over the listed goods once.') }}</p>
<h2>{{ __('Original order receipt') }} {{ $outcome['order']['order_number'] }}</h2><p>{{ $format($outcome['order']['currency'],$outcome['order']['amount_cents']) }}</p>
<ul>
@foreach($outcome['order']['lines'] as $line)
<li>{{ $line['product_name'] }} · {{ $line['quantity'] }} {{ $line['unit_label'] }}</li>
@endforeach
</ul>
@endif
</section>
@endif
@if($canWrite)
<form method="post" action="{{ route('vendor.payments.lookup',['vendor'=>$vendor]) }}" autocomplete="off" data-wallet-payment-new>@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}">
<fieldset><legend>{{ __('Review wallet QR payment') }}</legend>
<x-page.field id="payment-lookup-qr" :restore-old-input="false" name="qr_token" :label="__('Current wallet payment QR')" maxlength="86" autocomplete="off" data-vendor-qr data-vendor-qr-purpose="wallet" required />
<button type="button" class="m3-button m3-button--outlined" data-vendor-camera disabled>{{ __('Scan wallet payment QR') }}</button>
<video data-vendor-video hidden autoplay playsinline muted aria-label="{{ __('Wallet QR camera preview') }}"></video><button type="button" class="m3-button m3-button--text" data-vendor-stop hidden>{{ __('Stop camera') }}</button>
<p role="status" data-vendor-camera-status data-unavailable="{{ __('Camera scanning is unavailable here. Paste the current wallet payment QR.') }}" data-ready="{{ __('Payment QR ready. Review its exact items and amount before confirming.') }}" data-stopped="{{ __('Camera scanning stopped.') }}"></p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Review wallet QR payment') }}</button></fieldset></form>
@endif
@if($review)
<section data-wallet-payment-expires="{{ $review['expires_at'] }}"><h2>{{ __('Review this counter payment') }}</h2>
<ul>
@foreach($review['lines'] as $line)
<li>{{ $line['product_name'] }} · {{ $line['quantity'] }} · {{ $format($review['currency'],$line['gross_cents']) }}</li>
@endforeach
</ul><p>{{ __('Exact wallet total') }}: {{ $format($review['currency'],$review['amount_cents']) }}</p>
@if($portrait)<h3>{{ $review['holder_name'] }}</h3><img src="{{ $portrait }}" alt="{{ __('Current cardholder photo') }}" width="128" height="160" style="object-fit:contain">@endif
<form method="post" action="{{ route('vendor.payments.pay',['vendor'=>$vendor]) }}" autocomplete="off" data-wallet-payment-confirm>@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="intent_id" value="{{ $review['id'] }}">
@foreach($credentials as $key=>$value)
<input type="hidden" name="{{ $key }}" value="{{ $value }}">
@endforeach
<label><input type="checkbox" name="confirmed" value="1" required> {{ $portrait?__('I checked this current photo, the exact items and total, and will hand over these goods once.'):__('I checked the exact items and total and will hand over these goods once.') }}</label>
<button type="submit" class="m3-button m3-button--filled">{{ __('Confirm wallet payment and handover') }}</button></form></section>
@endif
@if($canTap && $catalogue)
<h2>{{ __('Pay by school identity card') }}</h2><p>{{ __('Select the goods, tap the card, then check its photo and the exact amount before confirming.') }}</p>
<form method="post" action="{{ route('vendor.payments.card',['vendor'=>$vendor]) }}" autocomplete="off" data-wallet-payment-new data-wallet-payment-card>@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
<fieldset><legend>{{ __('Goods for this card payment') }}</legend><p>{{ __('Choose up to twenty product lines. Zero quantity leaves a product out of this payment.') }}</p>
@foreach($catalogue['items'] as $i=>$product)
<input type="hidden" name="lines[{{ $i }}][product_id]" value="{{ $product['id'] }}">
<x-page.field :id="'tap-product-'.$product['id']" :name="'lines['.$i.'][quantity]'" type="number" :restore-old-input="false" :label="$product['name'].' · '.$format($catalogue['currency'],$product['unit_price_cents'])" value="0" min="0" max="999" />
@endforeach
<x-page.field id="payment-tap-reader" name="reader_credential" type="password" :restore-old-input="false" :label="__('Private reader pairing credential')" maxlength="159" required autocomplete="off" />
<x-page.field id="payment-tap-uri" name="tap_uri" type="password" :restore-old-input="false" :label="__('Read school identity card')" maxlength="1024" required autocomplete="off" data-wallet-payment-tap />
<button type="button" class="m3-button m3-button--outlined" data-wallet-payment-nfc>{{ __('Read school card by NFC') }}</button>
<p role="status" data-wallet-payment-nfc-status data-unavailable="{{ __('NFC is unavailable in this browser. Use the Vendor app or a paired reader that enters the school card link.') }}" data-ready="{{ __('School card read. Review its current photo and exact amount before confirming.') }}"></p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Review card photo and exact payment') }}</button></fieldset></form>
@if($catalogue['page']>1)<a href="{{ route('vendor.payments.index',['vendor'=>$vendor,'page'=>$catalogue['page']-1]) }}">{{ __('Previous products') }}</a>@endif
@if($catalogue['page']<$catalogue['last_page'])<a href="{{ route('vendor.payments.index',['vendor'=>$vendor,'page'=>$catalogue['page']+1]) }}">{{ __('More products') }}</a>@endif
@endif
</div>
<script src="{{ asset('assets/js/vendor-wallet-payments.js') }}" defer></script>
@endsection
