@extends('layouts.master')
@section('title', __('School wallet funding receipt'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('School wallet funding receipt')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->name }}</p>
<dl><dt>{{ __('Receipt') }}</dt><dd>{{ $receipt->id }}</dd><dt>{{ __('Reference') }}</dt><dd>{{ $receipt->source_reference }}</dd><dt>{{ __('Funding method') }}</dt><dd>{{ $receipt->method==='cash'?__('Cash'):__('EFT') }}</dd><dt>{{ __('Date funding received') }}</dt><dd>{{ $receipt->received_on }}</dd><dt>{{ __('Amount') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($receipt->amount_cents,$receipt->currency)->format() }}</dd></dl>
<p>{{ __('Verified school-owned funding credited to the master school wallet.') }}</p>
<details><summary>{{ __('Private verification evidence') }}</summary><p>{{ $receipt->evidence }}</p></details>
<x-m3.button type="button" data-wallet-print>{{ __('Print receipt') }}</x-m3.button>
<a href="{{ route('wallet-funding.index') }}">{{ __('Back to school wallet funding') }}</a></div>@endsection
@section('js')<script defer src="{{ asset('assets/js/custom/wallet-funding.js') }}"></script>@endsection
