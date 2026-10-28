@extends('layouts.master')
@section('title', __('Fee refunds'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Fee refunds')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Refund history is read-only while fees are disabled.') }}</p>@endif
<p>{{ __('Approve a reservation before an outside cash or EFT refund. Record only verified payment to the original receipt holder; cancellation returns the credit to that receipt.') }}</p>
@if($canWrite)<x-m3.button :href="route('fee-refunds.new')">{{ __('Prepare receipt refund') }}</x-m3.button>@endif
<h2>{{ __('School manual refund policy') }}</h2><form method="post" action="{{ route('fee-refunds.policy') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Permitted refund methods and approval') }}</legend>
<x-m3.select name="cash_allowed" :label="__('Cash refunds permitted')" :options="['0'=>__('No'),'1'=>__('Yes')]" :value="$policy ? ($policy->cash_allowed?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="eft_allowed" :label="__('EFT refunds permitted')" :options="['0'=>__('No'),'1'=>__('Yes')]" :value="$policy ? ($policy->eft_allowed?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="independent_approval" :label="__('Separate Finance approval')" :options="['0'=>__('Same Finance operator may approve'),'1'=>__('Another Finance operator must approve')]" :value="$policy ? ($policy->independent_approval?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.button type="submit">{{ __('Save manual refund policy') }}</x-m3.button></fieldset></form>
<h2>{{ __('Receipt refund history') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Receipt refund history') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Receipt refund history') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Original receipt') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead><tbody>
@foreach($refunds as $refund)<tr><td><a href="{{ route('fee-refunds.show',['refund'=>$refund->id]) }}">FEE-REF-{{ $refund->id }}</a></td><td>{{ $refund->snapshot['payment_number'] }}</td><td>{{ $actions->get($refund->id)?->last()?->action??'preview' }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($refund->amount_cents,$refund->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>{{ $refunds->links() }}</div>@endsection
