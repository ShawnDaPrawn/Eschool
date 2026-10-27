@extends('layouts.master')
@section('title', __('Fee items and charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">

<x-page.header :title="__('Prepare item charge')" />
<x-m3.button variant="text" :href="route('fee-item-charges.index')">{{ __('Back to item charges') }}</x-m3.button>
@if(!$canWrite)<p>{{ __('Fee item history is read-only while fees are disabled.') }}</p>@endif
<form method="get"><x-m3.select name="fee_item_id" :label="__('Fee item')" :options="$items->pluck('name','id')" :value="$item?->id" :placeholder="__('Choose a fee item')" required /><x-m3.select name="fee_account_id" :label="__('Family account')" :options="$accounts->pluck('name','id')" :value="$account?->id" :placeholder="__('Choose a family account')" required /><x-m3.button type="submit" variant="outlined">{{ __('Load charge details') }}</x-m3.button></form>
@if($item && $account && $year)
<h2 class="m3-title-large">{{ $item->name }} · {{ $account->name }}</h2>
<p>{{ __('Item amount') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($item->amount_cents,$item->currency)->format() }} · {{ __('No VAT charged') }}</p>
@if($item->scope==='family')<p>{{ __('This charges the family once. The review divides the total across its learners and preserves each holder total share.') }}</p><ul>@foreach($learnerOptions as $name)<li>{{ $name }}</li>@endforeach</ul>@endif
<form method="post" action="{{ route('fee-item-charges.review') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<input type="hidden" name="fee_item_id" value="{{ $item->id }}"><input type="hidden" name="fee_account_id" value="{{ $account->id }}"><input type="hidden" name="session_year_id" value="{{ $year->id }}">
<fieldset @disabled(!$canWrite || $item->state!=='active' || !$account->active)><legend>{{ __('Charge details') }}</legend>
@if($item->scope==='learner')<x-m3.select name="learner_id" :label="__('Learner receiving this charge')" :options="$learnerOptions" :placeholder="__('Choose a learner')" required />@endif
<x-m3.text-field name="source_reference" :label="__('Unique occurrence reference')" maxlength="80" autocomplete="off" required />
<p>{{ __('Use the same reference for this occurrence. A new form or request cannot charge it again.') }}</p>
<x-m3.text-field name="charged_on" type="date" :label="__('Charge date')" :value="$today" :max="$today" required />
<x-m3.text-field name="due_on" type="date" :label="__('Invoice due date')" :value="$today" required />
<x-m3.button type="submit">{{ __('Review item charge') }}</x-m3.button></fieldset></form>
@endif

</div>
@endsection
