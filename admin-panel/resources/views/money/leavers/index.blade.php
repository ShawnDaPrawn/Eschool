@extends('layouts.master')
@section('title', __('Leaver credits'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Leaver credits')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Leaver credit history is read-only while fees are disabled.') }}</p>@endif
<p>{{ __('Original school fee contracts and each original payer share are retained. A leaver credit reduces remaining debt or restores original receipt credit; it makes no cash refund.') }}</p>
@if($canWrite)<x-m3.button :href="route('fee-leavers.new')">{{ __('Prepare leaver credit') }}</x-m3.button>@endif
<h2>{{ __('Explicit school departure credit rules') }}</h2>
<form method="post" action="{{ route('fee-leavers.policy') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('School leaver credit policy') }}</legend>
<x-m3.select name="prior_policyless_basis" :label="__('Departure rule for invoices without an original saved policy')" :options="['daily'=>__('Daily proportion'),'calendar_month'=>__('Calendar-month proportion'),'no_credit'=>__('No automatic departure credit')]" :value="$policy?->prior_policyless_basis" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="prior_reductions" :label="__('Existing fee reductions on departure')" :options="['retain_full'=>__('Retain the full original reduction'),'proportion_reductions'=>__('Proportion fee reductions to served tuition')]" :value="$policy?->prior_reductions" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="independent_approval" :label="__('Separate Finance approval')" :options="['0'=>__('Same Finance operator may approve'),'1'=>__('Another Finance operator must approve')]" :value="$policy ? ($policy->independent_approval?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<p>{{ __('Invoices with an original saved policy keep that contract. Other billing corrections stay credited. Earlier departure credits are counted so an unchanged departure cannot be refunded twice.') }}</p>
<x-m3.button type="submit">{{ __('Save leaver credit policy') }}</x-m3.button></fieldset></form>
<h2>{{ __('Reviewed departures') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Reviewed departures') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Reviewed departures') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Original invoice') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Credit') }}</th></tr></thead><tbody>
@foreach($runs as $run)<tr><td><a href="{{ route('fee-leavers.show',['run'=>$run->id]) }}">FEE-LEAVE-{{ $run->id }}</a></td><td>{{ $run->snapshot['invoice_number'] }}</td><td>{{ $run->state }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($run->amount_cents,$run->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>{{ $runs->links() }}
</div>
@endsection
