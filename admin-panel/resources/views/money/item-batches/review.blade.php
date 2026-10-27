@extends('layouts.master')
@section('title', __('Review automatic item batch'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Review automatic item batch')" />
<x-m3.button variant="text" :href="route('fee-item-batches.new',['fee_item_id'=>$preview['fee_item_id']])">{{ __('Change batch details') }}</x-m3.button>
@include('money.item-batches.summary')
<p>{{ __('All new charges post together. Exceptions are excluded only with confirmation. Changed eligibility, payer details or prices require a new review.') }}</p>
<form method="post" action="{{ route('fee-item-batches.post') }}" data-year-safe>
@csrf
@foreach(['expected_school_id','expected_account_id','operation_id','fee_item_id','session_year_id','charged_on','due_on','source_reference'] as $key)<input type="hidden" name="{{ $key }}" value="{{ $input[$key] }}">@endforeach
<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
<fieldset @disabled(!$canWrite||$preview['charges']===[])><legend>{{ __('Confirm reviewed item batch') }}</legend>
@if($preview['exceptions']!==[])<x-m3.checkbox name="exclude_exceptions" value="1" :label="__('I confirm the listed exceptions are excluded and will be resolved separately.')" required />@endif
<x-m3.checkbox name="confirmed" value="1" :label="__('I confirm these reviewed item charges and payer allocations should create invoices.')" required />
<x-m3.button type="submit">{{ __('Post automatic item batch once') }}</x-m3.button>
</fieldset></form>
</div>
@endsection
