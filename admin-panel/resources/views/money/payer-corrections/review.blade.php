@extends('layouts.master')
@section('title', __('Review original unpaid correction'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench payer-correction-workbench">
<x-page.header :title="__('Review original unpaid correction')" :subtitle="__('Paid receipts, allocations and held credit stay with their original payer. Only the reviewed unpaid debt receives an equal credit and replacement invoice.')" />
<dl>
<dt>{{ __('Original invoice') }}</dt><dd>{{ $preview['credit']['invoice_number'] }}</dd>
<dt>{{ __('Learner') }}</dt><dd>{{ $preview['learner_name'] }}</dd>
<dt>{{ __('Original payer') }}</dt><dd>{{ $preview['credit']['holder_name'] }}</dd>
<dt>{{ __('Intended configured payer') }}</dt><dd>{{ $preview['new_holder']['name'] }}</dd>
<dt>{{ __('Unpaid correction amount') }}</dt><dd>{{ $preview['currency'] }} {{ number_format($preview['amount_cents']/100,2) }}</dd>
<dt>{{ __('Preserved original VAT') }}</dt><dd>{{ $preview['currency'] }} {{ number_format($preview['vat_cents']/100,2) }}</dd>
<dt>{{ __('Preserved original due date') }}</dt><dd>{{ $preview['due_on'] }}</dd>
<dt>{{ __('Private Finance correction reason') }}</dt><dd>{{ $input['reason'] }}</dd>
</dl>
<form method="post" action="{{ route('fee-payer-corrections.post') }}" autocomplete="off">
@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
@foreach($input as $key=>$value)
<input type="hidden" name="{{ $key }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
<label for="payer-confirm"><input id="payer-confirm" type="checkbox" name="confirmed" value="1" required> {{ __('I reviewed the original unpaid debt, intended payer and exact preserved tax. Record this correction once.') }}</label>
<button type="submit" class="btn btn-primary">{{ __('Post original unpaid payer correction') }}</button>
</form>
<p><a href="{{ route('fee-payer-corrections.index',['account_id'=>$preview['fee_account_id']]) }}">{{ __('Cancel and review the family account') }}</a></p>
</div>
@endsection
