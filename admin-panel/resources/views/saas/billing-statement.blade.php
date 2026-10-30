@extends('saas.layout')

@section('title', $school['name'].' · '.__('Statement'))

@use('App\Services\Platform\Saas\PlatformBilling')
@section('content')
@php
    $canManage = app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'billing.manage');
    $rands = fn (int $cents): string => PlatformBilling::rands($cents);
    $kinds = ['invoice' => __('Invoice'), 'credit_note' => __('Credit note'), 'payment' => __('Payment'), 'reversal' => __('Reversal')];
    $today = PlatformBilling::today();
@endphp
<p class="saas-print-hide"><a href="{{ route('saas.billing') }}">{{ __('Billing') }}</a></p>
<h1>{{ __('Statement: :school', ['school' => $school['name']]) }}</h1>
<p class="saas-lead">
    @if ($school['status'] !== 'active')<span class="saas-badge saas-badge--warn">{{ __(ucfirst($school['status'])) }}</span> · @endif
    {{ __('Balance: :amount', ['amount' => $balance_cents < 0 ? __(':amount in credit', ['amount' => $rands(-$balance_cents)]) : $rands($balance_cents)]) }}
    · {{ __('Account credit: :amount', ['amount' => $rands($credit_cents)]) }}
    · {{ __('Printed :date', ['date' => $today]) }}
</p>
<p class="saas-print-hide">{{ __('To print or save it as a PDF, use the browser\'s print command: forms and menus are left off the page.') }}</p>

<h2>{{ __('Statement') }}</h2>
@if ($entries === [])
    <p class="saas-card">{{ __('Nothing issued or paid yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Statement') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('What') }}</th><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Balance') }}</th></tr></thead>
        <tbody>
        @foreach ($entries as $entry)
            <tr>
                <td>{{ $entry['date'] }}</td>
                <td>{{ $kinds[$entry['kind']] }}: {{ $entry['description'] }}</td>
                <td>@if (in_array($entry['kind'], ['invoice', 'credit_note'], true))<a href="{{ route('saas.billing.invoices.show', [$school['id'], $entry['id']]) }}">{{ $entry['reference'] }}</a>@else{{ $entry['reference'] }}@endif</td>
                <td>{{ $rands($entry['amount_cents']) }}</td>
                <td>{{ $rands($entry['balance_cents']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2>{{ __('Unpaid invoices') }}</h2>
@if ($open === [])
    <p class="saas-card">{{ __('Every issued invoice is paid.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Unpaid invoices') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Month') }}</th><th scope="col">{{ __('Due') }}</th><th scope="col">{{ __('Total') }}</th><th scope="col">{{ __('Unpaid') }}</th>@if ($canManage && $credit_cents > 0)<th scope="col" class="saas-print-hide">{{ __('Account credit') }}</th>@endif</tr></thead>
        <tbody>
        @foreach ($open as $invoice)
            <tr>
                <td><a href="{{ route('saas.billing.invoices.show', [$school['id'], $invoice['id']]) }}">{{ $invoice['reference'] }}</a></td>
                <td>{{ \Carbon\CarbonImmutable::parse($invoice['period_start'])->format('F Y') }}</td>
                <td>{{ $invoice['due_on'] }} @if ($invoice['overdue'])<span class="saas-badge saas-badge--warn">{{ __('Overdue') }}</span>@endif</td>
                <td>{{ $rands($invoice['total_cents']) }}</td>
                <td>{{ $rands($invoice['outstanding_cents']) }}</td>
                @if ($canManage && $credit_cents > 0)
                <td class="saas-print-hide">
                    <form method="POST" action="{{ route('saas.billing.invoices.apply-credit', [$school['id'], $invoice['id']]) }}">
                        @csrf
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Settle from account credit') }}</button>
                    </form>
                </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if ($canManage && collect($open)->contains('overdue', true))
<form method="POST" action="{{ route('saas.billing.reminders.store', $school['id']) }}" class="saas-card saas-print-hide" aria-label="{{ __('Send a reminder') }}">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ (string) Illuminate\Support\Str::uuid() }}">
    <p>{{ __('Sends a notice to the school\'s Super Admins in NavigateEd and an email to the bursar contact on HQ\'s school record.') }}</p>
    @if ($contacts === [])<p>{{ __('No bursar contact is recorded, so only the Super Admins are told.') }}</p>@endif
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Send a reminder') }}</button></div>
</form>
@endif
@endif

@if ($canManage)
<h2 class="saas-print-hide">{{ __('Record an EFT payment') }}</h2>
<form method="POST" action="{{ route('saas.billing.payments.store', $school['id']) }}" class="saas-card saas-print-hide" aria-label="{{ __('Record an EFT payment') }}">
    @csrf
    <p>{{ __('Enter the payment exactly as the bank statement shows it. Say how much settles each invoice; anything left over becomes credit on the school\'s account.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="payment-amount">{{ __('Amount received (rands)') }}</label>
            <input id="payment-amount" name="amount" inputmode="decimal" required pattern="\d{1,8}(\.\d{1,2})?" value="{{ old('amount') }}">
        </div>
        <div class="saas-field">
            <label for="payment-date">{{ __('Date received') }}</label>
            <input id="payment-date" name="received_on" type="date" required max="{{ $today }}" value="{{ old('received_on', $today) }}">
        </div>
        <div class="saas-field">
            <label for="payment-reference">{{ __('Bank reference') }}</label>
            <input id="payment-reference" name="bank_reference" required maxlength="96" value="{{ old('bank_reference') }}">
        </div>
        <div class="saas-field">
            <label for="payment-note">{{ __('Note (optional)') }}</label>
            <input id="payment-note" name="note" maxlength="500" value="{{ old('note') }}">
        </div>
        @foreach ($open as $invoice)
        <div class="saas-field">
            <label for="allocation-{{ $invoice['id'] }}">{{ __('Settles :reference (:amount unpaid)', ['reference' => $invoice['reference'], 'amount' => $rands($invoice['outstanding_cents'])]) }}</label>
            <input id="allocation-{{ $invoice['id'] }}" name="allocations[{{ $invoice['id'] }}]" inputmode="decimal" pattern="\d{1,8}(\.\d{1,2})?" value="{{ old('allocations.'.$invoice['id']) }}">
        </div>
        @endforeach
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record the payment') }}</button></div>
</form>
@endif

<h2>{{ __('Payments') }}</h2>
@if ($payments === [])
    <p class="saas-card">{{ __('No payments recorded.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Payments') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Received') }}</th><th scope="col">{{ __('Bank reference') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('State') }}</th></tr></thead>
        <tbody>
        @foreach ($payments as $payment)
            <tr>
                <td>{{ $payment['received_on'] }}</td>
                <td>{{ $payment['bank_reference'] }}@if ($payment['note']) <small>({{ $payment['note'] }})</small>@endif</td>
                <td>{{ $rands($payment['amount_cents']) }}</td>
                <td>
                    @if ($payment['reversed'])
                        {{ __('Reversed: :reason', ['reason' => $payment['reversal_reason']]) }}
                    @elseif ($canManage)
                        <details class="saas-print-hide">
                            <summary>{{ __('Reverse this payment') }}</summary>
                            <form method="POST" action="{{ route('saas.billing.payments.reverse', [$school['id'], $payment['id']]) }}">
                                @csrf
                                <div class="saas-field">
                                    <label for="reverse-{{ $payment['id'] }}">{{ __('Why (kept in the audit log)') }}</label>
                                    <textarea id="reverse-{{ $payment['id'] }}" name="reason" required minlength="10" maxlength="500" rows="2"></textarea>
                                </div>
                                <button class="saas-btn saas-btn--danger" type="submit">{{ __('Reverse') }}</button>
                            </form>
                        </details>
                    @else
                        {{ __('Stands') }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($credit_sources !== [])
<h2>{{ __('Account credit') }}</h2>
<ul>
    @foreach ($credit_sources as $source)<li>{{ $source['label'] }} ({{ $source['date'] }}): {{ $rands($source['available_cents']) }}</li>@endforeach
</ul>
@endif

@if ($drafts !== [])
<h2 class="saas-print-hide">{{ __('Drafts (not on the statement)') }}</h2>
<ul class="saas-print-hide">
    @foreach ($drafts as $draft)<li><a href="{{ route('saas.billing.invoices.show', [$school['id'], $draft['id']]) }}">{{ \Carbon\CarbonImmutable::parse($draft['period_start'])->format('F Y') }}</a>: {{ $rands($draft['total_cents']) }}</li>@endforeach
</ul>
@endif
@endsection
