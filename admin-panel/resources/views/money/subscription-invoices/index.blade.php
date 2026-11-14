@extends('layouts.master')
@section('title', __('School subscription invoices'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('School subscription invoices')" :subtitle="__('Issued Navigate Ed invoices and credit notes for this school, with payment allocations as recorded by billing.')" />
<p>{{ __('This view is read-only. Payments, credits and corrections are recorded by Navigate Ed billing.') }}</p>
<div class="page-table__scroll" role="region" tabindex="0" aria-label="{{ __('Issued subscription invoices') }}"><table class="page-table__table"><caption>{{ __('Issued subscription invoices') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Package and period') }}</th><th scope="col">{{ __('Due date') }}</th><th scope="col">{{ __('Total') }}</th><th scope="col">{{ __('Payment status') }}</th><th scope="col">{{ __('Outstanding') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td><a href="{{ route('school-subscription-invoices.show', ['invoice'=>$record['id']]) }}">{{ $record['reference'] }}</a><br>{{ __($record['kind']==='credit_note'?'Credit note':'Invoice') }}</td><td>{{ $record['package'] }}<br>{{ $record['period_start'] }} – {{ $record['period_end'] }}</td><td>{{ $record['due_on'] ?? '—' }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($record['total_cents'],$record['currency'])->format() }}</td><td>{{ __(['paid'=>'Paid','part_paid'=>'Part paid','unpaid'=>'Unpaid','credit_note'=>'Credit note'][$record['payment_status']]) }}</td><td>{{ $record['outstanding_cents']===null?'—':\App\Services\Platform\Ledger\Money::ofCents($record['outstanding_cents'],$record['currency'])->format() }}</td></tr>
@empty<tr><td colspan="6">{{ __('No subscription invoices have been issued for this school.') }}</td></tr>@endforelse
</tbody></table></div>
{{ $records->links() }}
</div>
@endsection
