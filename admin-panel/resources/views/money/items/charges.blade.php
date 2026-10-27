@extends('layouts.master')
@section('title', __('Fee items and charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">

<x-page.header :title="__('Posted item charges')" />
<x-m3.button variant="text" :href="route('fee-items.index')">{{ __('Back to fee items') }}</x-m3.button>
@if($canWrite)<x-m3.button :href="route('fee-item-charges.new')">{{ __('Prepare item charge') }}</x-m3.button>@else<p>{{ __('Fee item history is read-only while fees are disabled.') }}</p>@endif
@if($charges->isEmpty())<x-page.empty-state :title="__('No item charges yet')" :message="__('Review an item and payer before confirming its invoice.')" />@else
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Posted item charges') }}" tabindex="0"><table class="page-table__table">
<caption>{{ __('Posted item charges') }}</caption><thead><tr><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Item') }}</th><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead>
<tbody>@foreach($charges as $charge)<tr><td><a href="{{ route('fee-item-charges.show',['charge'=>$charge->id]) }}">ITEM-{{ $charge->id }}</a></td><td>{{ $charge->snapshot['item_name'] }}</td><td>{{ $charge->snapshot['account_name'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($charge->amount_cents,$charge->currency)->format() }}</td></tr>@endforeach</tbody>
</table></div></div>@include('money.billing.pagination',['paginator'=>$charges])@endif

</div>
@endsection
