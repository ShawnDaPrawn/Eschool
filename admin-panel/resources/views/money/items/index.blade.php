@extends('layouts.master')
@section('title', __('Fee items and charges'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">

<x-page.header :title="__('Fee items')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ __('Fee items are additional charges. Each occurrence is reviewed before it creates an invoice.') }}</p>
<x-m3.button variant="text" :href="route('fee-items.tax')">{{ __('School VAT details') }}</x-m3.button>
<x-m3.button variant="outlined" :href="route('fee-item-charges.index')">{{ __('Posted item charges') }}</x-m3.button>
@if($canWrite)<x-m3.button :href="route('fee-items.new')">{{ __('New fee item') }}</x-m3.button>@else<p>{{ __('Fee item history is read-only while fees are disabled.') }}</p>@endif
@if($items->isEmpty())<x-page.empty-state :title="__('No fee items yet')" :message="__('Create an item for a learner or a family, then review its first charge.')" />@else
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Fee items') }}" tabindex="0"><table class="page-table__table">
<caption>{{ __('Fee items') }}</caption><thead><tr><th scope="col">{{ __('Item') }}</th><th scope="col">{{ __('Charged once per') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead>
<tbody>@foreach($items as $item)<tr><td><a href="{{ route('fee-items.edit',['item'=>$item->id]) }}">{{ $item->name }}</a></td><td>{{ $item->scope==='family'?__('Family'):__('Learner') }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($item->amount_cents,$item->currency)->format() }}</td><td>{{ __($item->state) }}</td><td>@if($canWrite && $item->state==='active')<a href="{{ route('fee-item-charges.new',['fee_item_id'=>$item->id]) }}">{{ __('Prepare item charge') }}</a>@endif</td></tr>@endforeach</tbody>
</table></div></div>@include('money.billing.pagination',['paginator'=>$items])@endif

</div>
@endsection
