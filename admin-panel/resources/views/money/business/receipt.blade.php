@extends('layouts.master')
@section('title', __('Giving receipt'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench"><x-page.header :title="$record['receipt']" :subtitle="__('Verified donation receipt')" />
<x-m3.button variant="text" :href="route('giving-panel.index')">{{ __('Back to giving campaigns') }}</x-m3.button>
<section class="workspace-section"><dl>
@foreach(['school'=>__('School'),'campaign'=>__('Campaign'),'donor_name'=>__('Donor'),'donor_email'=>__('Donor email address'),'received_on'=>__('Received on'),'source_reference'=>__('Bank transaction reference'),'issued_at'=>__('Receipt issued')] as $key=>$label)<dt>{{ $label }}</dt><dd>{{ $record[$key]??'—' }}</dd>@endforeach
<dt>{{ __('Amount received and posted') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($record['amount_cents'],$record['currency'])->format() }}</dd></dl><p role="status">{{ __('This receipt is retained financial history. Do not record the same bank transaction again.') }}</p></section></div>
@endsection
