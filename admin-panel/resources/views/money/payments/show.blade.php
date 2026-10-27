@extends('layouts.master')
@section('title', __('Fee payments'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="'FEE-PAY-'.$payment->id" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <x-m3.button variant="text" :href="route('fee-payments.index')">{{ __('Back to fee payments') }}</x-m3.button>
    <x-m3.button variant="outlined" :href="route('fee-payments.statement',['account'=>$account->id])">{{ __('Account statement and receipts') }}</x-m3.button>
    <dl>
        <dt>{{ __('Family account') }}</dt><dd>{{ $account->name }}</dd>
        <dt>{{ __('Received on') }}</dt><dd>{{ $payment->received_on->toDateString() }}</dd>
        <dt>{{ __('Receipt method') }}</dt><dd>{{ $payment->method==='cash'?__('Cash'):__('EFT') }}</dd>
        <dt>{{ __('Unique receipt reference') }}</dt><dd>{{ $payment->source_reference }}</dd>
        <dt>{{ __('Amount received') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($payment->amount_cents,$payment->currency)->format() }}</dd>
        <dt>{{ __('Held credit') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($credit,$payment->currency)->format() }}</dd>
    </dl>
    <p>{{ $payment->pinned_learner_id===null?__('This receipt may pay eligible learners for its named holder.'):__('This receipt stays pinned to its recorded learner.') }}</p>
    @if($canWrite && $credit>0)<x-m3.button :href="route('fee-payments.preview',['payment'=>$payment->id])">{{ __('Review credit allocation') }}</x-m3.button>@endif
    @if(!$canWrite)<p>{{ __('Payment history is read-only while fees are disabled.') }}</p>@endif
    <h2 class="m3-title-large">{{ __('Posted allocations') }}</h2>
    @if($runs->isEmpty())<p>{{ __('No allocation has been posted for this receipt.') }}</p>@else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Posted allocations') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Posted allocations') }}</caption><thead><tr><th scope="col">{{ __('Allocation') }}</th><th scope="col">{{ __('Posted on') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead>
        <tbody>@foreach($runs as $run)<tr><td>{{ $run->id }}</td><td>{{ $run->sealed_at->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d H:i') }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($run->amount_cents,$payment->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>@include('money.billing.pagination',['paginator'=>$runs])@endif
</div>
@endsection
