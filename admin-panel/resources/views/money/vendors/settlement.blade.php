@extends('layouts.master')
@section('title', __('Original vendor settlement'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
<x-page.header :title="__('Original vendor settlement').' '.$row->id" :subtitle="$row->private_vendor_snapshot['name']" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ $row->period_from }}–{{ $row->period_to }} · {{ __(['school_internal'=>'School sales recognition','cash'=>'Verified cash payout','eft'=>'Verified EFT payout'][$row->method]) }}</p>
<p>{{ $row->private_vendor_snapshot['settlement_terms'] }}</p>
@if($row->method==='eft')<p>{{ __('Original merchant bank instructions') }}: {{ $row->private_vendor_snapshot['private_bank_instructions'] }}</p>@endif
<dl>@foreach(['gross_cents'=>'Original gross','sales_net_cents'=>'VAT-exclusive sales net','vat_cents'=>'VAT included','collector_fee_cents'=>'Collector fee','payout_cents'=>'Vendor settlement amount'] as $key=>$label)<dt>{{ __($label) }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($row->$key,$row->currency)->format() }}</dd>@endforeach</dl>
<p>{{ __('The school is the collector. NavigateEd collection remains disabled and collection accounts remain at zero.') }}</p>
<ul>@foreach($lines as $line)<li><a href="{{ route('vendor-orders.show',['order'=>$line->order_id]) }}">{{ __('Original order') }} {{ $line->order_id }}</a> · {{ \App\Services\Platform\Ledger\Money::ofCents($line->gross_cents,$row->currency)->format() }}</li>@endforeach</ul>
@if($outcome)
<p role="status">{{ __('Original vendor settlement recorded. Do not pay this batch again.') }}</p><p>{{ __('Actual settlement date') }}: {{ $outcome->paid_on }}</p>
@elseif(!$approval)
<p>{{ __('Another Finance operator must approve this original batch before settlement. No money has moved.') }}</p>
@if($canWrite && $row->prepared_by!==(int)auth()->id())
<form method="post" action="{{ route('vendor-settlements.approve',['settlement'=>$row->id]) }}" aria-label="{{ __('Approve original vendor settlement') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I independently reviewed the original merchant, orders and exact settlement amount.') }}</label><x-m3.button type="submit">{{ __('Approve original vendor settlement') }}</x-m3.button></form>
@endif
@else
<p>{{ __('Independent settlement approval recorded. No money has moved.') }}</p>
<form method="post" action="{{ route('vendor-settlements.finish',['settlement'=>$row->id]) }}" aria-label="{{ __('Record original vendor settlement') }}" data-year-safe autocomplete="off">@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Record original vendor settlement') }}</legend>
<x-page.field id="settlement-paid-on" type="date" :restore-old-input="false" name="paid_on" :label="__('Actual settlement date')" required />
@if($row->method!=='school_internal' && $row->payout_cents>0)
<x-page.field id="settlement-reference" :restore-old-input="false" name="payout_reference" :label="__('Original actual payout reference')" minlength="3" maxlength="191" required />
<label><input type="checkbox" name="verified_actual_payout" value="1" required> {{ __('I independently verified this completed cash or EFT payout. This form does not send money.') }}</label>
@endif
<x-page.field id="settlement-evidence" type="textarea" :restore-old-input="false" name="private_evidence" :label="__('Private settlement verification')" minlength="10" maxlength="4000" required />
<label><input type="checkbox" name="verified_original_vendor" value="1" required> {{ __('I verified the original merchant and retained settlement instructions.') }}</label>
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('Record this exact original settlement once.') }}</label>
<x-m3.button type="submit">{{ __('Record original vendor settlement') }}</x-m3.button></fieldset></form>
@endif
<p><a href="{{ route('vendor-settlements.index',['vendor_id'=>$row->vendor_id]) }}">{{ __('Back to vendor settlements') }}</a></p>
</div>
@endsection
