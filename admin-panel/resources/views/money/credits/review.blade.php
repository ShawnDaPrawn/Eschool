@extends('layouts.master')
@section('title', __('Fee credit notes'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Review credit note')" />
    <x-m3.button variant="text" :href="route('fee-credit-notes.new',['invoice_line_id'=>$preview['invoice_line_id'],'holder_user_id'=>$preview['holder_user_id']])">{{ __('Change credit details') }}</x-m3.button>
    <p>{{ $preview['invoice_number'] }} · {{ $preview['learner_name'] }} · {{ $preview['holder_name'] }}</p>
    <dl>
        @foreach(['gross_charged_cents'=>'Original share','credited_before_cents'=>'Earlier credit notes','charged_before_cents'=>'Net charge before correction','amount_cents'=>'Credit amount','unpaid_cents'=>'Debt reduction','released_cents'=>'Restored held credit','outstanding_after_cents'=>'Share still due after correction'] as $field=>$label)
        <dt>{{ __($label) }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($preview[$field],$preview['currency'])->format() }}</dd>
        @endforeach
        <dt>{{ __('Correction category') }}</dt><dd>{{ \App\Services\Money\FeeCreditNotes::reasonLabels()[$input['reason_code']] }}</dd>
        <dt>{{ __('Private Finance explanation') }}</dt><dd>{{ $input['reason'] }}</dd>
    </dl>
    <p>{{ __('Credit notes preserve original invoices and receipts. Restored funds remain held until allocated; no cash or bank payout is made.') }}</p>
    <form method="post" action="{{ route('fee-credit-notes.post') }}" data-year-safe>
        @csrf
        @foreach(['expected_school_id','expected_account_id','operation_id','invoice_line_id','holder_user_id','amount','reason_code','reason'] as $key)<input type="hidden" name="{{ $key }}" value="{{ $input[$key] }}">@endforeach
        <input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
        <fieldset @disabled(!$canWrite)><legend>{{ __('Confirm reviewed correction') }}</legend>
            <x-m3.checkbox name="confirmed" value="1" :label="__('I confirm this credit should correct the recorded share and restore any paid amount as shown.')" required />
            <x-m3.button type="submit">{{ __('Post credit note once') }}</x-m3.button>
        </fieldset>
    </form>
</div>
@endsection
