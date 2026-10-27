@extends('layouts.master')
@section('title', __('Automatic item batches'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Automatic item batches')" />
<x-m3.button variant="text" :href="route('fee-items.index')">{{ __('Back to fee items') }}</x-m3.button>
<x-m3.button :href="route('fee-item-batches.new')">{{ __('Prepare automatic item batch') }}</x-m3.button>
@if($batches->isEmpty())<x-page.empty-state :title="__('No automatic item batches yet')" :message="__('Configure an item rule, then review eligible targets and exceptions before posting.')" />@else
<ul>@foreach($batches as $batch)<li><a href="{{ route('fee-item-batches.show',['batch'=>$batch->id]) }}">ITEM-BATCH-{{ $batch->id }}</a> · {{ $batch->snapshot['item_name'] }} · {{ \App\Services\Platform\Ledger\Money::ofCents($batch->amount_cents,$batch->currency)->format() }}</li>@endforeach</ul>
@include('money.billing.pagination',['paginator'=>$batches])
@endif
</div>
@endsection
