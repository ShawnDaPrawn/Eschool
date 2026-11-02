@extends('layouts.master')
@section('title', __('Delivery report'))
@section('content')
@php
    $states = ['delivered' => __('Delivered'), 'sent' => __('Sent'), 'queued' => __('Waiting to send'), 'bounced' => __('Bounced'), 'complained' => __('Marked as spam'), 'failed' => __('Failed'), 'suppressed' => __('Stopped before sending')];
    $notices = ['pending' => __('Not processed yet'), 'queued' => __('Notified'), 'none' => __('No notification channel')];
    $local = fn (?string $at) => $at === null ? null : \Illuminate\Support\Carbon::parse($at)->setTimezone($timezone)->format('Y-m-d H:i');
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Delivery report')" :subtitle="$report['title']" />
    <p><a href="{{ route('communication-centre.index', ['message_id' => $report['id']]) }}">{{ __('Back to the message') }}</a></p>
    <section class="page-section" aria-labelledby="delivery-summary">
        <h2 id="delivery-summary">{{ __(':read of :total recipients have read it', ['read' => $report['read_count'], 'total' => $report['recipient_count']]) }}</h2>
        <p>{{ __('Published') }}: {{ $local($report['published_at']) }} ({{ $timezone }})</p>
        @if ($report['not_notified'] > 0)
            <p>{{ __('Recipients with no notification channel') }}: {{ $report['not_notified'] }}. {{ __('Their own choices or access left no channel. They can still read the message in the app.') }}</p>
        @endif
        <div class="page-table__scroll" role="region" aria-label="{{ __('Delivery by channel') }}" tabindex="0"><table class="page-table__table">
            <caption>{{ __('Delivery by channel') }}</caption>
            <thead><tr><th scope="col">{{ __('Channel') }}</th>@foreach ($states as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach (['email' => __('Email'), 'push' => __('Push notification')] as $channel => $label)
                <tr><th scope="row">{{ $label }}</th>@foreach (array_keys($states) as $state)<td>{{ $report['channels'][$channel][$state] }}</td>@endforeach</tr>
            @endforeach
            </tbody>
        </table></div>
        <p>{{ __('People are counted once per channel. Push counts as sent when it reached at least one of the person\'s devices. Delivered, bounced and spam reports arrive from the email provider later.') }}</p>
    </section>
    <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
        <div class="page-table__scroll" role="region" aria-label="{{ __('Recipients') }}" tabindex="0"><table class="page-table__table">
            <caption>{{ __('Recipients') }}</caption>
            <thead><tr><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Read') }}</th><th scope="col">{{ __('Notification') }}</th><th scope="col">{{ __('Email') }}</th><th scope="col">{{ __('Push notification') }}</th></tr></thead>
            <tbody>
            @forelse ($report['items'] as $row)
                <tr>
                    <th scope="row">{{ $row['name'] }}</th>
                    <td>{{ $local($row['read_at']) ?? __('Not yet') }}</td>
                    <td>{{ $notices[$row['notice']] }}</td>
                    <td>{{ $row['email'] === null ? '—' : $states[$row['email']] }}</td>
                    <td>{{ $row['push'] === null ? '—' : $states[$row['push']] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('No recipients') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if ($report['page'] > 1)<a href="{{ route('communication-centre.report', ['message' => $report['id'], 'page' => $report['page'] - 1]) }}">{{ __('Previous page') }}</a>@endif
        @if ($report['page'] < $report['last_page'])<a href="{{ route('communication-centre.report', ['message' => $report['id'], 'page' => $report['page'] + 1]) }}">{{ __('Next page') }}</a>@endif
    </div></div>
</div>
@endsection
