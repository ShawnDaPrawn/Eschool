@extends('layouts.master')
@section('title', __('Bill runs'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Bill runs')" :primary-label="$canWrite?__('New bill preview'):null" :primary-href="$canWrite?route('fee-bill-runs.new'):null" />
    @if(!$canWrite)<p>{{ __('Billing history is read-only while fees are disabled.') }}</p>@endif
    <p>{{ __('Preview every payer and amount, approve the unchanged preview, then post invoices once.') }}</p>
    @if($runs->isEmpty())<x-page.empty-state :title="__('No bill runs yet')" :message="__('Set up annual fee plans and family accounts, then prepare a preview.')" :action-label="$canWrite?__('New bill preview'):null" :action-href="$canWrite?route('fee-bill-runs.new'):null" />
    @else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Bill runs') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Bill runs') }}</caption><thead><tr><th scope="col">{{ __('Bill run') }}</th><th scope="col">{{ __('Preview date') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Billable learners') }}</th><th scope="col">{{ __('Exceptions') }}</th></tr></thead>
        <tbody>@foreach($runs as $run)<tr><td><a href="{{ route('fee-bill-runs.show',['run'=>$run->id]) }}">{{ __('Bill run') }} {{ $run->id }}</a></td><td>{{ $run->as_of->toDateString() }}</td><td>{{ __($run->state) }}</td><td>{{ $run->snapshot['billable'] }}</td><td>{{ array_sum($run->snapshot['exceptions']) }}</td></tr>@endforeach</tbody>
    </table></div></div>
    @include('money.billing.pagination',['paginator'=>$runs])
    @endif
</div>
@endsection
