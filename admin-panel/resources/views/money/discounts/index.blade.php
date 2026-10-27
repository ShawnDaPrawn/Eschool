@extends('layouts.master')
@section('title', __('Discounts and bursaries'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Discounts and bursaries')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Discount history is read-only while fees are disabled.') }}</p>@endif
<p>{{ __('Approved reductions retain original invoices and issue linked credits. Private eligibility and evidence stay with Finance.') }}</p>
@if($canWrite)<div class="fee-plan-actions"><x-m3.button :href="route('fee-discounts.new')">{{ __('Create discount rule') }}</x-m3.button><x-m3.button variant="outlined" :href="route('fee-discounts.apply')">{{ __('Prepare fee reduction') }}</x-m3.button></div>@endif
<h2>{{ __('Discount rules') }}</h2>
<div class="page-table__scroll" role="region" aria-label="{{ __('Discount rules') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Discount rules') }}</caption><thead><tr><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Type') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Revision') }}</th></tr></thead><tbody>
@foreach($rules as $rule)<tr><td><a href="{{ route('fee-discounts.edit',['rule'=>$rule->id]) }}">{{ $rule->name }}</a></td><td>{{ $rule->kind }}</td><td>{{ $rule->state }}</td><td>{{ $rule->revision }}</td></tr>@endforeach
</tbody></table></div>{{ $rules->links() }}
<h2>{{ __('Reviewed reductions') }}</h2>
<div class="page-table__scroll" role="region" aria-label="{{ __('Reviewed reductions') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Reviewed reductions') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Reduction') }}</th></tr></thead><tbody>
@foreach($runs as $run)<tr><td><a href="{{ route('fee-discounts.run',['run'=>$run->id]) }}">FEE-RED-{{ $run->id }}</a></td><td>{{ $run->state }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($run->amount_cents,$run->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>
</div>
@endsection
