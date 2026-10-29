@extends('layouts.master')
@section('title', __('Vendor payment readers'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Vendor payment readers')" :subtitle="__('Pair a short-lived reader with one Vendor counter. Gate and classroom credentials cannot approve wallet payments.')" />
@if($pairing)
<section data-payment-pairing><h2>{{ __('Pair this payment reader privately') }}</h2>
<p>{{ __('Give this credential only to the assigned counter operator. It expires automatically and can be revoked below. Clear this page after pairing.') }}</p>
<x-page.field id="payment-reader-credential" name="pairing_credential" :restore-old-input="false" :label="__('Private reader pairing credential')" :value="$pairing['credential']" readonly autocomplete="off" />
<p>{{ __('Expires at') }} {{ $pairing['expires_at'] }}</p></section>
@endif
@if(!$canWrite)<p role="status">{{ __('New payment readers are unavailable while cards or wallet payments are paused. Existing readers can still be revoked.') }}</p>@endif
<form method="post" action="{{ route('vendor-payment-readers.register') }}" autocomplete="off" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Register a Vendor payment reader') }}</legend>
<x-page.field id="payment-reader-vendor" name="vendor_id" type="select" :restore-old-input="false" :label="__('Assigned Vendor counter')" :options="$vendors" required />
<x-page.field id="payment-reader-name" name="name" :restore-old-input="false" :label="__('Reader name')" required maxlength="100" />
<x-page.field id="payment-reader-days" name="days" type="number" :restore-old-input="false" :label="__('Reader validity in days')" min="1" max="30" required />
<x-m3.button type="submit">{{ __('Register and pair payment reader') }}</x-m3.button>
</fieldset></form>
<h2>{{ __('Registered payment readers') }}</h2>
<ul>
@foreach($bindings as $binding)
@php($device=$devices->get($binding->device_id))
@if($device)
<li><p>{{ $device->name }} · {{ $vendors[$binding->vendor_id]??$binding->vendor_id }} · {{ $device->status }} · {{ $device->expires_at }}</p>
@if($device->revoked_at===null)
<form method="post" action="{{ route('vendor-payment-readers.revoke',['reader'=>$device->id]) }}" data-year-safe>@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}"><input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<x-m3.button type="submit" variant="outlined">{{ __('Revoke this payment reader') }}</x-m3.button></form>
@endif
</li>
@endif
@endforeach
</ul>{{ $bindings->links() }}
</div>
@endsection

@section('script')<script src="{{ asset('assets/js/vendor-payment-pairing.js') }}" defer></script>@endsection
