@extends('saas.layout')

@section('title', 'Mail streams')

@php
    $canManage = app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'mail.manage');
    $percent = fn (?float $rate): string => $rate === null ? '–' : number_format($rate * 100, 2).'%';
    $setLabels = ['ready' => 'Ready', 'pending' => 'Not set up yet', 'failed' => 'Problem', 'not_set_up' => 'Not set up'];
    $errorLabels = [
        'ses_unreachable' => 'SES could not be reached at the last check',
        'missing_in_ses' => 'SES no longer has this configuration set',
        'no_event_topic' => 'No feedback SNS topic is configured',
    ];
@endphp

@section('content')
<h1>Mail streams</h1>
<p class="saas-lead">Each school sends through two Amazon SES configuration sets: <strong>account</strong> (sign-in, passwords, invitations, welcome mails and safety notices such as safeguarding alerts, urgent notices and absences) and <strong>bulk</strong> (every other notice). Rates are over the last {{ $days }} days, from what SES reported back. Counts only: no person is shown.</p>

@unless ($enabled)
    <p class="saas-card" role="status">SES streams are not switched on (MAIL_SES_STREAMS). Mail is sent without a configuration set; the counts below still show each school's mail by stream.</p>
@endunless

@foreach ($targets as $target)
<section class="saas-card" aria-labelledby="streams-{{ $target['school_id'] ?? 'platform' }}">
    <h2 id="streams-{{ $target['school_id'] ?? 'platform' }}">{{ $target['name'] }}@if ($target['school_status'] !== null && $target['school_status'] !== 'active') <span class="saas-badge">{{ ucfirst($target['school_status']) }}</span>@endif</h2>
    <div class="saas-table-wrap" role="region" aria-label="Streams of {{ $target['name'] }}" tabindex="0">
        <table class="saas-table">
            <thead><tr><th scope="col">Stream</th><th scope="col">Configuration set</th><th scope="col">Set up</th><th scope="col">Sending</th><th scope="col">Tenant status</th><th scope="col">Sent</th><th scope="col">Bounced</th><th scope="col">Complaints</th></tr></thead>
            <tbody>
            @foreach ($target['streams'] as $stream)
                <tr>
                    <th scope="row">{{ ucfirst($stream['stream']) }}</th>
                    <td><code>{{ $stream['configuration_set'] }}</code></td>
                    <td>
                        {{ $setLabels[$stream['status']] ?? $stream['status'] }}
                        @if ($stream['last_error'])<br><span class="saas-badge saas-badge--warn">{{ $errorLabels[$stream['last_error']] ?? $stream['last_error'] }}</span>@endif
                        @if ($stream['checked_at'])<br><small>Checked {{ $stream['checked_at'] }}</small>@endif
                    </td>
                    <td>
                        @if ($stream['sending_enabled'] === null) –
                        @elseif ($stream['sending_enabled']) Enabled
                        @else <span class="saas-badge saas-badge--warn">Paused in SES</span>
                        @endif
                    </td>
                    <td>
                        @if ($stream['tenant_status'] === null) –
                        @else
                            <span @class(['saas-badge', 'saas-badge--warn' => $stream['tenant_status'] !== 'ENABLED'])>{{ ['ENABLED' => 'Enabled', 'DISABLED' => 'Paused', 'REINSTATED' => 'Reinstated'][$stream['tenant_status']] ?? $stream['tenant_status'] }}</span>
                            <br><small>{{ $stream['tenant_status_origin'] === 'AWS_SES' ? 'By AWS' : 'By policy or by hand' }}, {{ $stream['tenant_status_at'] }}@if ($stream['tenant_status_cause']): {{ $stream['tenant_status_cause'] }}@endif</small>
                        @endif
                    </td>
                    <td>{{ $stream['sent'] }}</td>
                    <td>{{ $stream['bounced'] }} ({{ $percent($stream['bounce_rate']) }})</td>
                    <td>
                        {{ $stream['complained'] }} ({{ $percent($stream['complaint_rate']) }})
                        @if ($stream['flag'] === 'pause')<br><span class="saas-badge saas-badge--warn">At SES's pause level</span>
                        @elseif ($stream['flag'] === 'review')<br><span class="saas-badge saas-badge--warn">At SES's review level</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if ($enabled && $canManage)
        <form method="POST" action="{{ route('saas.mail-streams.check') }}" class="saas-actions">
            @csrf
            @if ($target['school_id'] !== null)<input type="hidden" name="school_id" value="{{ $target['school_id'] }}">@endif
            <button class="saas-btn saas-btn--quiet" type="submit">Set up or check now<span class="m3-visually-hidden"> for {{ $target['name'] }}</span></button>
        </form>
    @endif
</section>
@endforeach
@endsection
