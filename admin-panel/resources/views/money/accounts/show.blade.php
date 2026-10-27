@extends('layouts.master')
@section('title', __('Family accounts'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="$account->name" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <p>{{ __('Reference') }}: {{ $account->reference }} · {{ $account->active?__('Active'):__('Archived') }}</p>
    <p>{{ __('Current receivable balance') }}: <strong>{{ $balance->format() }}</strong></p>
    <x-m3.button variant="text" :href="route('fee-accounts.index')">{{ __('Back to family accounts') }}</x-m3.button>
    @if($canWrite)<x-m3.button variant="outlined" :href="route('fee-accounts.edit',['account'=>$account->id])">{{ __('Review learners and holder shares') }}</x-m3.button>@endif
    @if(!$canWrite)<p>{{ __('Billing history is read-only while fees are disabled.') }}</p>@endif
    @can('fee-payments-manage')
    <x-m3.button variant="outlined" :href="route('fee-payments.statement',['account'=>$account->id])">{{ __('Account statement and receipts') }}</x-m3.button>
    @endcan
    <h2 class="m3-title-large">{{ __('Posted invoices') }}</h2>
    @if($invoices->isEmpty())<x-page.empty-state :title="__('No posted invoices yet')" :message="__('Prepare, review and approve a bill run before posting charges.')" />
    @else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Posted invoices') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Posted invoices') }}</caption><thead><tr><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Issued on') }}</th><th scope="col">{{ __('Due on') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead>
        <tbody>@foreach($invoices as $invoice)<tr><td>@can('fee-billing-manage')<a href="{{ route('fee-invoices.show',['invoice'=>$invoice->id]) }}">{{ $invoice->number }}</a>@else{{ $invoice->number }}@endcan</td><td>{{ $invoice->issued_on->toDateString() }}</td><td>{{ $invoice->due_on->toDateString() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($invoice->total_cents,$invoice->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>
    @include('money.billing.pagination',['paginator'=>$invoices])
    @endif
    @if($canWrite && $account->active)<form method="post" action="{{ route('fee-accounts.archive',['account'=>$account->id]) }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$account->revision])
        <p>{{ __('Archiving stops new charges through this account. Financial history and existing debt remain.') }}</p>
        <x-m3.button type="submit" variant="outlined">{{ __('Archive family account') }}</x-m3.button>
    </form>@endif
</div>
@endsection
