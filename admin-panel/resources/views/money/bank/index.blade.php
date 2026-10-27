@extends('layouts.master')
@section('title', __('Bank statement matching'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Bank statement matching')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ __('Import bank evidence, then review each fee receipt. Importing never posts money or settles invoices.') }}</p>
@if($canWrite)<x-m3.button :href="route('bank-statements.new')">{{ __('Import bank CSV') }}</x-m3.button>@else<p>{{ __('Bank history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-payments.index')">{{ __('Fee payments') }}</x-m3.button>
<form method="get"><x-m3.select name="status" :label="__('Bank queue')" :value="$status" :options="['unmatched'=>__('Unmatched'),'matched'=>__('Matched'),'excluded'=>__('Excluded'),'all'=>__('All bank rows')]" /><x-m3.button type="submit" variant="outlined">{{ __('Show bank rows') }}</x-m3.button></form>
@if($lines->isEmpty())<x-page.empty-state :title="__('No bank rows in this queue')" :message="__('Imported transactions await a reviewed fee match or a reasoned exclusion.')" />@else
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Bank transactions') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Bank transactions') }}</caption><thead><tr><th scope="col">{{ __('Received on') }}</th><th scope="col">{{ __('Bank transaction reference') }}</th><th scope="col">{{ __('Direction') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
@foreach($lines as $line)<tr><td>{{ $line->received_on->format('Y-m-d') }}</td><td>{{ $line->details['transaction_reference'] }}</td><td>{{ $line->direction==='credit'?__('Bank credit'):__('Bank debit') }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($line->amount_cents,$line->currency)->format() }}</td><td>{{ $line->resolution_kind==='matched'?__('Matched'):($line->resolution_kind==='excluded'?__('Excluded'):__('Unmatched')) }}</td><td><a href="{{ route('bank-statements.show',['line'=>$line->id]) }}">{{ __('Open bank transaction') }}</a></td></tr>@endforeach
</tbody></table></div></div>@include('money.billing.pagination',['paginator'=>$lines])@endif
@if($imports->isNotEmpty())<h2>{{ __('Recent bank imports') }}</h2><ul>@foreach($imports as $import)<li>{{ $import->created_at->format('Y-m-d H:i') }} UTC · {{ $import->row_count }} {{ __('bank rows reviewed') }}</li>@endforeach</ul>@endif

</div>
@endsection
