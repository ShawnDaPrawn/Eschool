@extends('layouts.master')
@section('title', __('Bill runs'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Bill run').' '.$run->id" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <p>{{ __('Status') }}: <strong>{{ __($run->state) }}</strong> · {{ __('Revision') }} {{ $run->revision }}</p>
    <p>{{ __('Preview date') }}: {{ $snapshot['as_of'] }} · {{ __('Invoice due date') }}: {{ $snapshot['due_on'] }}</p>
    <p>{{ __('Billable learners') }}: {{ $snapshot['billable'] }} · {{ __('Excluded learners') }}: {{ $snapshot['skipped'] }}</p>
    @foreach($snapshot['totals'] as $currency=>$total)<p><strong>{{ \App\Services\Platform\Ledger\Money::ofCents($total,$currency)->format() }}</strong></p>@endforeach
    <x-m3.button variant="text" :href="route('fee-bill-runs.index')">{{ __('Back to bill runs') }}</x-m3.button>
    @if(!$canWrite)<p>{{ __('Billing history is read-only while fees are disabled.') }}</p>@endif
    @if($snapshot['exceptions'])<section aria-label="{{ __('Bill-run exceptions') }}">
        <h2 class="m3-title-large">{{ __('Resolve these exceptions') }}</h2><ul>@foreach($snapshot['exceptions'] as $issue=>$count)<li>{{ __($issue) }}: {{ $count }}</li>@endforeach</ul>
        <p>{{ __('Correct the affected inputs and prepare a new preview. This snapshot is retained for review.') }}</p>
    </section>@endif
    @if($canWrite && $run->state==='preview' && $snapshot['exceptions']===[])
    <form method="post" action="{{ route('fee-bill-runs.approve',['run'=>$run->id]) }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$run->revision])
        <p>{{ __('Approval rechecks the exact preview. No invoices are posted at this step.') }}</p>
        <x-m3.button type="submit">{{ __('Approve bill preview') }}</x-m3.button>
    </form>@endif
    @if($canWrite && $run->state==='approved')
    <form method="post" action="{{ route('fee-bill-runs.post',['run'=>$run->id]) }}">
        @include('money.fee-plans.hidden',['revision'=>$run->revision])
        <p>{{ __('Posted invoices are permanent. Review every amount and payer before posting. This does not collect payment.') }}</p>
        <x-m3.checkbox name="confirmed" :label="__('I confirm these approved charges should be invoiced.')" required />
        <x-m3.button type="submit">{{ __('Post approved invoices') }}</x-m3.button>
    </form>@endif
    <form method="get" class="m3-row m3-gap-3">
        <x-m3.text-field name="search" type="search" :label="__('Find a learner')" :value="request('search')" maxlength="120" />
        <x-m3.checkbox name="exceptions_only" :label="__('Show exceptions only')" :checked="request()->boolean('exceptions_only')" />
        <x-m3.button type="submit" variant="outlined">{{ __('Filter learners') }}</x-m3.button>
    </form>
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Bill preview learners') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Bill preview learners') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('Annual amount') }}</th><th scope="col">{{ __('Result') }}</th></tr></thead>
        <tbody>@forelse($rows as $row)<tr><td>{{ $row['name'] }}</td><td>{{ $row['account_name']??__('No payer') }}</td><td>{{ $row['amount_cents']===null?'—':\App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$row['currency'])->format() }}</td><td>
            @if($row['issues'])<ul>@foreach($row['issues'] as $issue)<li>{{ __($issue) }}</li>@endforeach</ul>
            @elseif($row['state']==='already_billed'){{ __('Already invoiced') }}
            @elseif($row['state']==='no_charge'){{ __('No charge') }}
            @else{{ __('Ready to invoice') }}@endif
        </td></tr>@empty<tr><td colspan="4">{{ __('No learners match this filter.') }}</td></tr>@endforelse</tbody>
    </table></div></div>
    @include('money.billing.pagination',['paginator'=>$rows])
    @if($run->state==='posted')
        <h2 class="m3-title-large">{{ __('Posted invoices') }}</h2>
        <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Posted invoices') }}" tabindex="0"><table class="page-table__table">
            <caption>{{ __('Posted invoices') }}</caption><thead><tr><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead>
            <tbody>@foreach($invoices as $invoice)<tr><td><a href="{{ route('fee-invoices.show',['invoice'=>$invoice->id]) }}">{{ $invoice->number }}</a></td><td>{{ $invoice->account_name }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($invoice->total_cents,$invoice->currency)->format() }}</td></tr>@endforeach</tbody>
        </table></div></div>
        @include('money.billing.pagination',['paginator'=>$invoices])
    @endif
</div>
@endsection
