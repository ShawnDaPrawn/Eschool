@extends('layouts.master')
@section('title', __('Cash custody handovers'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Cash custody handovers')" :subtitle="__('Hand over existing school cash to another operator. The recipient must confirm the actual notes and coins received.')" />
<p><a href="{{ route('cashups.index') }}">{{ __('Daily cash-up') }}</a></p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@foreach($available as $currency => $a)
<section><h2>{{ $currency }}</h2>
<dl>@foreach(['book_cents'=>'Expected cash in custody','shortage_cents'=>'Recorded physical shortage','reserved_cents'=>'Pending outgoing handovers','available_cents'=>'Available cash in your custody'] as $key => $label)<dt>{{ __($label) }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($a[$key],$currency)->format() }}</dd>@endforeach</dl>
@if(!$a['healthy'])<p role="alert">{{ __('Reconcile original cash receipts, payouts and projections before paying cash.') }}</p>@endif
@if($a['needs_recount'])<p role="status">{{ __('Record a fresh physical cash count before paying or handing over this cash.') }}</p>@endif
</section>
@endforeach
<p>{{ __('A proposal reserves the sender’s cash. Acceptance moves custody once without changing the school ledger. Reject or cancel only when no cash was handed over.') }}</p>
@if($currencies!==[] && $recipients!==[])
<form method="post" action="{{ route('cashups.handovers.propose') }}" aria-label="{{ __('Propose actual cash handover') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>0])
<x-page.field id="handover-recipient" type="select" :restore-old-input="false" name="recipient_id" :label="__('Receiving cash operator')" :options="$recipients" required />
<x-page.field id="handover-currency" type="select" :restore-old-input="false" name="currency" :label="__('Currency')" :options="array_combine($currencies,$currencies)" required />
<x-page.field id="handover-amount" :restore-old-input="false" name="amount" :label="__('Actual cash to hand over')" inputmode="decimal" pattern="[0-9]{1,10}(\.[0-9]{1,2})?" required maxlength="13" />
<x-page.field id="handover-evidence" type="textarea" :restore-old-input="false" name="private_evidence" :label="__('Private cash handover evidence')" minlength="10" maxlength="4000" required />
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I will hand over this actual cash to the selected operator for their independent confirmation.') }}</label>
<x-m3.button type="submit">{{ __('Propose actual cash handover') }}</x-m3.button>
</form>
@else<p>{{ __('A recorded currency and another authorized cash operator are required for a handover.') }}</p>@endif
<h2>{{ __('Your retained cash handovers') }}</h2>
@forelse($rows as $row)
@php($outcome=$outcomes->get($row->id))
<section data-handover-id="{{ $row->id }}"><h3>{{ __('Cash handover') }} {{ $row->id }} · {{ \App\Services\Platform\Ledger\Money::ofCents($row->amount_cents,$row->currency)->format() }}</h3>
<p>{{ $people[$row->sender_id] }} → {{ $people[$row->recipient_id] }}</p>
<p>{{ $row->private_evidence }}</p>
@if($outcome)<p role="status">{{ __(['accepted'=>'Cash received and confirmed','cancelled'=>'Cancelled without handing over cash','rejected'=>'Rejected without receiving cash'][$outcome->status]) }} · {{ $outcome->effective_on }}</p><p>{{ $outcome->private_evidence }}</p>
@else
@foreach($row->sender_id===(int)auth()->id()?['cancelled'=>'Cancel without handing over cash']:['accepted'=>'Confirm actual cash received','rejected'=>'Reject without receiving cash'] as $status => $label)
<form method="post" action="{{ route('cashups.handovers.finish',['handover'=>$row->id,'status'=>$status]) }}" aria-label="{{ __($label) }} {{ $row->id }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>0])
<x-page.field :id="'handover-outcome-'.$row->id.'-'.$status" type="textarea" :restore-old-input="false" name="private_evidence" :label="__('Private confirmation or refusal evidence')" minlength="10" maxlength="4000" required />
<label><input type="checkbox" name="confirmed" value="1" required> {{ __($label) }}</label>
<x-m3.button type="submit">{{ __($label) }}</x-m3.button></form>
@endforeach
@endif
</section>
@empty<p>{{ __('No cash handovers are recorded for your account.') }}</p>@endforelse
{{ $rows->links() }}
</div>
@endsection
