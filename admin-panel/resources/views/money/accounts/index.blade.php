@extends('layouts.master')
@section('title', __('Family accounts'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Family accounts')" :primary-label="$canWrite?__('New family account'):null" :primary-href="$canWrite?route('fee-accounts.new'):null" />
    @if(!$canWrite)<p>{{ __('Billing history is read-only while fees are disabled.') }}</p>@endif
    <form method="get" class="m3-row m3-gap-3">
        <x-m3.text-field name="search" type="search" :label="__('Find a family account')" :value="request('search')" maxlength="120" />
        <x-m3.button type="submit" variant="outlined">{{ __('Search') }}</x-m3.button>
    </form>
    @if($accounts->isEmpty())
        <x-page.empty-state :title="__('No family accounts found')" :message="__('Create an account and explicitly choose its learners, guardians and shares.')" :action-label="$canWrite?__('New family account'):null" :action-href="$canWrite?route('fee-accounts.new'):null" />
    @else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Family accounts') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Family accounts') }}</caption><thead><tr><th scope="col">{{ __('Account name') }}</th><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Currency code') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead>
        <tbody>@foreach($accounts as $account)<tr><td><a href="{{ route('fee-accounts.show',['account'=>$account->id]) }}">{{ $account->name }}</a></td><td>{{ $account->reference }}</td><td>{{ $account->currency }}</td><td>{{ $account->active?__('Active'):__('Archived') }}</td></tr>@endforeach</tbody>
    </table></div></div>
    @include('money.billing.pagination',['paginator'=>$accounts])
    @endif
</div>
@endsection
