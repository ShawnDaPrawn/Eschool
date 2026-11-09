@extends('saas.layout')

@section('title', __('Learning plans'))

@use('App\Services\Platform\Subscriptions\SubscriptionPricing')
@section('content')
@php
    $rands = fn (int $cents): string => SubscriptionPricing::rands($cents);
    $monthLabel = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $month)->format('F Y');
    $plans = ['per_child' => __('Per child'), 'family' => __('Family (further child)'), 'school_licence' => __('School licence'), 'sponsored' => __('Sponsored places')];
    $statuses = ['requested' => __('Waiting for payment'), 'active' => __('Running'), 'lapsed' => __('Lapsed'), 'cancelled' => __('Cancelled'), 'ended' => __('Ended'), 'declined' => __('Declined')];
@endphp
<h1>{{ __('Learning plans') }}</h1>
<p class="saas-lead">{{ __('Subscriptions to the tutor and the parent insights, by reference: never a learner\'s or a guardian\'s name. Families and sponsors pay by EFT and quote the reference; record the EFT here to start or renew a plan. Daily practice stays free for every learner.') }}</p>
@unless ($pricing_confirmed)
    <p class="saas-card" role="note">{{ __('The prices are placeholders until the owner decides them (R 89 for the first child is the hypothesis).') }}</p>
@endunless
@if ($gateway === null)
    <p>{{ __('No payment gateway is connected: every plan is paid by EFT and recorded here.') }}</p>
@endif

<form method="GET" action="{{ route('saas.subscriptions') }}" class="saas-inline" aria-label="{{ __('Choose a month and filter') }}">
    <label for="subscriptions-month">{{ __('Month') }}</label>
    <input id="subscriptions-month" name="month" type="month" value="{{ $month }}">
    <label for="subscriptions-status">{{ __('Status') }}</label>
    <select id="subscriptions-status" name="status">
        <option value="">{{ __('Any') }}</option>
        @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? null) === $key)>{{ $label }}</option>@endforeach
    </select>
    <label for="subscriptions-plan">{{ __('Plan') }}</label>
    <select id="subscriptions-plan" name="plan">
        <option value="">{{ __('Any') }}</option>
        @foreach ($plans as $key => $label)<option value="{{ $key }}" @selected(($filters['plan'] ?? null) === $key)>{{ $label }}</option>@endforeach
    </select>
    <label for="subscriptions-school">{{ __('School') }}</label>
    <select id="subscriptions-school" name="school">
        <option value="">{{ __('Any') }}</option>
        @foreach ($schools as $school)<option value="{{ $school['id'] }}" @selected((int) ($filters['school'] ?? 0) === $school['id'])>{{ $school['name'] }}</option>@endforeach
    </select>
    <label for="subscriptions-reference">{{ __('Reference') }}</label>
    <input id="subscriptions-reference" name="reference" type="text" maxlength="24" value="{{ $filters['reference'] ?? '' }}">
    <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Show') }}</button>
</form>

<h2>{{ __('Revenue for :month', ['month' => $monthLabel]) }}</h2>
<div class="saas-grid">
    <div class="saas-card"><p>{{ __('Collected (payments received this month)') }}</p><p class="saas-figure">{{ $rands($revenue['collected_cents']) }}</p></div>
    <div class="saas-card"><p>{{ __('Monthly recurring revenue on :date', ['date' => $revenue['as_of']]) }}</p><p class="saas-figure">{{ $rands($revenue['mrr_cents']) }}</p><p>{{ __('Annual run rate: :amount', ['amount' => $rands($revenue['arr_cents'])]) }}</p></div>
    <div class="saas-card"><p>{{ __('Families paying') }}</p><p class="saas-figure">{{ $revenue['families'] }}</p><p>{{ __('Per family a month: :amount', ['amount' => $rands($revenue['per_family_cents'])]) }}</p></div>
    <div class="saas-card"><p>{{ __('Paying learners (family plans and sponsored places)') }}</p><p class="saas-figure">{{ $revenue['paying_learners'] }}</p><p>{{ __('Per learner a month: :amount', ['amount' => $rands($revenue['per_paying_learner_cents'])]) }}</p></div>
    <div class="saas-card"><p>{{ __('Churn this month') }}</p><p class="saas-figure">{{ intdiv($revenue['churn']['rate_bp'], 100) }}.{{ str_pad((string) ($revenue['churn']['rate_bp'] % 100), 2, '0', STR_PAD_LEFT) }} %</p><p>{{ __(':churned of :start running at the start of the month', ['churned' => $revenue['churn']['cancelled_or_ended'], 'start' => $revenue['churn']['running_at_start']]) }}</p></div>
</div>
<p><a href="{{ route('saas.subscriptions.revenue', ['month' => $month]) }}">{{ __('The same figures as JSON') }}</a></p>

@if ($revenue['schools'] !== [])
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Revenue per school') }}" tabindex="0">
    <table class="saas-table">
        <caption>{{ __('Revenue per school for :month', ['month' => $monthLabel]) }}</caption>
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Running plans') }}</th><th scope="col">{{ __('Monthly recurring revenue') }}</th><th scope="col">{{ __('Collected') }}</th></tr></thead>
        <tbody>
        @foreach ($revenue['schools'] as $row)
            <tr><th scope="row">{{ $row['school'] }}</th><td>{{ $row['running'] }}</td><td>{{ $rands($row['mrr_cents']) }}</td><td>{{ $rands($row['collected_cents']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2>{{ __('Subscriptions') }}</h2>
@if ($rows->isEmpty())
    <p class="saas-card">{{ __('No subscriptions match.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Subscriptions') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Plan') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Price a period') }}</th><th scope="col">{{ __('Paid until') }}</th>@if ($can_manage)<th scope="col">{{ __('Record an EFT') }}</th><th scope="col">{{ __('Decline or end') }}</th>@endif</tr></thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <th scope="row">{{ $row['reference'] }}</th>
                <td>{{ $row['school'] }}</td>
                <td>{{ $plans[$row['plan']] ?? $row['plan'] }}@if ($row['plan'] === 'sponsored')<br><small>{{ $row['sponsor_name'] }}: {{ __(':named of :places places named', ['named' => $row['named'], 'places' => $row['places']]) }}</small>@endif</td>
                <td>{{ $statuses[$row['effective_status']] ?? $row['effective_status'] }}</td>
                <td>{{ $rands($row['price_cents']) }}</td>
                <td>{{ $row['current_period_end'] ?? '—' }}</td>
                @if ($can_manage)
                <td>
                    @if (in_array($row['status'], ['requested', 'active'], true))
                    <form method="POST" action="{{ route('saas.subscriptions.payments.store', [$row['school_id'], $row['id']]) }}" aria-label="{{ __('Record an EFT for :reference', ['reference' => $row['reference']]) }}">
                        @csrf
                        <div class="saas-field"><label for="amount-{{ $row['id'] }}">{{ __('Amount (rands)') }}</label><input id="amount-{{ $row['id'] }}" name="amount" inputmode="decimal" required value="{{ number_format($row['price_cents'] / 100, 2, '.', '') }}"></div>
                        <div class="saas-field"><label for="received-{{ $row['id'] }}">{{ __('Received on') }}</label><input id="received-{{ $row['id'] }}" name="received_on" type="date" required max="{{ \App\Services\Platform\Saas\PlatformBilling::today() }}"></div>
                        <div class="saas-field"><label for="bank-{{ $row['id'] }}">{{ __('Bank reference') }}</label><input id="bank-{{ $row['id'] }}" name="bank_reference" required maxlength="96"></div>
                        <button class="saas-btn" type="submit">{{ __('Record') }}</button>
                    </form>
                    @endif
                </td>
                <td>
                    @if (in_array($row['status'], ['requested', 'active', 'cancelled'], true))
                    <form method="POST" action="{{ route($row['status'] === 'requested' ? 'saas.subscriptions.decline' : 'saas.subscriptions.end', [$row['school_id'], $row['id']]) }}" aria-label="{{ __('Decline or end :reference', ['reference' => $row['reference']]) }}">
                        @csrf
                        <div class="saas-field"><label for="reason-{{ $row['id'] }}">{{ __('Reason') }}</label><input id="reason-{{ $row['id'] }}" name="reason" required minlength="10" maxlength="500"></div>
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ $row['status'] === 'requested' ? __('Decline') : __('End now') }}</button>
                    </form>
                    @endif
                </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="saas-inline">
    @if ($rows->previousPageUrl())<a class="saas-btn saas-btn--quiet" href="{{ $rows->appends($filters)->previousPageUrl() }}" rel="prev">{{ __('Newer') }}</a>@endif
    @if ($rows->nextPageUrl())<a class="saas-btn saas-btn--quiet" href="{{ $rows->appends($filters)->nextPageUrl() }}" rel="next">{{ __('Older') }}</a>@endif
</p>
@endif

@if ($can_manage)
<h2>{{ __('Set up a school licence or sponsored places') }}</h2>
@php($chosen = collect($schools)->firstWhere('id', (int) ($filters['school'] ?? 0)))
@if ($chosen === null)
    <p class="saas-card">{{ __('Choose a school above to set up a licence or sponsored places for it.') }}</p>
@else
<div class="saas-grid">
    <form method="POST" action="{{ route('saas.subscriptions.licences.store', ['school' => $chosen['id']]) }}" class="saas-card" aria-label="{{ __('Set up a school licence') }}">
        @csrf
        <p><strong>{{ $chosen['name'] }}</strong></p>
        <p>{{ __('Every learner at the school gets the tutor and their families the insights, while the licence is paid. Default per learner a month: :amount.', ['amount' => $rands($defaults['licence_per_learner_cents'])]) }}</p>
        <div class="saas-field"><label for="licence-amount">{{ __('Agreed amount a period (rands)') }}</label><input id="licence-amount" name="amount" inputmode="decimal" required></div>
        <button class="saas-btn" type="submit">{{ __('Set up the licence') }}</button>
    </form>
    <form method="POST" action="{{ route('saas.subscriptions.sponsorships.store', ['school' => $chosen['id']]) }}" class="saas-card" aria-label="{{ __('Set up sponsored places') }}">
        @csrf
        <p><strong>{{ $chosen['name'] }}</strong></p>
        <p>{{ __('A sponsor pays for places at a school; the school names the learners. Default per place a month: :amount.', ['amount' => $rands($defaults['sponsored_place_cents'])]) }}</p>
        <div class="saas-field"><label for="sponsor-name">{{ __('Sponsor (an organisation)') }}</label><input id="sponsor-name" name="sponsor_name" required maxlength="120"></div>
        <div class="saas-field"><label for="sponsor-places">{{ __('Places') }}</label><input id="sponsor-places" name="places" type="number" min="1" max="10000" required></div>
        <div class="saas-field"><label for="sponsor-amount">{{ __('Agreed amount a period for all places (rands)') }}</label><input id="sponsor-amount" name="amount" inputmode="decimal" required></div>
        <button class="saas-btn" type="submit">{{ __('Set up the places') }}</button>
    </form>
</div>
@endif
@endif
@endsection
