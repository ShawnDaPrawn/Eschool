@extends('saas.layout')

@section('title', __('SMS and WhatsApp'))

@section('content')
@php($money = fn (array $cost): string => \App\Services\Platform\Saas\PlatformMessagingCosts::money($cost))
<h1>{{ __('SMS and WhatsApp') }}</h1>
<p class="saas-lead">{{ __('Messages each school sent by SMS and WhatsApp, and what Twilio charged for them, to pass on to the school at cost. Counts only: no numbers, people or message text. A price appears once Twilio reports it, usually within the hour.') }}</p>

<form method="GET" action="{{ route('saas.messaging') }}" class="saas-card" aria-label="{{ __('Choose the month') }}">
    <div class="saas-field">
        <label for="messaging-month">{{ __('Month') }}</label>
        <input id="messaging-month" name="month" type="month" value="{{ $month }}">
    </div>
    <button class="saas-btn" type="submit">{{ __('Show') }}</button>
</form>

<h2>{{ $label }}</h2>
@if ($rows === [])
    <p class="saas-card">{{ __('No SMS or WhatsApp messages were sent this month.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Messages and cost per school') }}" tabindex="0">
    <table class="saas-table">
        <caption>{{ __('Messages and cost per school for :month', ['month' => $label]) }}</caption>
        <thead><tr>
            <th scope="col">{{ __('School') }}</th>
            <th scope="col">{{ __('SMS sent') }}</th><th scope="col">{{ __('SMS segments') }}</th><th scope="col">{{ __('SMS cost') }}</th>
            <th scope="col">{{ __('WhatsApp sent') }}</th><th scope="col">{{ __('WhatsApp cost') }}</th>
            <th scope="col">{{ __('Not delivered') }}</th><th scope="col">{{ __('Awaiting a price') }}</th>
        </tr></thead>
        <tbody>
        @foreach ($rows as $row)
            @php($sms = $row['channels']['sms'] ?? ['messages' => 0, 'segments' => 0, 'failed' => 0, 'unpriced' => 0, 'cost' => []])
            @php($wa = $row['channels']['whatsapp'] ?? ['messages' => 0, 'segments' => 0, 'failed' => 0, 'unpriced' => 0, 'cost' => []])
            <tr>
                <th scope="row">{{ $row['school'] }}</th>
                <td>{{ $sms['messages'] }}</td><td>{{ $sms['segments'] }}</td><td>{{ $money($sms['cost']) }}</td>
                <td>{{ $wa['messages'] }}</td><td>{{ $money($wa['cost']) }}</td>
                <td>{{ $sms['failed'] + $wa['failed'] }}</td><td>{{ $sms['unpriced'] + $wa['unpriced'] }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <th scope="row">{{ __('All schools') }}</th>
            <td>{{ $totals['sms']['messages'] ?? 0 }}</td><td>{{ $totals['sms']['segments'] ?? 0 }}</td><td>{{ $money($totals['sms']['cost'] ?? []) }}</td>
            <td>{{ $totals['whatsapp']['messages'] ?? 0 }}</td><td>{{ $money($totals['whatsapp']['cost'] ?? []) }}</td>
            <td></td><td>{{ ($totals['sms']['unpriced'] ?? 0) + ($totals['whatsapp']['unpriced'] ?? 0) }}</td>
        </tr></tfoot>
    </table>
</div>
@endif
@endsection
