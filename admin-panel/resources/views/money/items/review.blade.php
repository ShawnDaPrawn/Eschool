@extends('layouts.master')
@section('title', __('Fee items and charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">

<x-page.header :title="__('Review item charge')" />
<p>{{ $preview['item_name'] }} · {{ $preview['account_name'] }}</p>
<p>{{ __('Item amount') }}: <strong>{{ \App\Services\Platform\Ledger\Money::ofCents($preview['amount_cents'],$preview['currency'])->format() }}</strong></p>
@include('money.items.tax-breakdown',['snapshot'=>$preview])
<p>{{ __('Unique occurrence reference') }}: {{ $input['source_reference'] }} · {{ __('Invoice due date') }}: {{ $preview['due_on'] }}</p>
@if($preview['scope']==='family')<p>{{ __('This charges the family once. The review divides the total across its learners and preserves each holder total share.') }}</p>@endif
<h2 class="m3-title-large">{{ __('Recorded holder totals') }}</h2><ul>@foreach($preview['holder_totals'] as $holder)<li>{{ $holder['name'] }} · {{ \App\Services\Platform\Ledger\Money::ofCents($holder['amount_cents'],$preview['currency'])->format() }}</li>@endforeach</ul>
@include('money.items.review-lines',['snapshot'=>$preview])
<form method="post" action="{{ route('fee-item-charges.post') }}" data-year-safe>@csrf
@foreach(['expected_school_id','expected_account_id','operation_id','fee_item_id','fee_account_id','session_year_id','learner_id','source_reference','charged_on','due_on'] as $field)@if(isset($input[$field]))<input type="hidden" name="{{ $field }}" value="{{ $input[$field] }}">@endif @endforeach
<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
<fieldset @disabled(!$canWrite)><legend>{{ __('Confirm reviewed charge') }}</legend><x-m3.checkbox name="confirmed" value="1" :label="__('I confirm this item, occurrence and payer allocation should create the invoice shown above.')" required /><x-m3.button type="submit">{{ __('Post item invoice once') }}</x-m3.button></fieldset></form>

</div>
@endsection
