@extends('layouts.master')
@section('title', __('Bank statement matching'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Bank statement matching')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('bank-statements.index')">{{ __('Back to bank matching') }}</x-m3.button>
@include('money.bank.row')
@if($resolution)
<h2>{{ $resolution->kind==='matched'?__('Matched'):__('Excluded') }}</h2><p>{{ $resolution->reason }}</p>
@if($resolution->payment_id)<x-m3.button :href="route('fee-payments.show',['payment'=>$resolution->payment_id])">{{ __('Open matched fee receipt') }}</x-m3.button>@endif
@elseif($canWrite)
@if($line->direction==='credit')
@if($suggestion)<p>{{ __('Suggested family from exact payment reference') }}: {{ $suggestion->name }}</p>@else<p>{{ __('No exact family reference match. Choose the family from verified bank evidence.') }}</p>@endif
<form method="get"><x-m3.select name="account_id" :label="__('Family account')" :options="$accounts->pluck('name','id')" :placeholder="__('Choose a family account')" :value="$account?->id" required /><x-m3.button type="submit" variant="outlined">{{ __('Load family account') }}</x-m3.button></form>
@if($account && $holders!==[])<form method="post" action="{{ route('bank-statements.review',['line'=>$line->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="kind" value="matched"><input type="hidden" name="fee_account_id" value="{{ $account->id }}">
<x-m3.select name="holder_user_id" :label="__('Holder whose share this pays')" :options="$holders" :placeholder="__('Choose the holder')" required />
<x-m3.select name="pinned_learner_id" :label="__('Keep this payment for one learner')" :options="$learnerOptions" :placeholder="__('Any eligible learner for this holder')" />
<x-m3.text-field name="reason" :label="__('Bank match verification note')" minlength="10" maxlength="1000" autocomplete="off" required />
<x-m3.button type="submit">{{ __('Review EFT match') }}</x-m3.button></form>@endif
@else<p>{{ __('Bank debits cannot become fee receipts. Leave unresolved or record a reasoned exclusion.') }}</p>@endif
<h2>{{ __('Exclude a non-fee transaction') }}</h2><p>{{ __('Exclusion retains the bank row and its reason without posting money. A resolved row cannot be edited or matched again.') }}</p>
<form method="post" action="{{ route('bank-statements.review',['line'=>$line->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="kind" value="excluded">
<x-m3.text-field name="reason" :label="__('Reason this is not a fee receipt')" minlength="10" maxlength="1000" autocomplete="off" required />
<x-m3.button type="submit" variant="outlined">{{ __('Review bank exclusion') }}</x-m3.button></form>
@else<p>{{ __('Bank history is read-only while fees are disabled.') }}</p>@endif

</div>
@endsection
