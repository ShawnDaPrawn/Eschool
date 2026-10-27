@extends('layouts.master')
@section('title', __('Fee payments'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Review credit allocation')" />
    <x-m3.button variant="text" :href="route('fee-payments.show',['payment'=>$payment->id])">{{ __('Back to receipt') }}</x-m3.button>
    <p>{{ __('Available held credit') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($preview['available_cents'],$payment->currency)->format() }}</p>
    <p>{{ __('Amount to allocate') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($preview['amount_cents'],$payment->currency)->format() }}</p>
    <p>{{ __('Credit remaining after allocation') }}: {{ \App\Services\Platform\Ledger\Money::ofCents($preview['credit_left_cents'],$payment->currency)->format() }}</p>
    @if($preview['policy_revision']===0)<p>{{ __('Choose the school allocation rule before allocating this credit.') }}</p><x-m3.button variant="outlined" :href="route('fee-payments.index')">{{ __('School allocation rule') }}</x-m3.button>
    @elseif($preview['rows']===[])<p>{{ __('There are no eligible unpaid charges for this held credit.') }}</p>
    @else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Proposed allocation') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Proposed allocation') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Due on') }}</th><th scope="col">{{ __('Holder share still due') }}</th><th scope="col">{{ __('Amount to allocate') }}</th></tr></thead>
        <tbody>@foreach($preview['rows'] as $row)<tr><td>{{ $row['learner_name'] }}</td><td>{{ $row['invoice_number'] }}</td><td>{{ $row['due_on'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['outstanding_cents'],$payment->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$payment->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>
    <form method="post" action="{{ route('fee-payments.allocate',['payment'=>$payment->id]) }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
        <fieldset @disabled(!$canWrite)><legend>{{ __('Confirm reviewed allocation') }}</legend>
            <x-m3.checkbox name="confirmed" value="1" :label="__('I confirm this held credit should pay the invoice shares shown above.')" required />
            <x-m3.button type="submit">{{ __('Post reviewed allocation') }}</x-m3.button>
        </fieldset>
    </form>
    @endif
</div>
@endsection
