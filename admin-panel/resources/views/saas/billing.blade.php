@extends('saas.layout')

@section('title', __('Billing'))

@use('App\Services\Platform\Saas\PlatformBilling')
@section('content')
@php
    $access = app(\App\Services\Platform\Saas\PlatformStaffAccess::class);
    $canManage = $access->can(auth()->user(), 'billing.manage');
    $canSuspend = $access->can(auth()->user(), 'schools.status');
    $rands = fn (int $cents): string => PlatformBilling::rands($cents);
    $monthLabel = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $month)->format('F Y');
    $sources = ['contract' => __('Contract'), 'list' => __('List price')];
    $dueForSuspension = array_values(array_filter($overdue, fn (array $row): bool => $row['stage'] === 'due_for_suspension'));
@endphp
<h1>{{ __('Billing') }}</h1>
<p class="saas-lead">{{ __('Navigate Ed\'s monthly invoices to schools, EFT payments and overdue accounts. Prices are per learner per month excluding VAT; amounts are exact to the cent.') }}</p>

<form method="GET" action="{{ route('saas.billing') }}" class="saas-inline" aria-label="{{ __('Choose a month') }}">
    <label for="billing-month">{{ __('Month') }}</label>
    <input id="billing-month" name="month" type="month" value="{{ $month }}">
    <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Show') }}</button>
</form>

<h2>{{ __('Revenue for :month', ['month' => $monthLabel]) }}</h2>
<div class="saas-grid">
    <div class="saas-card"><p>{{ __('Invoiced (including VAT)') }}</p><p class="saas-figure">{{ $rands($summary['invoiced_cents']) }}</p><p>{{ trans_choice('{0} No school invoiced|{1} One school invoiced|[2,*] :count schools invoiced', $summary['schools_invoiced']) }}</p></div>
    <div class="saas-card"><p>{{ __('Collected (EFT received this month)') }}</p><p class="saas-figure">{{ $rands($summary['collected_cents']) }}</p></div>
    <div class="saas-card"><p>{{ __('Overdue now, all months') }}</p><p class="saas-figure">{{ $rands($summary['overdue_cents']) }}</p></div>
    <div class="saas-card"><p>{{ __('Monthly recurring revenue (excluding VAT, less credit notes)') }}</p><p class="saas-figure">{{ $rands($summary['mrr_cents']) }}</p>@if ($summary['credited_cents'] > 0)<p>{{ __('Credit notes issued this month: :amount', ['amount' => $rands($summary['credited_cents'])]) }}</p>@endif</div>
</div>

@if ($canManage)
<h2>{{ __('Run the month') }}</h2>
<form method="POST" action="{{ route('saas.billing.runs.store') }}" class="saas-card" aria-label="{{ __('Create drafts for a month') }}">
    @csrf
    <p>{{ __('Creates a draft invoice for every active school that has none for the month. Learners are counted today. Running it again creates nothing new. Drafts are not sent anywhere: review them, then issue them.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="run-period">{{ __('Month to bill') }}</label>
            <input id="run-period" name="period" type="month" required value="{{ old('period', $month) }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Create drafts') }}</button></div>
</form>
@endif

<h2>{{ __('Drafts for :month', ['month' => $monthLabel]) }}</h2>
@if ($drafts === [])
    <p class="saas-card">{{ __('No drafts for this month.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Drafts') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Price') }}</th><th scope="col">{{ __('Excluding VAT') }}</th><th scope="col">{{ __('VAT') }}</th><th scope="col">{{ __('Total') }}</th><th scope="col">{{ __('Ready?') }}</th></tr></thead>
        <tbody>
        @foreach ($drafts as $draft)
            <tr>
                <th scope="row"><a href="{{ route('saas.billing.invoices.show', [$draft['school_id'], $draft['id']]) }}">{{ $draft['school'] }}</a></th>
                <td>{{ $draft['learners'] }} <small>({{ __('counted :date', ['date' => $draft['learners_counted_on']]) }})</small></td>
                <td>{{ $sources[$draft['price_source']] ?? __('None') }}</td>
                <td>{{ $rands($draft['subtotal_cents']) }}</td>
                <td>{{ $rands($draft['vat_cents']) }}</td>
                <td>{{ $rands($draft['total_cents']) }}</td>
                <td>{{ $draft['problem'] ?? __('Ready to issue') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if ($canManage)
<form method="POST" action="{{ route('saas.billing.runs.issue') }}" class="saas-card" aria-label="{{ __('Issue the month\'s drafts') }}">
    @csrf
    <input type="hidden" name="period" value="{{ $month }}">
    <p>{{ __('Issuing gives each ready draft its reference, today\'s date and a due date :days days later. An issued invoice never changes; a correction is a credit note.', ['days' => (int) config('platform_billing.payment_terms_days', 30)]) }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Issue every ready draft') }}</button></div>
</form>
@endif
@endif

<h2>{{ __('Issued for :month', ['month' => $monthLabel]) }}</h2>
@if ($issued === [])
    <p class="saas-card">{{ __('Nothing issued for this month yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Issued invoices') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Issued') }}</th><th scope="col">{{ __('Due') }}</th><th scope="col">{{ __('Total') }}</th><th scope="col">{{ __('Unpaid') }}</th></tr></thead>
        <tbody>
        @foreach ($issued as $invoice)
            <tr>
                <td><a href="{{ route('saas.billing.invoices.show', [$invoice['school_id'], $invoice['id']]) }}">{{ $invoice['reference'] }}</a></td>
                <th scope="row"><a href="{{ route('saas.billing.statement', $invoice['school_id']) }}">{{ $invoice['school'] }}</a></th>
                <td>{{ $invoice['issued_on'] }}</td>
                <td>{{ $invoice['due_on'] }}</td>
                <td>{{ $rands($invoice['total_cents']) }}</td>
                <td>{{ $invoice['outstanding_cents'] === 0 ? __('Paid') : $rands($invoice['outstanding_cents']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@if ($discarded !== [])
<p>{{ __('Not billed this month (draft discarded):') }} {{ implode(', ', array_column($discarded, 'school')) }}</p>
@endif

<h2 id="overdue">{{ __('Overdue') }}</h2>
<p>{{ __('An invoice is overdue the day after its due date while anything on it is unpaid. After :days days of grace the school is listed as due for suspension. Suspending is always a deliberate decision, with a reason, through the school\'s status.', ['days' => $grace_days]) }}</p>
@if ($overdue === [])
    <p class="saas-card">{{ __('Nothing is overdue.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Overdue schools') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Overdue') }}</th><th scope="col">{{ __('Oldest due') }}</th><th scope="col">{{ __('Stage') }}</th><th scope="col">{{ __('Last reminder') }}</th>@if ($canManage)<th scope="col">{{ __('Remind') }}</th>@endif</tr></thead>
        <tbody>
        @foreach ($overdue as $row)
            <tr>
                <th scope="row"><a href="{{ route('saas.billing.statement', $row['school_id']) }}">{{ $row['name'] }}</a></th>
                <td>{{ $rands($row['overdue_cents']) }} <small>({{ trans_choice('{1} one invoice|[2,*] :count invoices', $row['invoices']) }})</small></td>
                <td>{{ $row['oldest_due'] }} · {{ trans_choice('{1} 1 day ago|[2,*] :count days ago', max(1, $row['days_overdue'])) }}</td>
                <td>
                    @if ($row['stage'] === 'due_for_suspension')<span class="saas-badge saas-badge--warn">{{ __('Due for suspension') }}</span>
                    @elseif ($row['stage'] === 'grace'){{ __('In grace until :date', ['date' => $row['grace_ends']]) }}
                    @else{{ __(ucfirst($row['status'])) }}@endif
                </td>
                <td>{{ $row['reminded_at'] ?? __('Never') }}</td>
                @if ($canManage)
                <td>
                    <form method="POST" action="{{ route('saas.billing.reminders.store', $row['school_id']) }}">
                        @csrf
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Send a reminder') }}</button>
                    </form>
                </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="suspension">{{ __('Due for suspension') }}</h2>
@if ($dueForSuspension === [])
    <p class="saas-card">{{ __('No school is past its grace period.') }}</p>
@else
    @foreach ($dueForSuspension as $row)
        <div class="saas-card">
            <h3>{{ $row['name'] }}</h3>
            <p>{{ __(':amount overdue since :date (:days days); grace ended :ends.', ['amount' => $rands($row['overdue_cents']), 'date' => $row['oldest_due'], 'days' => $row['days_overdue'], 'ends' => $row['grace_ends']]) }}</p>
            @if ($canSuspend)
            <form method="POST" action="{{ route('saas.schools.status', $row['school_id']) }}" aria-label="{{ __('Suspend :school', ['school' => $row['name']]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="suspended">
                <div class="saas-field">
                    <label for="suspend-reason-{{ $row['school_id'] }}">{{ __('Reason (kept in the audit log)') }}</label>
                    <textarea id="suspend-reason-{{ $row['school_id'] }}" name="reason" required minlength="10" maxlength="500" rows="2"></textarea>
                </div>
                <p>{{ __('Suspended schools\' people cannot sign in. Nothing is deleted, and the school can be restored from its page.') }}</p>
                <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Suspend this school') }}</button></div>
            </form>
            @else
            <p>{{ __('Ask an Owner or Operations to decide whether to suspend this school.') }}</p>
            @endif
        </div>
    @endforeach
@endif

<h2>{{ __('Schools and statements') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Schools') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Balance') }}</th></tr></thead>
        <tbody>
        @foreach ($schools as $school)
            <tr>
                <th scope="row"><a href="{{ route('saas.billing.statement', $school['id']) }}">{{ $school['name'] }}</a></th>
                <td>{{ __(ucfirst($school['status'])) }}</td>
                <td>{{ $school['balance_cents'] < 0 ? __(':amount in credit', ['amount' => $rands(-$school['balance_cents'])]) : $rands($school['balance_cents']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
