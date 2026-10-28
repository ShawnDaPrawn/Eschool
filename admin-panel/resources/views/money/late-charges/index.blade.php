@extends('layouts.master')
@section('title', __('One-off late charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench late-charge-workbench">
<x-page.header :title="__('One-off late charges')" :subtitle="__('Review original overdue debt after recorded allocations. Corrections stay with the original debt; late fees never generate further late fees.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($readOnly)<p role="status">{{ __('Late charges are read-only while fees are paused. Keep original policy and invoice history.') }}</p>@endif
<p><a href="{{ route('fee-late-charges.policy',$account?['currency'=>$account->currency]:[]) }}">{{ __('Configure the explicit school late-charge rule') }}</a></p>
<h2>{{ __('Choose the original family account') }}</h2>
<ul>
@foreach($accounts as $item)
<li><a href="{{ route('fee-late-charges.index',['account_id'=>$item->id]) }}">{{ $item->name }}</a></li>
@endforeach
</ul>
{{ $accounts->links() }}
@if($account)
<h2>{{ $account->name }}</h2>
<ul>
@forelse($sources as $row)
<li>{{ $row['invoice_number'] }} · {{ $row['learner_name'] }} · {{ $row['holder_name'] }} · {{ $format($row['currency'],$row['basis_cents']) }}
@if($row['already_charged'])
{{ __('Original one-off late charge already recorded') }}
@else
<a href="{{ route('fee-late-charges.index',['account_id'=>$account->id,'original_invoice_line_id'=>$row['original_invoice_line_id'],'holder_user_id'=>$row['holder_user_id']]) }}">{{ __('Review this original overdue debt') }}</a>
@endif
</li>
@empty
<li>{{ __('No original unpaid debt is available for late-charge review.') }}</li>
@endforelse
</ul>
@endif
@if($source!==null && !$source['already_charged'])
<form method="post" action="{{ route('fee-late-charges.review') }}" autocomplete="off" aria-label="{{ __('Review original overdue debt') }}">
@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="operation_id" value="{{ $operation }}">
<input type="hidden" name="original_invoice_line_id" value="{{ $source['original_invoice_line_id'] }}">
<input type="hidden" name="holder_user_id" value="{{ $source['holder_user_id'] }}">
<fieldset @disabled($readOnly)><legend>{{ __('Review the original debt and school rule') }}</legend>
<p>{{ $source['invoice_number'] }} · {{ $source['learner_name'] }} · {{ $source['holder_name'] }}</p>
<x-page.field id="late-reason" name="reason" type="textarea" :restore-old-input="false" :label="__('Private Finance late-charge reason')" minlength="10" maxlength="2000" required />
<button type="submit" class="btn btn-primary">{{ __('Review configured one-off late charge') }}</button>
</fieldset>
</form>
@endif
<h2>{{ __('Retained original late-charge history') }}</h2>
<ul>
@foreach($history as $row)
<li>{{ __('Late charge') }} {{ $row->id }} · {{ $format($row->currency,$row->amount_cents) }} · {{ __('Original debt line') }} {{ $row->original_invoice_line_id }} · {{ __('Replacement charge') }} {{ $row->replacement_charge_id }}</li>
@endforeach
</ul>
{{ $history->links() }}
</div>
@endsection
