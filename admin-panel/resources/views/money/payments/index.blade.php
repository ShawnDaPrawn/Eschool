@extends('layouts.master')
@section('title', __('Fee payments'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-m3.button variant="outlined" :href="route('bank-statements.index')">{{ __('Bank statement matching') }}</x-m3.button>
    <x-page.header :title="__('Fee payments')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if($canWrite)<x-m3.button :href="route('fee-payments.new')">{{ __('Record verified receipt') }}</x-m3.button>@else<p>{{ __('Payment history is read-only while fees are disabled.') }}</p>@endif
    <form method="get"><x-m3.text-field name="search" type="search" :label="__('Find a family or receipt reference')" :value="request('search')" /><x-m3.button type="submit" variant="outlined">{{ __('Search') }}</x-m3.button></form>
    <h2 class="m3-title-large">{{ __('School allocation rule') }}</h2>
    <p>{{ __('Choose the order before allocating. Receipts can remain held credit while the rule is undecided.') }}</p>
    <p>{{ __('Oldest due first allocates across eligible charges by due date. Tuition first pays annual tuition before other charges, using the oldest due date within each group. Credit stays held until you allocate it.') }}</p>
    <form method="post" action="{{ route('fee-payments.policy') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
        <fieldset @disabled(!$canWrite)><legend>{{ __('School allocation rule') }}</legend>
            <x-m3.select name="allocation_order" :label="__('Allocation order')" :options="['oldest_first'=>__('Oldest due first'),'tuition_first'=>__('Tuition first, then oldest due')]" :placeholder="__('Choose the school rule')" :value="$policy?->allocation_order" required />
            <x-m3.button type="submit" variant="outlined">{{ __('Save allocation rule') }}</x-m3.button>
        </fieldset>
    </form>
    <h2 class="m3-title-large">{{ __('Recorded receipts') }}</h2>
    @if($payments->isEmpty())<x-page.empty-state :title="__('No ledger receipts yet')" :message="__('Record confirmed cash or EFT against an explicit family and holder. A receipt starts as held credit.')" />
    @else<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Recorded receipts') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Recorded receipts') }}</caption><thead><tr><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('Received on') }}</th><th scope="col">{{ __('Method') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead>
        <tbody>@foreach($payments as $payment)<tr><td><a href="{{ route('fee-payments.show',['payment'=>$payment->id]) }}">FEE-PAY-{{ $payment->id }}</a></td><td><a href="{{ route('fee-payments.statement',['account'=>$payment->fee_account_id]) }}">{{ $payment->getAttribute('account_name') }}</a></td><td>{{ $payment->received_on->toDateString() }}</td><td>{{ $payment->method==='cash'?__('Cash'):__('EFT') }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($payment->amount_cents,$payment->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>@include('money.billing.pagination',['paginator'=>$payments])@endif
</div>
@endsection
