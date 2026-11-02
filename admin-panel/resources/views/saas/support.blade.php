@extends('saas.layout')

@section('title', __('Support desk'))

@use('App\Models\Platform\SupportTicket')

@section('content')
<h1>{{ __('Support desk') }}</h1>
<p class="saas-lead">{{ __('Tickets schools open from their panel under Settings. Urgent tickets come first, then the oldest. Replies go to the school; internal notes never do.') }}</p>
<p>
    @if ($lastSeen === null)
        {{ __('This is your first visit: every ticket is marked new.') }}
    @else
        {{ __('Marked new: changed since you last opened the queue (:time).', ['time' => $lastSeen]) }}
    @endif
</p>

<h2>{{ __('Queue') }}</h2>
@if ($queue === [])
    <p class="saas-card">{{ __('No open tickets.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Queue') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Priority') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Opened') }}</th><th scope="col">{{ __('Assigned to') }}</th></tr></thead>
        <tbody>
        @foreach ($queue as $ticket)
            <tr>
                <td><a href="{{ route('saas.support.show', $ticket['id']) }}">{{ $ticket['reference'] }}</a>@if ($ticket['new']) <span class="saas-badge">{{ __('New') }}</span>@endif</td>
                <th scope="row">{{ $ticket['subject'] }}</th>
                <td>{{ $ticket['school'] }}</td>
                <td><span class="saas-badge {{ $ticket['priority'] === 'urgent' ? 'saas-badge--warn' : '' }}">{{ SupportTicket::PRIORITY_LABELS[$ticket['priority']] }}</span></td>
                <td>{{ SupportTicket::STATUS_LABELS[$ticket['status']] }}</td>
                <td>{{ $ticket['opened_at'] }} · {{ trans_choice('{0} today|{1} 1 day ago|[2,*] :count days ago', $ticket['days_open'], ['count' => $ticket['days_open']]) }}</td>
                <td>{{ $ticket['assignee'] ?? __('Nobody') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2>{{ __('Recently resolved or closed') }}</h2>
@if ($recent === [])
    <p class="saas-card">{{ __('Nothing resolved or closed yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Recently resolved or closed') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Last change') }}</th></tr></thead>
        <tbody>
        @foreach ($recent as $ticket)
            <tr>
                <td><a href="{{ route('saas.support.show', $ticket['id']) }}">{{ $ticket['reference'] }}</a>@if ($ticket['new']) <span class="saas-badge">{{ __('New') }}</span>@endif</td>
                <th scope="row">{{ $ticket['subject'] }}</th>
                <td>{{ $ticket['school'] }}</td>
                <td>{{ SupportTicket::STATUS_LABELS[$ticket['status']] }}</td>
                <td>{{ $ticket['updated_at'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
