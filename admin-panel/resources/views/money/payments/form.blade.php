@extends('layouts.master')
@section('title', __('Fee payments'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Record verified receipt')" />
    <x-m3.button variant="text" :href="route('fee-payments.index')">{{ __('Back to fee payments') }}</x-m3.button>
    <p>{{ __('Use the unique cash receipt or bank transaction reference, not the reusable family payment reference.') }}</p>
    <form method="get">
        <x-m3.select name="account_id" :label="__('Family account')" :options="$accounts->pluck('name','id')" :placeholder="__('Choose a family account')" :value="$account?->id" required />
        <x-m3.button type="submit" variant="outlined">{{ __('Load family account') }}</x-m3.button>
    </form>
    @if($account)
    <p>{{ __('Family account') }}: {{ $account->name }} · {{ $account->currency }}</p>
    <p>{{ __('Choose the holder whose share this payment should cover. It will not automatically pay another holder share.') }}</p>
    @if(!$canWrite)<p>{{ __('Enable fee payments and configure an explicit holder before recording a receipt.') }}</p>@endif
    <form method="post" action="{{ route('fee-payments.create') }}" data-year-safe data-fee-payment-form data-eligible="{{ json_encode($eligible,JSON_HEX_APOS|JSON_HEX_TAG|JSON_HEX_QUOT|JSON_HEX_AMP) }}">
        @include('money.fee-plans.hidden',['revision'=>0])
        <input type="hidden" name="fee_account_id" value="{{ $account->id }}">
        <fieldset @disabled(!$canWrite)><legend>{{ __('Confirmed receipt details') }}</legend>
            <x-m3.select name="holder_user_id" :label="__('Holder whose share this pays')" :options="$holders" :placeholder="__('Choose the holder')" required />
            <x-m3.select name="pinned_learner_id" :label="__('Keep this payment for one learner')" :options="$learnerOptions" :placeholder="__('Any eligible learner for this holder')" />
            <x-m3.select name="method" :label="__('Receipt method')" :options="['cash'=>__('Cash'),'eft'=>__('EFT')]" :placeholder="__('Choose receipt method')" required />
            <x-m3.text-field name="source_reference" :label="__('Unique cash receipt or bank transaction reference')" maxlength="96" autocomplete="off" required />
            <x-m3.text-field name="amount" :label="__('Amount received')" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" autocomplete="off" required />
            <x-m3.text-field name="received_on" type="date" :label="__('Received on')" :value="$today" :max="$today" required />
            <x-m3.checkbox name="confirmed" value="1" :label="__('I verified the received funds and confirm this receipt has not already been recorded.')" required />
            <p>{{ __('Recording holds the money as credit. Review allocation separately before it reduces invoice debt.') }}</p>
            <x-m3.button type="submit">{{ __('Record receipt once') }}</x-m3.button>
        </fieldset>
    </form>
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/fee-payments.js') }}?v={{ filemtime(public_path('assets/js/custom/fee-payments.js')) }}" defer></script>@endsection
