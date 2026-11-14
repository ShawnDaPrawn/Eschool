@extends('layouts.master')
@section('title', __('Fee payments'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="$account->name.' — '.__('Account statement')" />
    <x-m3.button variant="text" :href="route('fee-payments.index')">{{ __('Back to fee payments') }}</x-m3.button>
    @if($canWrite)<x-m3.button :href="route('fee-payments.new',['account_id'=>$account->id])">{{ __('Record verified receipt') }}</x-m3.button>@endif
    <p>{{ __('These are new NavigateEd ledger receipts. Earlier fee and payment history remains separate.') }}</p>
    <dl class="money-summary">
    @foreach(['gross_charged_cents'=>__('Original invoice shares'),'credited_cents'=>__('Posted credit notes'),'charged_cents'=>__('Net charges'),'outstanding_cents'=>__('Invoice debt still due'),'credit_cents'=>__('Held holder credits')] as $key=>$label)
        <div><dt>{{ $label }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($statement[$key],$account->currency)->format() }}</dd></div>
    @endforeach
    </dl>
    <p>{{ __('A holder credit does not automatically settle another holder share. Pinned credits remain with their learner.') }}</p>
@php
$section=request('section','charges');
if(!in_array($section,['charges','credits','receipts'],true)) $section='charges';
$tabs=[];
$tabs['charges']=['label'=>__('Invoice shares'),'url'=>request()->fullUrlWithQuery(['section'=>'charges','charges_page'=>null,'credits_page'=>null,'receipts_page'=>null])];
$tabs['credits']=['label'=>__('Credit notes'),'url'=>request()->fullUrlWithQuery(['section'=>'credits','charges_page'=>null,'credits_page'=>null,'receipts_page'=>null])];
$tabs['receipts']=['label'=>__('Receipts and credit history'),'url'=>request()->fullUrlWithQuery(['section'=>'receipts','charges_page'=>null,'credits_page'=>null,'receipts_page'=>null])];
@endphp
@include('money.billing.tabs')
@if($section==='charges')
    <h2 class="m3-title-large">{{ __('Invoice shares by learner and holder') }}</h2>
    @if($charges->isEmpty())<p>{{ __('No posted invoice shares yet.') }}</p>@else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Invoice shares by learner and holder') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Invoice shares by learner and holder') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Charge') }}</th><th scope="col">{{ __('Due on') }}</th><th scope="col">{{ __('Original share') }}</th><th scope="col">{{ __('Credited') }}</th><th scope="col">{{ __('Net charge') }}</th><th scope="col">{{ __('Applied payments') }}</th><th scope="col">{{ __('Still due') }}</th></tr></thead>
        <tbody>@foreach($charges as $row)<tr><td>{{ $row['learner_name'] }}</td><td>{{ $row['holder_name'] }}</td><td>{{ $row['invoice_number'] }}</td><td>{{ $row['description'] }}@if($row['charge_scope']==='family')<p>{{ __('Family charge apportioned to this learner') }}</p>@endif</td><td>{{ $row['due_on'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['gross_charged_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['credited_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['charged_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['allocated_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['outstanding_cents'],$account->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>@include('money.billing.pagination',['paginator'=>$charges])@endif
@endif
@if($section==='credits')
    <h2 class="m3-title-large">{{ __('Posted credit notes') }}</h2>
    @if(auth()->user()->can('fee-credits-manage') && $canWrite)<x-m3.button variant="outlined" :href="route('fee-credit-notes.new',['account_id'=>$account->id])">{{ __('Prepare credit note') }}</x-m3.button>@endif
    @if($creditNotes->isEmpty())<p>{{ __('No posted credit notes') }}</p>@else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Posted credit notes') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Posted credit notes') }}</caption><thead><tr><th scope="col">{{ __('Credit note') }}</th><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Credit amount') }}</th><th scope="col">{{ __('Restored held credit') }}</th></tr></thead>
        <tbody>@foreach($creditNotes as $row)<tr><td>@if(auth()->user()->can('fee-credits-manage'))<a href="{{ route('fee-credit-notes.show',['note'=>$row['id']]) }}">{{ $row['number'] }}</a>@else{{ $row['number'] }}@endif</td><td>{{ $row['learner_name'] }}</td><td>{{ $holderNames[$row['holder_user_id']]??'' }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['released_cents'],$account->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>@include('money.billing.pagination',['paginator'=>$creditNotes])@endif
@endif
@if($section==='receipts')
    <h2 class="m3-title-large">{{ __('Receipt and credit history') }}</h2>
    @if($receipts->isEmpty())<p>{{ __('No ledger receipts yet') }}</p>@else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Receipt and credit history') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Receipt and credit history') }}</caption><thead><tr><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Received on') }}</th><th scope="col">{{ __('Amount received') }}</th><th scope="col">{{ __('Applied payments') }}</th><th scope="col">{{ __('Held credit') }}</th><th scope="col">{{ __('Reserved for refund') }}</th><th scope="col">{{ __('Refunded to holder') }}</th></tr></thead>
        <tbody>@foreach($receipts as $row)<tr><td><a href="{{ route('fee-payments.show',['payment'=>$row['id']]) }}">{{ $row['number'] }}</a></td><td>{{ $row['received_on'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['allocated_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['credit_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['pending_refund_cents'],$account->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['refunded_cents'],$account->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>@include('money.billing.pagination',['paginator'=>$receipts])@endif
@endif
</div>
@endsection
