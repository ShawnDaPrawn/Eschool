@extends('layouts.master')
@section('title', __('Review adult withdrawal'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Review adult withdrawal')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if(!$canWrite)<p>{{ __('Wallet history is read-only while wallets are disabled.') }}</p>@endif
    <dl><dt>{{ __('Request') }}</dt><dd>{{ $receipt['reference'] }}</dd><dt>{{ __('Original holder') }}</dt><dd>{{ $holder->first_name }} {{ $holder->last_name }}</dd><dt>{{ __('Amount reserved') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($row->amount_cents,$row->currency)->format() }}</dd><dt>{{ __('Method') }}</dt><dd>{{ $row->method==='cash'?__('Cash'):__('Bank EFT') }}</dd><dt>{{ __('Status') }}</dt><dd>{{ match($receipt['status']){'approved'=>__('Approved, awaiting actual payment'),'paid'=>__('Paid'),'cancelled'=>__('Cancelled'),'rejected'=>__('Rejected'),default=>__('Awaiting Finance approval')} }}</dd></dl>
    @if($receipt['pickup_starts_at'])<p>{{ __('Reserved cash pickup') }}: {{ \Carbon\CarbonImmutable::parse($receipt['pickup_starts_at'])->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d H:i T') }}</p>@endif
    @if($row->private_bank_instructions)<details><summary>{{ __('Private original-holder bank instructions') }}</summary><p>{{ $row->private_bank_instructions }}</p></details>@endif
    @if($row->private_reason)<details><summary>{{ __('Private original-holder return reason') }}</summary><p>{{ $row->private_reason }}</p></details>@endif
    @if($receipt['status']==='requested')
        <h2>{{ __('Approve original-holder withdrawal') }}</h2>
        @if(!$canApprove)<p>{{ __('A different current Finance operator must approve your withdrawal.') }}</p>@endif
        <form method="post" action="{{ route('wallet-withdrawals.approve',['withdrawal'=>$row->id]) }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>0])
            <fieldset @disabled(!$canApprove)><legend>{{ __('Finance approval, without sending payment') }}</legend>
                <label><input type="checkbox" name="verified_original_holder" value="1" required> {{ __('I verified the original adult holder and their cash pickup or bank instructions.') }}</label>
                <x-m3.button type="submit">{{ __('Approve withdrawal') }}</x-m3.button>
            </fieldset>
        </form>
    @endif
    @if($receipt['status']==='approved')
        <h2>{{ __('Record actual original-holder payout') }}</h2>
        <p>{{ __('Complete the outside cash or bank payment first. Recording this form does not send money.') }}</p>
        <form method="post" action="{{ route('wallet-withdrawals.paid',['withdrawal'=>$row->id]) }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>0])
            <fieldset @disabled(!$canApprove)><legend>{{ __('Verified actual payment') }}</legend>
                <x-m3.text-field name="payout_reference" :label="__('Actual cash or bank payout reference')" maxlength="96" required />
                <x-m3.text-field name="paid_on" type="date" :label="__('Actual paid date')" :value="now(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->toDateString()" required />
                <x-m3.text-field name="private_evidence" type="textarea" :label="__('Private verified payout proof')" minlength="10" maxlength="2000" required />
                <label><input type="checkbox" name="verified_original_holder" value="1" required> {{ __('I verified that the payment went to this original adult holder.') }}</label>
                <label><input type="checkbox" name="verified_actual_payout" value="1" required> {{ $row->method==='cash'?__('I handed the original holder cash from my desk and verified the amount and receipt.'):__('I verified the completed bank payment, amount, date and reference.') }}</label>
                <x-m3.button type="submit">{{ __('Record verified payout') }}</x-m3.button>
            </fieldset>
        </form>
    @endif
    @if(in_array($receipt['status'],['requested','approved'],true))
        <h2>{{ __('Release the reservation without outside payment') }}</h2>
        <form method="post" action="{{ route($receipt['status']==='requested'?'wallet-withdrawals.reject':'wallet-withdrawals.cancel',['withdrawal'=>$row->id]) }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>0])
            <fieldset @disabled(!$canWrite)><legend>{{ __('Return funds to the original wallet') }}</legend>
                <x-m3.text-field name="private_reason" type="textarea" :label="__('Private reservation release reason')" minlength="5" maxlength="1000" required />
                <label><input type="checkbox" name="confirmed" value="1" required> {{ __('No outside payout has been made; return only the original reserved wallet money.') }}</label>
                <x-m3.button type="submit" variant="outlined">{{ $receipt['status']==='requested'?__('Reject withdrawal'):__('Cancel approved withdrawal') }}</x-m3.button>
            </fieldset>
        </form>
    @endif
    @if($receipt['status']==='paid')<p><a href="{{ route('wallet-withdrawals.receipt',['withdrawal'=>$row->id]) }}">{{ __('Open withdrawal payout receipt') }}</a></p>@endif
    <p><a href="{{ route('wallet-withdrawals.index') }}">{{ __('Back to adult withdrawals') }}</a></p>
</div>
@endsection
