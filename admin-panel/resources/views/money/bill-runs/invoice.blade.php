@extends('layouts.master')
@section('title', __('Invoice'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="$invoice->number" />
    <p>{{ $invoice->account_name }} · <strong>{{ \App\Services\Platform\Ledger\Money::ofCents($invoice->total_cents,$invoice->currency)->format() }}</strong></p>
    <p>{{ __('Issued on') }}: {{ $invoice->issued_on->toDateString() }} · {{ __('Due on') }}: {{ $invoice->due_on->toDateString() }}</p>
    <p>{{ __('No VAT charged on this invoice.') }}</p>
    <x-m3.button variant="text" :href="$backUrl??route('fee-bill-runs.show',['run'=>$invoice->bill_run_id])">{{ $backLabel??__('Back to bill run') }}</x-m3.button>
    @if(auth()->user()->can('fee-credits-manage'))<x-m3.button variant="outlined" :href="route('fee-credit-notes.new',['account_id'=>$invoice->fee_account_id])">{{ __('Prepare credit note') }}</x-m3.button>@endif
    <p>{{ __('This invoice records the original charge. Credit notes and current balances are shown on the account statement.') }}</p>
    <h2 class="m3-title-large">{{ __('Recorded holder shares') }}</h2>
    <ul>@foreach($invoice->holders as $holder)<li>{{ $holder['name'] }}: {{ intdiv($holder['share_bp'],100) }}.{{ str_pad((string)($holder['share_bp']%100),2,'0',STR_PAD_LEFT) }}% · {{ \App\Services\Platform\Ledger\Money::ofCents($holder['total_cents'],$invoice->currency)->format() }}</li>@endforeach</ul>
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Invoice lines') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Invoice lines') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Charge') }}</th><th scope="col">{{ __('Period') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead>
        <tbody>@foreach($lines as $line)<tr><td>{{ $line->learner_name }}</td><td>{{ $line->description }} @if($line->installment_due_on)<span>{{ __('Due') }} {{ $line->installment_due_on }}</span>@endif</td><td>{{ $line->period_start->toDateString() }} — {{ $line->period_end->toDateString() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($line->amount_cents,$line->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>
    @include('money.billing.pagination',['paginator'=>$lines])
</div>
@endsection
