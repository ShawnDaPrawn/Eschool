@extends('layouts.master')
@section('title', __('Fee items and charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">

<x-page.header :title="$invoice->number" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-item-charges.index')">{{ __('Back to item charges') }}</x-m3.button>
<x-m3.button variant="outlined" :href="route('fee-item-charges.invoice',['charge'=>$charge->id])">{{ __('Open original invoice') }}</x-m3.button>
@if(auth()->user()->can('fee-payments-manage'))<x-m3.button variant="outlined" :href="route('fee-payments.statement',['account'=>$charge->fee_account_id])">{{ __('Account statement and receipts') }}</x-m3.button>@endif
<p>{{ $charge->snapshot['item_name'] }} · {{ $charge->snapshot['account_name'] }} · <strong>{{ \App\Services\Platform\Ledger\Money::ofCents($charge->amount_cents,$charge->currency)->format() }}</strong></p>
<p>{{ __('Unique occurrence reference') }}: {{ $charge->source_reference }}</p><p>{{ __('This charge is posted. Corrections use a credit note; the original invoice is retained.') }}</p>
@if($charge->scope==='family')<p>{{ __('This charges the family once. The review divides the total across its learners and preserves each holder total share.') }}</p>@endif
@include('money.items.tax-breakdown',['snapshot'=>$charge->snapshot])
@include('money.items.review-lines',['snapshot'=>$charge->snapshot])

</div>
@endsection
