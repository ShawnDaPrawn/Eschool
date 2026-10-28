@extends('layouts.master')
@section('title', __('Wallet withdrawal receipt'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Wallet withdrawal receipt')" />
    <p>{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->name }}</p>
    <dl><dt>{{ __('Receipt') }}</dt><dd>{{ $receipt['payout_reference'] }}</dd><dt>{{ __('Request') }}</dt><dd>{{ $receipt['reference'] }}</dd><dt>{{ __('Original holder') }}</dt><dd>{{ $holder->first_name }} {{ $holder->last_name }}</dd><dt>{{ __('Amount paid') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($receipt['amount_cents'],$receipt['currency'])->format() }}</dd><dt>{{ __('Method') }}</dt><dd>{{ $receipt['method']==='cash'?__('Cash'):__('Bank EFT') }}</dd><dt>{{ __('Actual paid date') }}</dt><dd>{{ $receipt['paid_on'] }}</dd></dl>
    <p>{{ __('This receipt records an already verified payout from the original adult wallet.') }}</p>
    <x-m3.button type="button" data-wallet-print>{{ __('Print receipt') }}</x-m3.button>
    <p><a href="{{ route('wallet-withdrawals.show',['withdrawal'=>$receipt['id']]) }}">{{ __('Back to withdrawal request') }}</a></p>
</div>
@endsection
@section('js')<script defer src="{{ asset('assets/js/custom/wallet-funding.js') }}"></script>@endsection
