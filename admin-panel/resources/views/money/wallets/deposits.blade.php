@extends('layouts.master')
@section('title', __('Parent wallet deposits'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Parent wallet deposits')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ __('Parent requests create no money. Verify actual receipts independently before crediting the original parent wallet.') }}</p>
<h2>{{ __('Parent deposit methods') }}</h2>
<p>{{ __('Choose parent receipt methods explicitly. Set both to No to pause parent deposits while existing wallet transfers continue.') }}</p>
<form method="post" action="{{ route('wallet-deposits.policy') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])<fieldset @disabled(!$canWrite)><legend>{{ __('Supported parent receipts, separate from school master funding') }}</legend>
<x-m3.select name="cash_allowed" :label="__('Allow verified parent cash deposits')" :options="['1'=>__('Yes'),'0'=>__('No')]" :value="$policy ? ($policy->cash_allowed?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="eft_allowed" :label="__('Allow verified parent EFT deposits')" :options="['1'=>__('Yes'),'0'=>__('No')]" :value="$policy ? ($policy->eft_allowed?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.button type="submit">{{ __('Save parent deposit methods') }}</x-m3.button></fieldset></form>
<div class="page-table__scroll" role="region" aria-label="{{ __('Parent deposit requests') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Parent deposit requests') }}</caption><thead><tr><th scope="col">{{ __('Request') }}</th><th scope="col">{{ __('Original parent holder') }}</th><th scope="col">{{ __('Requested amount') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead><tbody>
@foreach($claims as $claim)<tr><td><a href="{{ route('wallet-deposits.show',['claim'=>$claim->id]) }}">WD-{{ $claim->id }}</a></td><td>{{ $names[$claim->actor_id]??'' }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($claim->requested_cents,$claim->currency)->format() }}</td><td>{{ match($statuses[$claim->id]??'pending'){'verified'=>__('Verified'),'rejected'=>__('Rejected'),'cancelled'=>__('Cancelled'),default=>__('Awaiting Finance verification')} }}</td></tr>@endforeach
</tbody></table></div>{{ $claims->links() }}</div>@endsection
