@extends('layouts.master')
@section('title', __('Bank statement matching'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Bank statement matching')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('bank-statements.new')">{{ __('Choose another bank CSV') }}</x-m3.button>
<p>{{ __('Review the bank rows before retaining them. No receipt or ledger posting is created by this import.') }}</p>
<p>{{ __('Already imported rows retained without duplication') }}: {{ $preview['existing_count'] }}</p>
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Bank import preview') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Bank import preview') }}</caption><thead><tr><th scope="col">{{ __('Received on') }}</th><th scope="col">{{ __('Bank transaction reference') }}</th><th scope="col">{{ __('Family payment reference') }}</th><th scope="col">{{ __('Description') }}</th><th scope="col">{{ __('Direction') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead><tbody>
@foreach($preview['rows'] as $row)<tr><td>{{ $row['received_on'] }}</td><td>{{ $row['transaction_reference'] }}</td><td>{{ $row['family_reference'] }}</td><td>{{ $row['description'] }}</td><td>{{ $row['direction']==='credit'?__('Bank credit'):__('Bank debit') }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$row['currency'])->format() }}</td></tr>@endforeach
</tbody></table></div></div>
<form method="post" action="{{ route('bank-statements.import') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<input type="hidden" name="payload" value="{{ $preview['payload'] }}"><input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
<x-m3.checkbox name="confirmed" value="1" :label="__('I confirm these bank rows should be retained for reconciliation. Importing does not record received fees.')" required />
<x-m3.button type="submit">{{ __('Import reviewed bank rows once') }}</x-m3.button></form>

</div>
@endsection
