@extends('saas.layout')

@php
    $isCredit = $invoice['kind'] === 'credit_note';
    $kindLabel = $isCredit ? __('Credit note') : ($invoice['status'] === 'issued' ? __('Tax invoice') : __('Draft invoice'));
@endphp
@section('title', ($invoice['reference'] ?? $kindLabel).' · '.$school['name'])

@use('App\Services\Platform\Saas\PlatformBilling')
@section('content')
@php
    $canManage = app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'billing.manage');
    $rands = fn (int $cents): string => PlatformBilling::rands($cents);
    $seller = (array) config('platform_billing.seller', []);
    $vat = PlatformBilling::percentLabel($invoice['vat_rate_bp']);
@endphp
<p class="saas-print-hide"><a href="{{ route('saas.billing.statement', $school['id']) }}">{{ __('Statement: :school', ['school' => $school['name']]) }}</a> · <a href="{{ route('saas.billing', ['month' => substr($invoice['period_start'], 0, 7)]) }}">{{ __('Billing') }}</a></p>

<article class="saas-card saas-invoice" aria-label="{{ $kindLabel }}">
    <h1>{{ $kindLabel }} @if ($invoice['reference']){{ $invoice['reference'] }}@endif</h1>
    @if ($invoice['status'] === 'draft')<p><span class="saas-badge saas-badge--warn">{{ __('Draft: not issued, not sent, no reference yet') }}</span></p>@endif
    @if ($invoice['status'] === 'discarded')<p><span class="saas-badge saas-badge--warn">{{ __('Discarded: this school is not billed for this month') }}</span></p>@endif
    <div class="saas-grid">
        <div>
            <p><strong>{{ $seller['name'] ?? 'Navigate Ed' }}</strong></p>
            @if (($seller['address'] ?? '') !== '')<p>{{ $seller['address'] }}</p>@endif
            @if (($seller['vat_number'] ?? '') !== '')<p>{{ __('VAT number: :number', ['number' => $seller['vat_number']]) }}</p>@endif
            @if (($seller['email'] ?? '') !== '')<p>{{ $seller['email'] }}</p>@endif
        </div>
        <div>
            <p><strong>{{ $school['name'] }}</strong></p>
            @if ($school['address'])<p>{{ $school['address'] }}</p>@endif
        </div>
        <div>
            <p>{{ __('For :month (:from to :to)', ['month' => \Carbon\CarbonImmutable::parse($invoice['period_start'])->format('F Y'), 'from' => $invoice['period_start'], 'to' => $invoice['period_end']]) }}</p>
            @if ($invoice['issued_on'])<p>{{ __('Issued: :date', ['date' => $invoice['issued_on']]) }}</p>@endif
            @if ($invoice['due_on'])<p>{{ __('Due: :date', ['date' => $invoice['due_on']]) }}</p>@endif
            @if ($isCredit)<p>{{ __('Credits invoice :reference', ['reference' => $invoice['credits_reference']]) }}</p><p>{{ __('Reason: :reason', ['reason' => $invoice['reason']]) }}</p>@endif
            @if (! $isCredit)<p>{{ __(':count learners, counted on :date', ['count' => $invoice['learners'], 'date' => $invoice['learners_counted_on']]) }}@if ($invoice['contracted_learners'] !== null) · {{ __(':count contracted', ['count' => $invoice['contracted_learners']]) }}@endif</p>@endif
        </div>
    </div>

    <div class="saas-table-wrap" role="region" aria-label="{{ __('Lines') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Description') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Per learner') }}</th><th scope="col">{{ __('Excluding VAT') }}</th><th scope="col">{{ __('VAT :rate', ['rate' => $vat]) }}</th></tr></thead>
        <tbody>
        @forelse ($lines as $line)
            <tr>
                <th scope="row">{{ $line['description'] }}</th>
                <td>{{ $line['learners'] ?? '' }}</td>
                <td>{{ $line['unit_cents'] === null ? '' : $rands($line['unit_cents']) }}</td>
                <td>{{ $rands($line['amount_cents']) }}</td>
                <td>{{ $rands($line['vat_cents']) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('No lines.') }}</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr><th scope="row" colspan="3">{{ __('Total excluding VAT') }}</th><td>{{ $rands($invoice['subtotal_cents']) }}</td><td></td></tr>
            <tr><th scope="row" colspan="3">{{ __('VAT at :rate', ['rate' => $vat]) }}</th><td></td><td>{{ $rands($invoice['vat_cents']) }}</td></tr>
            <tr><th scope="row" colspan="3">{{ $isCredit ? __('Total credit including VAT') : __('Total including VAT') }}</th><td colspan="2"><strong>{{ $rands($invoice['total_cents']) }}</strong></td></tr>
        </tfoot>
    </table>
    </div>
    <p>{{ __('Rounding: each line\'s VAT is rounded to the cent (half a cent up) on the line, and the totals add up the lines.') }}</p>
    @if (! $isCredit && $invoice['status'] === 'issued')
        <p>{{ __('Pay by EFT using the reference :reference.', ['reference' => $invoice['reference']]) }}@if (($seller['bank'] ?? '') !== '') {{ $seller['bank'] }}@endif</p>
        <p>{{ $invoice['outstanding_cents'] === 0 ? __('Paid in full.') : __('Unpaid: :amount', ['amount' => $rands($invoice['outstanding_cents'])]) }}</p>
    @endif
    @if ($invoice['credit_notes'] !== [])
        <p>{{ __('Credit notes:') }} @foreach ($invoice['credit_notes'] as $note)<a href="{{ route('saas.billing.invoices.show', [$school['id'], $note['id']]) }}">{{ $note['reference'] }}</a> ({{ $note['issued_on'] }}, {{ $rands($note['total_cents']) }})@if (! $loop->last), @endif @endforeach</p>
    @endif
</article>

@if ($canManage && $invoice['status'] === 'draft')
<div class="saas-card saas-print-hide">
    <h2>{{ __('Review this draft') }}</h2>
    @if ($problem)<p class="saas-error">{{ $problem }}</p>@endif
    <p>{{ __('Recompute counts the learners again today and takes the contract or list price again. Issuing gives it a reference and a due date; after that it never changes.') }}</p>
    <div class="saas-actions">
        <form method="POST" action="{{ route('saas.billing.invoices.recompute', [$school['id'], $invoice['id']]) }}">@csrf<button class="saas-btn saas-btn--quiet" type="submit">{{ __('Recompute') }}</button></form>
        @if (! $problem)<form method="POST" action="{{ route('saas.billing.invoices.issue', [$school['id'], $invoice['id']]) }}">@csrf<button class="saas-btn" type="submit">{{ __('Issue this invoice') }}</button></form>@endif
        <form method="POST" action="{{ route('saas.billing.invoices.discard', [$school['id'], $invoice['id']]) }}">@csrf<button class="saas-btn saas-btn--danger" type="submit">{{ __('Do not bill this month') }}</button></form>
    </div>
</div>
@endif

@if ($canManage && ! $isCredit && $invoice['status'] === 'issued' && $invoice['creditable_cents'] > 0)
<form method="POST" action="{{ route('saas.billing.invoices.credit', [$school['id'], $invoice['id']]) }}" class="saas-card saas-print-hide" aria-label="{{ __('Issue a credit note') }}">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) Illuminate\Support\Str::uuid()) }}">
    <h2>{{ __('Issue a credit note') }}</h2>
    <p>{{ __('An issued invoice never changes. To correct it, credit part or all of it: :amount (including VAT) is left to credit. The credit settles what is unpaid; anything more is credit on the school\'s account.', ['amount' => $rands($invoice['creditable_cents'])]) }}</p>
    <div class="saas-grid">
        @if ($invoice['credit_notes'] === [])
        <div class="saas-field">
            <label><input type="checkbox" name="full" value="1" @checked(old('full'))> {{ __('Credit the whole invoice') }}</label>
        </div>
        @endif
        <div class="saas-field">
            <label for="credit-amount">{{ __('Or an amount excluding VAT (rands)') }}</label>
            <input id="credit-amount" name="amount" inputmode="decimal" pattern="\d{1,8}(\.\d{1,2})?" value="{{ old('amount') }}">
        </div>
        <div class="saas-field">
            <label for="credit-reason">{{ __('Reason (printed on the credit note)') }}</label>
            <textarea id="credit-reason" name="reason" required minlength="10" maxlength="500" rows="2">{{ old('reason') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Issue the credit note') }}</button></div>
</form>
@endif
@endsection
