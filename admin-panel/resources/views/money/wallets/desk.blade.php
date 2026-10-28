@extends('layouts.master')
@section('title', __('Desk wallet top-ups'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Desk wallet top-ups')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Wallet history is read-only while wallets are disabled.') }}</p>@endif
<p>{{ __('Desk grants use verified school-owned master funds. Parent-funded top-ups use the separate parent funding flow.') }}</p>
@if($canFinance)<p><a href="{{ route('wallet-funding.index') }}">{{ __('School wallet funding') }}</a> · <a href="{{ route('wallet-finance.index') }}">{{ __('School wallet spending rules') }}</a></p>@endif
@if($balance['currency'])<p>{{ __('Available master wallet balance') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($balance['balance_cents'],$balance['currency'])->format() }}</p>@endif
<h2>{{ __('Desk approval policy') }}</h2>
@if(!$canConfigureDesk)<p>{{ __('Only an authorised school policy manager may change the desk approval threshold.') }}</p>@endif
<form method="post" action="{{ route('wallet-desk.policy') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])<fieldset @disabled(!$canWrite||!$canConfigureDesk||!$setting)><legend>{{ __('Choose the independent approval threshold') }}</legend>
@if($setting)<p>{{ __('Wallet currency code') }}: {{ $setting->currency }}</p>@else<p>{{ __('Configure wallet currency first.') }}</p>@endif
<x-m3.text-field name="threshold_amount" :label="__('Second approval required above')" :value="$configured ? \App\Services\Platform\Ledger\Money::ofCents($policy->threshold_cents,$policy->currency)->toDecimalString() : null" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<p>{{ __('Set zero to require a different Finance approver for every positive desk grant. An amount equal to the threshold does not require another approver.') }}</p><x-m3.button type="submit">{{ __('Save desk approval policy') }}</x-m3.button></fieldset></form>
<h2>{{ __('Credit a school desk top-up') }}</h2>
<form method="get" action="{{ route('wallet-desk.index') }}"><x-m3.text-field name="search" type="search" :label="__('Find a recipient by name')" :value="$search" maxlength="120" /><x-m3.button type="submit" variant="outlined">{{ __('Find recipient') }}</x-m3.button></form>
@if(!$configured)<p>{{ __('Review the desk approval threshold for the current wallet currency first.') }}</p>@endif
<form method="post" action="{{ route('wallet-desk.grant') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])<fieldset @disabled(!$canWrite||!$configured)><legend>{{ __('Verified school grant to an active approved person') }}</legend>
<x-m3.select name="owner_user_id" :label="__('Wallet recipient')" :options="$recipients" :placeholder="__('Choose explicitly')" required />
<x-m3.text-field name="amount" :label="__('Desk top-up amount')" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-m3.text-field name="reason" type="textarea" rows="3" :label="__('Private school grant reason')" minlength="10" maxlength="2000" required />
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I reviewed the recipient and amount and authorise this school-owned grant.') }}</label>
<p>{{ __('Above-threshold money is reserved until an independent current Finance approver confirms it.') }}</p><x-m3.button type="submit">{{ __('Record desk top-up') }}</x-m3.button></fieldset></form>
<h2>{{ __('Desk top-up receipts and approvals') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Desk top-up receipts and approvals') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Desk top-up receipts and approvals') }}</caption><thead><tr><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Wallet recipient') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead><tbody>
@foreach($topups as $topup)<tr><td><a href="{{ route('wallet-desk.receipt',['topup'=>$topup->id]) }}">WT-{{ $topup->id }}</a></td><td>{{ $names[$topup->owner_user_id]??'' }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($topup->amount_cents,$topup->currency)->format() }}</td><td>{{ match($states[$topup->id]??'pending'){'released'=>__('Credited'),'cancelled'=>__('Cancelled'),default=>__('Awaiting independent approval')} }}</td></tr>@endforeach
</tbody></table></div>{{ $topups->links() }}</div>@endsection
