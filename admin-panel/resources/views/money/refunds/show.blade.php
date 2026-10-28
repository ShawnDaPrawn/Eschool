@extends('layouts.master')
@section('title', __('Fee refunds'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Fee refunds')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Refund history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-refunds.index')">{{ __('Back to fee refunds') }}</x-m3.button><h2>FEE-REF-{{ $refund->id }} · {{ $state }}</h2>
<dl><dt>{{ __('Original receipt') }}</dt><dd><a href="{{ route('fee-payments.show',['payment'=>$refund->payment_id]) }}">{{ $refund->snapshot['payment_number'] }}</a></dd><dt>{{ __('Original receipt holder') }}</dt><dd>{{ $refund->snapshot['holder_name'] }}</dd><dt>{{ __('Refund amount') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($refund->amount_cents,$refund->currency)->format() }}</dd><dt>{{ __('Refund method') }}</dt><dd>{{ $refund->method==='cash'?__('Cash'):__('EFT') }}</dd><dt>{{ __('Private Finance explanation') }}</dt><dd>{{ $refund->private_explanation }}</dd></dl>
<p>{{ __('A reservation prevents this credit being allocated or refunded again. Confirm the original holder and payout evidence before recording an outside payment.') }}</p>
@if($canWrite && $state==='preview')<form method="post" action="{{ route('fee-refunds.reserve',['refund'=>$refund->id]) }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])<x-m3.checkbox name="confirmed" :label="__('I reviewed the original holder, receipt credit, method and refund policy.')" required /><x-m3.button type="submit">{{ __('Approve and reserve refund') }}</x-m3.button></form>@endif
@if($canWrite && $state==='reserved')
<h3>{{ __('Record verified outside payout') }}</h3><form method="post" action="{{ route('fee-refunds.payout',['refund'=>$refund->id]) }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])
<x-m3.text-field name="payout_reference" :label="__('Unique payout reference')" maxlength="96" autocomplete="off" required /><x-m3.text-field name="private_evidence" :label="__('Private payout verification')" minlength="5" maxlength="1200" autocomplete="off" required />
<x-m3.checkbox name="verified_original_holder" :label="__('I verified payment to the original receipt holder.')" required /><x-m3.checkbox name="confirmed" :label="__('I confirm this outside payout occurred and has not already been recorded.')" required /><x-m3.button type="submit">{{ __('Record verified refund payout') }}</x-m3.button></form>
<h3>{{ __('Cancel unpaid reservation') }}</h3><form method="post" action="{{ route('fee-refunds.cancel',['refund'=>$refund->id]) }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])<x-m3.text-field name="private_evidence" :label="__('Private cancellation reason')" minlength="5" maxlength="1200" autocomplete="off" required /><x-m3.checkbox name="confirmed" :label="__('I confirm no outside payout occurred for this reservation.')" required /><x-m3.button variant="outlined" type="submit">{{ __('Cancel refund reservation') }}</x-m3.button></form>@endif
<h3>{{ __('Immutable refund actions') }}</h3>@foreach($actions as $action)<p>{{ $action->action }} · {{ $action->created_at->format('Y-m-d H:i') }} · {{ __('Ledger entry') }} {{ $action->ledger_entry_id }}</p>@if($action->private_evidence)<p>{{ $action->private_evidence }}</p>@endif @endforeach
</div>@endsection
