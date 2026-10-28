@extends('layouts.master')
@section('title', __('Adult wallet withdrawals'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Adult wallet withdrawals')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <p>{{ __('Requests reserve only the original adult wallet. Finance approval sends no money; record a payout only after verifying actual payment to that holder.') }}</p>
    @if(!$canWrite)<p>{{ __('Wallet history is read-only while wallets are disabled.') }}</p>@endif
    <h2>{{ __('Cash-or-bank withdrawal rule') }}</h2>
    <p>{{ __('For rand wallets the boundary is R250. Choose whether exactly R250 is cash or bank. For another currency, enter the school-approved threshold explicitly.') }}</p>
    <form method="post" action="{{ route('wallet-withdrawals.policy') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
        <fieldset @disabled(!$canWrite || !$currency)><legend>{{ __('Explicit school withdrawal policy') }}</legend>
            <x-m3.text-field name="cash_threshold_amount" :label="__('Cash-or-bank boundary amount')" :value="$currency==='ZAR'?'250.00':($policy?\App\Services\Platform\Ledger\Money::ofCents($policy->cash_threshold_cents,$policy->currency)->toDecimalString():null)" :readonly="$currency==='ZAR'" inputmode="decimal" required />
            <x-m3.select name="cash_at_boundary" :label="__('Method at exactly the boundary')" :options="['1'=>__('Cash'),'0'=>__('Bank EFT')]" :value="$policy?($policy->cash_at_boundary?'1':'0'):null" :placeholder="__('Choose explicitly')" required />
            <x-m3.select name="accepting_requests" :label="__('Accept new withdrawal requests')" :options="['1'=>__('Yes'),'0'=>__('No')]" :value="$policy?($policy->accepting_requests?'1':'0'):null" :placeholder="__('Choose explicitly')" required />
            <x-m3.button type="submit">{{ __('Save withdrawal policy') }}</x-m3.button>
        </fieldset>
    </form>
    <h2>{{ __('Cash pickup slots') }}</h2><p>{{ __('Publish available collection times in the school timezone.') }} {{ $timezone }}</p>
    <form method="post" action="{{ route('wallet-withdrawals.slots.publish') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>0])
        <fieldset @disabled(!$canWrite)><legend>{{ __('Publish cash pickup availability') }}</legend>
            <x-m3.text-field name="starts_at" type="datetime-local" :label="__('Pickup starts in school time')" required />
            <x-m3.text-field name="ends_at" type="datetime-local" :label="__('Pickup ends in school time')" required />
            <x-m3.text-field name="capacity" type="number" min="1" max="1000" :label="__('Available pickup places')" required />
            <x-m3.button type="submit">{{ __('Publish pickup slot') }}</x-m3.button>
        </fieldset>
    </form>
    @foreach($slots as $slot)
        <details><summary>{{ $slot->starts_at->setTimezone($timezone)->format('Y-m-d H:i') }} – {{ $slot->ends_at->setTimezone($timezone)->format('H:i') }} {{ $timezone }} · {{ $slot->capacity }} {{ __('places') }}</summary>
            @if(in_array($slot->id,$closed,true))<p>{{ __('Closed to new bookings; existing bookings remain.') }}</p>
            @else
                <form method="post" action="{{ route('wallet-withdrawals.slots.close',['slot'=>$slot->id]) }}" data-year-safe>
                    @include('money.fee-plans.hidden',['revision'=>0])
                    <fieldset @disabled(!$canWrite)><legend>{{ __('Close new bookings') }}</legend>
                        <x-m3.text-field name="private_reason" :label="__('Private reason for closing this slot')" minlength="5" maxlength="1000" required />
                        <label><input type="checkbox" name="confirmed" value="1" required> {{ __('Existing pickup bookings will be retained.') }}</label>
                        <x-m3.button type="submit" variant="outlined">{{ __('Close pickup bookings') }}</x-m3.button>
                    </fieldset>
                </form>
            @endif
        </details>
    @endforeach
    <details><summary>{{ __('Return a departing adult holder’s available funds') }}</summary>
        <p>{{ __('Use the existing original adult wallet and verified holder instructions. Learner wallets cannot use this return. Funds remain reserved until Finance approval and actual payment.') }}</p>
        <form method="post" action="{{ route('wallet-withdrawals.offboarding') }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>0])
            <fieldset @disabled(!$canWrite || !$policy?->accepting_requests)><legend>{{ __('Original adult holder return') }}</legend>
                <x-m3.select name="holder_user_id" :label="__('Original adult wallet holder')" :options="$holders" :placeholder="__('Choose explicitly')" required />
                <x-m3.text-field name="amount" :label="__('Amount to return')" inputmode="decimal" required />
                <x-m3.select name="slot_id" :label="__('Cash pickup slot when the cash rule applies')" :options="$slotOptions" :placeholder="__('Choose only for cash')" />
                <x-m3.text-field name="private_bank_instructions" type="textarea" :label="__('Original-holder bank instructions when the bank rule applies')" maxlength="2000" />
                <x-m3.text-field name="private_reason" type="textarea" :label="__('Private original-holder return reason')" minlength="5" maxlength="1000" required />
                <label><input type="checkbox" name="verified_original_holder" value="1" required> {{ __('I verified the original adult holder and their return instructions.') }}</label>
                <label><input type="checkbox" name="confirmed" value="1" required> {{ __('Reserve this amount from the original adult wallet for Finance approval.') }}</label>
                <x-m3.button type="submit">{{ __('Reserve original-holder return') }}</x-m3.button>
            </fieldset>
        </form>
    </details>
    <h2>{{ __('Withdrawal queue and history') }}</h2>
    <div class="page-table__scroll" role="region" aria-label="{{ __('Adult withdrawal requests') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Adult withdrawal requests') }}</caption><thead><tr><th scope="col">{{ __('Request') }}</th><th scope="col">{{ __('Original holder') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Method') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><td><a href="{{ route('wallet-withdrawals.show',['withdrawal'=>$row->id]) }}">WW-{{ $row->id }}</a></td><td>{{ $names[$row->holder_user_id]??'' }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row->amount_cents,$row->currency)->format() }}</td><td>{{ $row->method==='cash'?__('Cash'):__('Bank EFT') }}</td><td>{{ match($receipts[$row->id]['status']){'approved'=>__('Approved, awaiting actual payment'),'paid'=>__('Paid'),'cancelled'=>__('Cancelled'),'rejected'=>__('Rejected'),default=>__('Awaiting Finance approval')} }}</td></tr>@endforeach</tbody>
    </table></div>{{ $rows->links() }}
</div>
@endsection
