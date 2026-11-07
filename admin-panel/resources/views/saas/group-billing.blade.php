@extends('saas.layout')

@section('title', __('Invoices: :group', ['group' => $group['name']]))

@use('App\Services\Platform\Saas\PlatformBilling')
@section('content')
@php
    $rands = fn (int $cents): string => PlatformBilling::rands($cents);
    $states = ['draft' => __('Draft'), 'issued' => __('Issued'), 'discarded' => __('Discarded')];
@endphp
<p><a href="{{ route('saas.groups.show', $group['id']) }}">{{ $group['name'] }}</a> · <a href="{{ route('saas.billing') }}">{{ __('Billing') }}</a></p>
<h1>{{ __('Invoices: :group', ['group' => $group['name']]) }}</h1>
<p class="saas-lead">{{ __('One invoice a month for the group, made from each school\'s own monthly draft. A school\'s month is billed on its own or through the group, never both.') }}</p>
<p>{{ $billed_through_group === [] ? __('No school is billed through the group yet. Change it on the group\'s page.') : __('Billed through the group: :schools', ['schools' => implode(', ', $billed_through_group)]) }}</p>

@if ($canManage)
<form method="POST" action="{{ route('saas.groups.invoices.store', $group['id']) }}" class="saas-card" aria-label="{{ __('Create the group\'s draft for a month') }}">
    @csrf
    <h2>{{ __('Create the group\'s draft for a month') }}</h2>
    <p>{{ __('Each school billed through the group gets its own monthly draft if it has none, counted and priced again today, and its lines are copied onto the group\'s draft. A school already invoiced on its own for the month, or not billed that month, is left off and listed.') }}</p>
    <div class="saas-grid"><div class="saas-field">
        <label for="group-period">{{ __('Month to bill') }}</label>
        <input id="group-period" name="period" type="month" required value="{{ old('period', $month) }}">
    </div></div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Create the group\'s draft') }}</button></div>
</form>
@endif

@if ($invoices === [])
    <p class="saas-card">{{ __('No group invoices yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Group invoices') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Month') }}</th><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('State') }}</th><th scope="col" class="num">{{ __('Schools') }}</th><th scope="col" class="num">{{ __('Total') }}</th><th scope="col" class="num">{{ __('Unpaid') }}</th></tr></thead>
        <tbody>
        @foreach ($invoices as $invoice)
            <tr data-group-invoice="{{ $invoice['id'] }}">
                <th scope="row"><a href="{{ route('saas.groups.invoices.show', [$group['id'], $invoice['id']]) }}">{{ $invoice['slot'] }}</a></th>
                <td>{{ $invoice['reference'] ?? '–' }}</td>
                <td>{{ $states[$invoice['status']] ?? $invoice['status'] }}@if ($invoice['problem'])<br><small>{{ $invoice['problem'] }}</small>@endif</td>
                <td class="num">{{ $invoice['schools'] }}</td>
                <td class="num">{{ $rands($invoice['total_cents']) }}</td>
                <td class="num">{{ $invoice['status'] === 'issued' ? $rands($invoice['outstanding_cents']) : '–' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
