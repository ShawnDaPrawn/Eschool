@extends('saas.layout')

@php $label = $invoice['status'] === 'issued' ? __('Tax invoice') : ($invoice['status'] === 'discarded' ? __('Discarded group draft') : __('Draft group invoice')); @endphp
@section('title', ($invoice['reference'] ?? $label).' · '.$group['name'])

@use('App\Services\Platform\Saas\PlatformBilling')
@section('content')
@php
    $rands = fn (int $cents): string => PlatformBilling::rands($cents);
    $seller = (array) config('platform_billing.seller', []);
    $vat = PlatformBilling::percentLabel($invoice['vat_rate_bp']);
@endphp
<p class="saas-print-hide"><a href="{{ route('saas.groups.invoices', $group['id']) }}">{{ __('Invoices: :group', ['group' => $group['name']]) }}</a></p>

<article class="saas-card saas-invoice" aria-label="{{ $label }}">
    <h1>{{ $label }} @if ($invoice['reference']){{ $invoice['reference'] }}@endif</h1>
    @if ($invoice['status'] === 'draft')<p><span class="saas-badge saas-badge--warn">{{ __('Draft: not issued, not sent, no reference yet') }}</span></p>@endif
    <div class="saas-grid">
        <div><p><strong>{{ $seller['name'] ?? 'Navigate Ed' }}</strong></p>@if (($seller['vat_number'] ?? '') !== '')<p>{{ __('VAT number: :number', ['number' => $seller['vat_number']]) }}</p>@endif</div>
        <div><p><strong>{{ $group['name'] }}</strong></p><p>{{ trans_choice('{0} No school|{1} 1 school|[2,*] :count schools', $invoice['schools'], ['count' => $invoice['schools']]) }} · {{ __(':count learners', ['count' => $invoice['learners']]) }}</p></div>
        <div>
            <p>{{ __('For :month (:from to :to)', ['month' => \Carbon\CarbonImmutable::parse($invoice['period_start'])->format('F Y'), 'from' => $invoice['period_start'], 'to' => $invoice['period_end']]) }}</p>
            @if ($invoice['issued_on'])<p>{{ __('Issued: :date', ['date' => $invoice['issued_on']]) }}</p>@endif
            @if ($invoice['due_on'])<p>{{ __('Due: :date', ['date' => $invoice['due_on']]) }}</p>@endif
        </div>
    </div>

    @forelse ($schools as $school)
    <h2>{{ $school['school'] }}</h2>
    <div class="saas-table-wrap" role="region" aria-label="{{ __('Lines for :school', ['school' => $school['school']]) }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Description') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Per learner') }}</th><th scope="col">{{ __('Excluding VAT') }}</th><th scope="col">{{ __('VAT :rate', ['rate' => $vat]) }}</th></tr></thead>
        <tbody>
        @forelse ($school['lines'] as $line)
            <tr><th scope="row">{{ $line['description'] }}</th><td>{{ $line['learners'] ?? '' }}</td><td>{{ $line['unit_cents'] === null ? '' : $rands($line['unit_cents']) }}</td><td>{{ $rands($line['amount_cents']) }}</td><td>{{ $rands($line['vat_cents']) }}</td></tr>
        @empty
            <tr><td colspan="5">{{ __('No lines.') }}</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr><th scope="row" colspan="3">{{ __(':school, including VAT', ['school' => $school['school']]) }}</th><td colspan="2">{{ $rands($school['total_cents']) }}</td></tr></tfoot>
    </table>
    </div>
    @empty
    <p>{{ __('No school is on this invoice.') }}</p>
    @endforelse

    <table class="saas-table">
        <tbody>
            <tr><th scope="row">{{ __('Total excluding VAT') }}</th><td>{{ $rands($invoice['subtotal_cents']) }}</td></tr>
            <tr><th scope="row">{{ __('VAT at :rate', ['rate' => $vat]) }}</th><td>{{ $rands($invoice['vat_cents']) }}</td></tr>
            <tr><th scope="row">{{ __('Total including VAT') }}</th><td><strong>{{ $rands($invoice['total_cents']) }}</strong></td></tr>
        </tbody>
    </table>
    <p>{{ __('Rounding: each line\'s VAT is rounded to the cent (half a cent up) on the line, and the totals add up the lines.') }}</p>
    @if ($invoice['status'] === 'issued')
        <p>{{ __('Pay by EFT using the reference :reference.', ['reference' => $invoice['reference']]) }}@if (($seller['bank'] ?? '') !== '') {{ $seller['bank'] }}@endif</p>
        <p>{{ $invoice['outstanding_cents'] === 0 ? __('Paid in full.') : __('Unpaid: :amount', ['amount' => $rands($invoice['outstanding_cents'])]) }}</p>
    @endif
</article>

@if ($payments !== [])
<div class="saas-card saas-table-wrap saas-print-hide" role="region" aria-label="{{ __('Payments') }}" tabindex="0">
    <h2>{{ __('Payments') }}</h2>
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Received') }}</th><th scope="col">{{ __('Bank reference') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('State') }}</th></tr></thead>
        <tbody>
        @foreach ($payments as $payment)
            <tr data-group-payment="{{ $payment['id'] }}">
                <th scope="row">{{ $payment['received_on'] }}</th><td>{{ $payment['bank_reference'] }}</td><td>{{ $rands($payment['amount_cents']) }}</td>
                <td>
                    @if ($payment['reversed_at']){{ __('Reversed :date: :reason', ['date' => $payment['reversed_at'], 'reason' => $payment['reversal_reason']]) }}
                    @elseif ($canManage)
                    <form method="POST" action="{{ route('saas.groups.invoices.payments.reverse', [$group['id'], $invoice['id'], $payment['id']]) }}" aria-label="{{ __('Reverse this payment') }}">
                        @csrf
                        <div class="saas-field"><label for="reverse-{{ $payment['id'] }}">{{ __('Reason') }}</label><input id="reverse-{{ $payment['id'] }}" name="reason" required minlength="10" maxlength="500"></div>
                        <button class="saas-btn saas-btn--danger" type="submit">{{ __('Reverse') }}</button>
                    </form>
                    @else{{ __('Stands') }}@endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($canManage && $invoice['status'] === 'draft')
<div class="saas-card saas-print-hide">
    <h2>{{ __('Review this draft') }}</h2>
    @if ($problem)<p class="saas-error">{{ $problem }}</p>@endif
    <p>{{ __('Recompute counts and prices each school\'s own draft again and copies its lines again, with the schools billed through the group now. Issuing gives the group invoice a reference and a due date, and each school\'s month is then billed through it and never on its own.') }}</p>
    <div class="saas-actions">
        <form method="POST" action="{{ route('saas.groups.invoices.recompute', [$group['id'], $invoice['id']]) }}">@csrf<button class="saas-btn saas-btn--quiet" type="submit">{{ __('Recompute') }}</button></form>
        @if (! $problem)<form method="POST" action="{{ route('saas.groups.invoices.issue', [$group['id'], $invoice['id']]) }}">@csrf<button class="saas-btn" type="submit">{{ __('Issue the group invoice') }}</button></form>@endif
        <form method="POST" action="{{ route('saas.groups.invoices.discard', [$group['id'], $invoice['id']]) }}">@csrf<button class="saas-btn saas-btn--danger" type="submit">{{ __('Do not bill the group this month') }}</button></form>
    </div>
</div>
@endif

@if ($canManage && $invoice['status'] === 'issued' && $invoice['outstanding_cents'] > 0)
<form method="POST" action="{{ route('saas.groups.invoices.payments.store', [$group['id'], $invoice['id']]) }}" class="saas-card saas-print-hide" aria-label="{{ __('Record an EFT payment') }}">
    @csrf
    <h2>{{ __('Record an EFT payment') }}</h2>
    <div class="saas-grid">
        <div class="saas-field"><label for="pay-amount">{{ __('Amount (rands)') }}</label><input id="pay-amount" name="amount" inputmode="decimal" pattern="\d{1,9}(\.\d{1,2})?" required value="{{ old('amount') }}"></div>
        <div class="saas-field"><label for="pay-date">{{ __('Received on') }}</label><input id="pay-date" name="received_on" type="date" required value="{{ old('received_on', PlatformBilling::today()) }}"></div>
        <div class="saas-field"><label for="pay-reference">{{ __('Bank reference') }}</label><input id="pay-reference" name="bank_reference" required minlength="3" maxlength="96" value="{{ old('bank_reference') }}"></div>
        <div class="saas-field"><label for="pay-note">{{ __('Note (optional)') }}</label><input id="pay-note" name="note" maxlength="500" value="{{ old('note') }}"></div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record the payment') }}</button></div>
</form>
@endif
@endsection
