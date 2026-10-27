@extends('layouts.master')
@section('title', __('Bank statement matching'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Bank statement matching')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('bank-statements.show',['line'=>$line->id])">{{ __('Back to bank transaction') }}</x-m3.button>
@include('money.bank.row')
@if($preview['kind']==='matched')
<h2>{{ __('Review EFT match') }}</h2><p>{{ __('Family account') }}: {{ $preview['account_name'] }}</p><p>{{ __('Holder whose share this pays') }}: {{ $preview['holder_name'] }}</p>
<p>{{ __('Learner pin') }}: {{ $preview['learner_name'] ?: __('Any eligible learner for this holder') }}</p>
@if($preview['existing_payment_id'])<p>{{ __('This bank transaction will link to its existing EFT receipt. No second receipt or posting will be created.') }}</p>@else<p>{{ __('Confirmation creates one verified EFT receipt as held credit. Review allocation separately before it reduces invoice debt.') }}</p>@endif
@else<h2>{{ __('Review bank exclusion') }}</h2><p>{{ __('This transaction will be retained as excluded. No money will be posted.') }}</p>@endif
<p>{{ $preview['reason'] }}</p>
<form method="post" action="{{ route('bank-statements.resolve',['line'=>$line->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
@foreach(['kind','fee_account_id','holder_user_id','pinned_learner_id','reason','fingerprint'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $preview[$field] }}">@endforeach
<x-m3.checkbox name="confirmed" value="1" :label="__('I verified the bank evidence, payer and legacy history and confirm this resolution. Resolved bank history cannot be edited.')" required />
<x-m3.button type="submit">{{ __('Confirm bank resolution once') }}</x-m3.button></form>

</div>
@endsection
