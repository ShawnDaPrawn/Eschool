@extends('layouts.master')
@section('title', __('Fee credit notes'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Prepare credit note')" />
    <x-m3.button variant="text" :href="route('fee-credit-notes.index')">{{ __('Back to credit notes') }}</x-m3.button>
    @if(!$canWrite)<p>{{ __('Credit history is read-only while fees are disabled.') }}</p>@endif
    @if(!$charge)
    <form method="get"><x-m3.select name="account_id" :label="__('Family account')" :options="$accounts->pluck('name','id')" :placeholder="__('Choose a family account')" :value="$account?->id" required /><x-m3.button type="submit" variant="outlined">{{ __('Load family account') }}</x-m3.button></form>
    @if($account)<h2 class="m3-title-large">{{ $account->name }}</h2>
    <p>{{ __('Choose the recorded learner and holder share to correct. Other holder shares are not changed.') }}</p>
    @if($charges===[])<p>{{ __('No posted invoice shares yet.') }}</p>@else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Invoice shares by learner and holder') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Invoice shares by learner and holder') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Original share') }}</th><th scope="col">{{ __('Net charge') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead>
        <tbody>@foreach($charges as $row)<tr><td>{{ $row['learner_name'] }}</td><td>{{ $row['holder_name'] }}</td><td>{{ $row['invoice_number'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['gross_charged_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['charged_cents'],$account->currency)->format() }}</td><td>@if($canWrite && $row['charged_cents']>0)<a href="{{ route('fee-credit-notes.new',['invoice_line_id'=>$row['invoice_line_id'],'holder_user_id'=>$row['holder_user_id']]) }}">{{ __('Credit this share') }}</a>@endif</td></tr>@endforeach</tbody>
    </table></div></div>@endif @endif
    @else
    <h2 class="m3-title-large">{{ $account->name }} · {{ $charge['learner_name'] }}</h2>
    <p>{{ __('Holder') }}: {{ $charge['holder_name'] }} · {{ $charge['invoice_number'] }}</p>
    <p>{{ __('Remaining charge available to credit') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($charge['charged_cents'],$account->currency)->format() }}</p>
    <p>{{ __('The credit reduces unpaid debt first. Paid amounts return to the original holder receipts as held credit, preserving learner restrictions.') }}</p>
    <form method="post" action="{{ route('fee-credit-notes.review') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>0])
        <input type="hidden" name="invoice_line_id" value="{{ $charge['invoice_line_id'] }}"><input type="hidden" name="holder_user_id" value="{{ $charge['holder_user_id'] }}">
        <fieldset @disabled(!$canWrite || $charge['charged_cents']===0)><legend>{{ __('Correction details') }}</legend>
            <x-m3.text-field name="amount" :label="__('Credit amount')" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" autocomplete="off" required />
            <x-m3.select name="reason_code" :label="__('Correction category')" :options="\App\Services\Money\FeeCreditNotes::reasonLabels()" :placeholder="__('Choose a correction category')" required />
            <x-m3.text-field name="reason" :label="__('Private Finance explanation')" maxlength="1200" minlength="5" autocomplete="off" required />
            <p>{{ __('This explanation is retained for Finance only. Parents see the correction category and their own amounts.') }}</p>
            <x-m3.button type="submit">{{ __('Review credit note') }}</x-m3.button>
        </fieldset>
    </form>
    @endif
</div>
@endsection
