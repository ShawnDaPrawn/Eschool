@extends('layouts.master')
@section('title', __('Discounts and bursaries'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Discounts and bursaries')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Discount history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-discounts.index')">{{ __('Back to discounts') }}</x-m3.button>
<p>{{ __('Choose the original billed family and learner. Review calculates the exact remaining source charges, eligibility and original-holder credits without posting money.') }}</p>
<form method="get"><x-m3.select name="account_id" :label="__('Family account')" :options="$accounts->pluck('name','id')" :value="$account?->id" :placeholder="__('Choose a family account')" required /><x-m3.button type="submit" variant="outlined">{{ __('Load family account') }}</x-m3.button></form>
@if($account)
<form method="post" action="{{ route('fee-discounts.prepare') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="fee_account_id" value="{{ $account->id }}">
<fieldset @disabled(!$canWrite)><legend>{{ __('Prepare fee reduction') }}</legend>
<x-m3.select name="fee_discount_id" :label="__('Approved discount rule')" :options="$rules->pluck('name','id')" :placeholder="__('Choose an approved rule')" required />
<x-m3.select name="learner_id" :label="__('Learner')" :options="$learners" :placeholder="__('Choose a learner')" required />
<x-m3.text-field name="private_explanation" :label="__('Private Finance explanation')" minlength="5" maxlength="1200" autocomplete="off" required />
<p>{{ __('This explanation and rule details remain with Finance. Parents see a generic fee reduction and only their own amounts.') }}</p>
<x-m3.button type="submit">{{ __('Review fee reduction') }}</x-m3.button></fieldset></form>
@endif
</div>
@endsection
