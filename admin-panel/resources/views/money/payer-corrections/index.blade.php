@extends('layouts.master')
@section('title', __('Unpaid payer corrections'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench payer-correction-workbench">
<x-page.header :title="__('Unpaid payer corrections')" :subtitle="__('Review an original unpaid share and its explicitly configured recipient payer. Original invoices, payments, allocations and receipt credits are retained.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($readOnly)<p role="status">{{ __('Payer corrections are read-only while fees are paused. Original correction history is retained.') }}</p>@endif
<h2>{{ __('Choose the original family account') }}</h2>
<ul>
@foreach($accounts as $item)
<li><a href="{{ route('fee-payer-corrections.index',['account_id'=>$item->id]) }}">{{ $item->name }}</a></li>
@endforeach
</ul>
{{ $accounts->links() }}
@if($statement!==null)
<h2>{{ $account->name }}</h2>
<ul>
@foreach($statement['charges'] as $row)
@if($row['outstanding_cents']>0)
<li>{{ $row['invoice_number'] }} · {{ $row['learner_name'] }} · {{ $row['holder_name'] }} · {{ $row['currency'] }} {{ number_format($row['outstanding_cents']/100,2) }} <a href="{{ route('fee-payer-corrections.index',['account_id'=>$account->id,'invoice_line_id'=>$row['invoice_line_id'],'holder_user_id'=>$row['holder_user_id']]) }}">{{ __('Review this unpaid payer share') }}</a></li>
@endif
@endforeach
</ul>
@endif
@if($source!==null)
<form method="post" action="{{ route('fee-payer-corrections.review') }}" autocomplete="off" aria-label="{{ __('Review original unpaid payer correction') }}">
@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="operation_id" value="{{ $operation }}">
<input type="hidden" name="invoice_line_id" value="{{ $source['invoice_line_id'] }}">
<input type="hidden" name="holder_user_id" value="{{ $source['holder_user_id'] }}">
<fieldset @disabled($readOnly)><legend>{{ __('Original unpaid share') }}</legend>
<p>{{ $source['invoice_number'] }} · {{ $source['learner_name'] }} · {{ $source['holder_name'] }}</p>
<x-page.field id="payer-recipient" name="new_holder_user_id" type="select" :restore-old-input="false" :label="__('Intended configured payer')" :options="$holderOptions" required />
<x-page.field id="payer-amount" name="amount" :restore-old-input="false" :label="__('Original unpaid amount to correct')" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-page.field id="payer-reason" name="reason" type="textarea" :restore-old-input="false" :label="__('Private Finance correction reason')" minlength="10" maxlength="1200" required />
<x-m3.button type="submit">{{ __('Review original unpaid correction') }}</x-m3.button>
</fieldset>
</form>
@endif
<h2>{{ __('Retained original correction history') }}</h2>
<ul>
@foreach($history as $row)
<li>{{ __('Correction') }} {{ $row->id }} · {{ $row->currency }} {{ number_format($row->amount_cents/100,2) }} · <a href="{{ route('fee-credit-notes.show',['note'=>$row->credit_note_id]) }}">{{ __('Original unpaid credit') }}</a> · {{ __('Replacement charge') }} {{ $row->replacement_charge_id }}</li>
@endforeach
</ul>
{{ $history->links() }}
</div>
@endsection
