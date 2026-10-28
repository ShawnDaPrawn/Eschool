@extends('layouts.master')
@section('title', __('Review parent wallet deposit'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Review parent wallet deposit')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Wallet history is read-only while wallets are disabled.') }}</p>@endif
<dl><dt>{{ __('Request') }}</dt><dd>WD-{{ $claim->id }}</dd><dt>{{ __('Original parent holder') }}</dt><dd>{{ $owner->first_name }} {{ $owner->last_name }}</dd><dt>{{ __('Requested amount') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($claim->requested_cents,$claim->currency)->format() }}</dd><dt>{{ __('Claimed reference') }}</dt><dd>{{ $claim->source_reference }}</dd><dt>{{ __('Claimed paid date') }}</dt><dd>{{ $claim->paid_on }}</dd><dt>{{ __('Requested method') }}</dt><dd>{{ $claim->method==='cash'?__('Cash'):__('EFT') }}</dd><dt>{{ __('Status') }}</dt><dd>{{ match($receipt['status']){'verified'=>__('Verified'),'rejected'=>__('Rejected'),'cancelled'=>__('Cancelled'),default=>__('Awaiting Finance verification')} }}</dd></dl>
<details><summary>{{ __('Private parent-supplied proof') }}</summary><p>{{ $claim->evidence }}</p></details>
@if($action?->private_note)<details><summary>{{ __('Private review note') }}</summary><p>{{ $action->private_note }}</p></details>@endif
@if($receipt['verified_receipt'])<p>{{ __('Actual verified amount') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($receipt['verified_receipt']['amount_cents'],$receipt['currency'])->format() }}</p><a href="{{ route('wallet-deposits.receipt',['claim'=>$claim->id]) }}">{{ __('Open parent deposit receipt') }}</a>@endif
@if(!$action)
@if(!$canVerify)<p>{{ __('A different current Finance operator and a supported receipt method are required to verify this parent deposit.') }}</p>@endif
<h2>{{ __('Verify actual money received') }}</h2><p>{{ __('Check the original holder, actual bank transaction or cash receipt, amount and date. Correct any mistaken claim details below; the original request remains unchanged.') }}</p>
<form method="post" action="{{ route('wallet-deposits.verify',['claim'=>$claim->id]) }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])<fieldset @disabled(!$canVerify)><legend>{{ __('Independent original-holder deposit verification') }}</legend>
<x-m3.select name="method" :label="__('Actual received method')" :options="$methods" :value="$claim->method" :placeholder="__('Choose explicitly')" required />
<x-m3.text-field name="amount" :label="__('Actual received amount')" :value="\App\Services\Platform\Ledger\Money::ofCents($claim->requested_cents,$claim->currency)->toDecimalString()" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-m3.text-field name="source_reference" :label="__('Verified outside receipt reference')" :value="$claim->source_reference" maxlength="96" required />
<x-m3.text-field name="received_on" type="date" :label="__('Actual received date')" :value="$claim->paid_on" required />
<x-m3.text-field name="evidence" type="textarea" :label="__('Private independent receipt verification')" minlength="10" maxlength="2000" rows="3" required />
<label><input type="checkbox" name="verified_original_holder" value="1" required> {{ __('I verified that this deposit belongs to the original parent holder shown above.') }}</label>
<label><input type="checkbox" name="verified_actual_receipt" value="1" required> {{ __('I checked the actual money received, amount, date and reference. For cash, I received it and hold it at my desk.') }}</label>
<x-m3.button type="submit">{{ __('Verify and credit parent deposit') }}</x-m3.button></fieldset></form>
<h2>{{ __('Reject unverified request') }}</h2><form method="post" action="{{ route('wallet-deposits.reject',['claim'=>$claim->id]) }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])<fieldset @disabled(!$canWrite)><legend>{{ __('No money moves when a request is rejected') }}</legend>
<x-m3.select name="reason_code" :label="__('Parent-visible outcome')" :options="['not_verified'=>__('Receipt could not be verified'),'duplicate'=>__('Receipt was already recorded'),'request_incorrect'=>__('Request details need correction')]" :placeholder="__('Choose explicitly')" required />
<x-m3.text-field name="private_note" type="textarea" :label="__('Private rejection reason')" minlength="10" maxlength="2000" rows="3" required />
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('Reject this request without moving any wallet money.') }}</label><x-m3.button type="submit" variant="outlined">{{ __('Reject parent deposit request') }}</x-m3.button></fieldset></form>
@endif
<p><a href="{{ route('wallet-deposits.index') }}">{{ __('Back to parent deposits') }}</a></p></div>@endsection
