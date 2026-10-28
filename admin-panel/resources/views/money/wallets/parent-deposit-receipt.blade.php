@extends('layouts.master')
@section('title', __('Parent wallet deposit receipt'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Parent wallet deposit receipt')" />
<p>{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->name }}</p>
<dl><dt>{{ __('Receipt') }}</dt><dd>{{ $receipt['verified_receipt']['reference'] }}</dd><dt>{{ __('Original parent holder') }}</dt><dd>{{ $owner->first_name }} {{ $owner->last_name }}</dd><dt>{{ __('Actual verified amount') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($receipt['verified_receipt']['amount_cents'],$receipt['currency'])->format() }}</dd><dt>{{ __('Date funding received') }}</dt><dd>{{ $receipt['verified_receipt']['received_on'] }}</dd><dt>{{ __('Reference') }}</dt><dd>{{ $receipt['verified_receipt']['source_reference'] }}</dd><dt>{{ __('Funding method') }}</dt><dd>{{ $receipt['verified_receipt']['method']==='cash'?__('Cash'):__('EFT') }}</dd></dl>
<p>{{ __('This receipt credits the original parent wallet. It is separate from school fees and school-owned master funding.') }}</p>
<x-m3.button type="button" data-wallet-print>{{ __('Print receipt') }}</x-m3.button><p><a href="{{ route('wallet-deposits.show',['claim'=>$receipt['id']]) }}">{{ __('Back to parent deposit request') }}</a></p></div>@endsection
@section('js')<script defer src="{{ asset('assets/js/custom/wallet-funding.js') }}"></script>@endsection
