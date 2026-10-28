@extends('layouts.master')
@section('title', __('Review configured one-off late charge'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench late-charge-workbench">
<x-page.header :title="__('Review configured one-off late charge')" :subtitle="__('This creates one invoice under the explicit school rule. It moves no paid receipt credit and does not affect academic access.')" />
<dl>
<dt>{{ __('Original invoice') }}</dt><dd>{{ $preview['invoice_number'] }}</dd>
<dt>{{ __('Learner') }}</dt><dd>{{ $preview['learner_name'] }}</dd>
<dt>{{ __('Original payer') }}</dt><dd>{{ $preview['holder']['name'] }}</dd>
<dt>{{ __('Actual overdue basis after allocations') }}</dt><dd>{{ $format($preview['currency'],$preview['basis_cents']) }}</dd>
<dt>{{ __('Separate available receipt credit') }}</dt><dd>{{ $format($preview['currency'],$preview['held_credit_cents']) }}</dd>
<dt>{{ __('Days overdue') }}</dt><dd>{{ $preview['days_overdue'] }}</dd>
<dt>{{ __('School rule revision') }}</dt><dd>{{ $preview['policy_revision'] }}</dd>
<dt>{{ __('New gross late charge including VAT') }}</dt><dd>{{ $format($preview['currency'],$preview['amount_cents']) }}</dd>
<dt>{{ __('VAT included') }}</dt><dd>{{ $format($preview['currency'],$preview['vat_cents']) }}</dd>
<dt>{{ __('New invoice due date') }}</dt><dd>{{ $preview['due_on'] }}</dd>
<dt>{{ __('Private Finance late-charge reason') }}</dt><dd>{{ $input['reason'] }}</dd>
</dl>
<p>{{ __('Held credit stays separate until explicitly allocated. Review that position before confirming a late charge.') }}</p>
<form method="post" action="{{ route('fee-late-charges.post') }}" autocomplete="off">
@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
@foreach($input as $key=>$value)
<input type="hidden" name="{{ $key }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
<label for="late-confirm"><input id="late-confirm" type="checkbox" name="confirmed" value="1" required> {{ __('I reviewed the original overdue debt, separate credit and exact school rule. Record this one-off invoice once.') }}</label>
<button type="submit" class="btn btn-primary">{{ __('Post reviewed one-off late charge') }}</button>
</form>
<p><a href="{{ route('fee-late-charges.index',['account_id'=>$preview['fee_account_id']]) }}">{{ __('Cancel and review the family account') }}</a></p>
</div>
@endsection
