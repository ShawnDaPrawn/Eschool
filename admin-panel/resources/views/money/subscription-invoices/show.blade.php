@extends('layouts.master')
@section('title', $record['reference'])
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="$record['reference']" :subtitle="__('Issued subscription record for :school',['school'=>$record['school_name']])" />
@php $money=fn(int $cents)=>\App\Services\Platform\Ledger\Money::ofCents($cents,$record['currency'])->format(); @endphp
<x-m3.button variant="text" :href="route('school-subscription-invoices.index')">{{ __('Back to subscription invoices') }}</x-m3.button>
<div class="workspace-layout"><div class="workspace-main"><section class="workspace-section">
<h2>{{ __('Invoice lines') }}</h2>
<div class="page-table__scroll" role="region" tabindex="0" aria-label="{{ __('Issued invoice lines') }}"><table class="page-table__table"><caption>{{ __('Issued invoice lines') }}</caption><thead><tr><th scope="col">{{ __('Description') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Unit amount') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('VAT') }}</th></tr></thead><tbody>
@foreach($record['lines'] as $line)<tr><td>{{ $line['description'] }}</td><td>{{ $line['learners'] ?? '—' }}</td><td>{{ $line['unit_cents']===null?'—':$money($line['unit_cents']) }}</td><td>{{ $money($line['amount_cents']) }}</td><td>{{ $money($line['vat_cents']) }}</td></tr>@endforeach
</tbody></table></div><dl><dt>{{ __('Subtotal') }}</dt><dd>{{ $money($record['subtotal_cents']) }}</dd><dt>{{ __('VAT') }}</dt><dd>{{ $money($record['vat_cents']) }}</dd><dt>{{ __('Total') }}</dt><dd>{{ $money($record['total_cents']) }}</dd></dl>
</section><section class="workspace-section"><h2>{{ __('Payment and credit allocations') }}</h2>
<p>{{ __('Amounts show the portion applied to this invoice. Reversed allocations remain visible and do not reduce the outstanding balance.') }}</p>
<div class="page-table__scroll" role="region" tabindex="0" aria-label="{{ __('Recorded allocations') }}"><table class="page-table__table"><caption>{{ __('Recorded allocations') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Source') }}</th><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Applied amount') }}</th><th scope="col">{{ __('State') }}</th></tr></thead><tbody>
@forelse($record['allocations'] as $allocation)<tr><td>{{ $allocation['reference'] }}</td><td>{{ __($allocation['kind']==='payment'?'Payment':'Credit note') }}</td><td>{{ $allocation['date'] }}</td><td>{{ $money($allocation['amount_cents']) }}</td><td>{{ __($allocation['reversed']?'Reversed':'Applied') }}</td></tr>@empty<tr><td colspan="5">{{ __('No payments or credits have been allocated to this invoice.') }}</td></tr>@endforelse
</tbody></table></div></section>
@if($record['credit_notes'])<section class="workspace-section"><h2>{{ __('Issued credit notes') }}</h2><ul>@foreach($record['credit_notes'] as $note)<li><a href="{{ route('school-subscription-invoices.show',['invoice'=>$note['id']]) }}">{{ $note['reference'] }}</a> · {{ $money($note['total_cents']) }}</li>@endforeach</ul></section>@endif
</div><aside class="workspace-context"><h2>{{ __('Invoice context') }}</h2><dl><dt>{{ __('School') }}</dt><dd>{{ $record['school_name'] }}</dd><dt>{{ __('School access state') }}</dt><dd>{{ __($record['school_status']) }}</dd><dt>{{ __('Package') }}</dt><dd>{{ $record['package'] }}</dd><dt>{{ __('Billing period') }}</dt><dd>{{ $record['period_start'] }} – {{ $record['period_end'] }}</dd><dt>{{ __('Issued on') }}</dt><dd>{{ $record['issued_on'] }}</dd><dt>{{ __('Due date') }}</dt><dd>{{ $record['due_on'] ?? '—' }}</dd><dt>{{ __('Payment status') }}</dt><dd>{{ __(['paid'=>'Paid','part_paid'=>'Part paid','unpaid'=>'Unpaid','credit_note'=>'Credit note'][$record['payment_status']]) }}</dd><dt>{{ __('Outstanding') }}</dt><dd>{{ $record['outstanding_cents']===null?'—':$money($record['outstanding_cents']) }}</dd></dl><p>{{ __('Issued records are retained. Contact Navigate Ed billing for payment allocation or a correction.') }}</p></aside></div>
</div>
@endsection
