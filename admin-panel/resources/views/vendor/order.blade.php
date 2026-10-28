@extends('vendor.layout')
@section('content')
<h1>{{ $order['order_number'] }}</h1>
@if(session('vendor_collection_success'))<p role="status">{{ session('vendor_collection_success') }}</p>@endif
<p>{{ __($order['status']) }} · {{ \App\Services\Platform\Ledger\Money::ofCents($order['amount_cents'],$order['currency'])->format() }}</p>
<p>{{ __('VAT included') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($order['vat_cents'],$order['currency'])->format() }}</p>
@if($order['processed_at'])<p>{{ __('Processed at') }}: {{ $order['processed_at'] }}</p>@endif
<ul>@foreach($order['lines'] as $line)<li>{{ $line['product_name'] }} · {{ $line['quantity'] }} {{ $line['unit_label'] }} · {{ \App\Services\Platform\Ledger\Money::ofCents($line['gross_cents'],$order['currency'])->format() }}</li>@endforeach</ul>
@if($order['can_collect'])
<form method="post" action="{{ route('vendor.collect',['vendor'=>$vendor]) }}" aria-label="{{ __('Confirm one order handover') }}">@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="order_number" value="{{ $order['order_number'] }}">
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I reviewed these exact goods and will hand them over once after successful validation.') }}</label>
<button type="submit" class="m3-button m3-button--filled">{{ __('Validate one handover') }}</button></form>
@else<p role="status">{{ __('This order cannot be handed over again. Check its recorded status before supplying goods.') }}</p>@endif
<p><a href="{{ route('vendor.index',['vendor_id'=>$vendor]) }}">{{ __('Back to assigned counter') }}</a></p>
@endsection
