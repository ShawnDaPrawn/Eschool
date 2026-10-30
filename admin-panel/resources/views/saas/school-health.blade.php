@extends('saas.layout')

@section('title', __('School health').' · '.$school['name'])

@section('content')
@php
    $m = $health['metrics'];
    $states = ['flagged' => __('At risk'), 'ok' => __('OK'), 'unknown' => __('Not enough data')];
    $groups = ['Parent' => __('Parents and guardians'), 'Student' => __('Learners'), 'Teacher' => __('Teachers'), 'Staff' => __('Other staff')];
    $share = fn (int $count, int $of): string => $of === 0 ? '–' : intdiv($count * 100, $of).'%';
    $money = fn (int $cents, string $currency): string => $currency.' '.number_format(intdiv($cents, 100)).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    $flagged = array_values(array_filter($health['flags'], fn ($flag) => $flag['state'] === 'flagged'));
@endphp
<h1>{{ $school['name'] }}</h1>
<p class="saas-lead">
    {{ __('School health, computed :time.', ['time' => $health['computed_at']->format('Y-m-d H:i')]) }}
    {{ $health['last_snapshot_at'] ? __('Last nightly figures: :time.', ['time' => $health['last_snapshot_at']]) : __('No nightly figures yet: the trend appears after the first nights.') }}
    {{ __('Counts and shares only: no learner, parent or staff record is shown here.') }}
</p>

<h2>{{ __('At-risk flags') }}</h2>
<p>{{ $flagged === [] ? __('No flags.') : trans_choice(':count flag|:count flags', count($flagged), ['count' => count($flagged)]) }}</p>
<ul class="saas-card" aria-label="{{ __('At-risk rules') }}">
    @foreach ($health['flags'] as $flag)
        <li data-health-flag="{{ $flag['key'] }}" data-state="{{ $flag['state'] }}"><span class="saas-badge {{ $flag['state'] === 'flagged' ? 'saas-badge--warn' : '' }}">{{ $states[$flag['state']] }}</span> <strong>{{ $flag['label'] }}</strong>: {{ $flag['detail'] }}</li>
    @endforeach
</ul>

<h2>{{ __('Families') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Families') }}" tabindex="0">
    <table class="saas-table">
        <tbody>
            <tr><th scope="row">{{ __('Weekly active families') }}</th><td class="num" data-health="active-families">{{ $m['families']['active'] }} {{ __('of') }} {{ $m['families']['guardians'] }} {{ __('guardians') }} ({{ $share($m['families']['active'], $m['families']['guardians']) }})</td></tr>
            <tr><th scope="row">{{ __('Average in the four weeks before') }}</th><td class="num">{{ $health['history'] === [] ? __('Not yet available') : intdiv(array_sum($health['history']), count($health['history'])) }}</td></tr>
            <tr><th scope="row">{{ __('Learners with a guardian the school can reach') }}</th><td class="num" data-health="reach">{{ $m['reach']['reached'] }} {{ __('of') }} {{ $m['reach']['learners'] }} ({{ $share($m['reach']['reached'], $m['reach']['learners']) }})</td></tr>
            <tr><th scope="row">{{ __('… by a verified email address') }}</th><td class="num">{{ $m['reach']['by_email'] }}</td></tr>
            <tr><th scope="row">{{ __('… by push notification') }}</th><td class="num">{{ $m['reach']['by_push'] }}</td></tr>
            <tr><th scope="row">{{ __('Learners active in the app, last 7 days') }}</th><td class="num">{{ $m['learners_active'] }}</td></tr>
        </tbody>
    </table>
</div>

<h2>{{ __('Sign-ins by role') }}</h2>
<p>{{ __('People who signed in to an app or used one. Sign-ins to the staff web panel are not recorded yet.') }}</p>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Sign-ins by role') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Role') }}</th><th scope="col" class="num">{{ __('People') }}</th><th scope="col" class="num">{{ __('Last 7 days') }}</th><th scope="col" class="num">{{ __('Last 30 days') }}</th></tr></thead>
        <tbody>
        @foreach ($m['sign_ins'] as $group => $row)
            <tr data-health-role="{{ $group }}"><th scope="row">{{ $groups[$group] ?? $group }}</th><td class="num">{{ $row['people'] }}</td><td class="num">{{ $row['days_7'] }}</td><td class="num">{{ $row['days_30'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('Modules') }}</h2>
<p>{{ trans_choice(':count module on|:count modules on', $m['modules']['on'], ['count' => $m['modules']['on']]) }}. {{ __('Activity is the messages each module sent and the AI requests it made in the last 30 days; it is not shown for sensitive modules.') }}</p>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Modules') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Module') }}</th><th scope="col" class="num">{{ __('Messages') }}</th><th scope="col" class="num">{{ __('AI requests') }}</th></tr></thead>
        <tbody>
        @foreach ($m['modules']['list'] as $module)
            <tr data-health-module="{{ $module['key'] }}">
                <th scope="row">{{ __($module['name']) }} @if ($module['read_only'])<span class="saas-badge">{{ __('Read only') }}</span>@endif</th>
                @if ($module['sensitive'])
                    <td class="num" colspan="2">{{ __('Not shown (sensitive)') }}</td>
                @else
                    <td class="num">{{ $module['messages'] }}</td><td class="num">{{ $module['ai_requests'] }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('Email and push, last 30 days') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Email and push') }}" tabindex="0">
    <table class="saas-table">
        <tbody>
            <tr><th scope="row">{{ __('Emails sent') }}</th><td class="num" data-health="mail-sent">{{ $m['delivery']['mail_sent'] }}</td></tr>
            <tr><th scope="row">{{ __('Bounced') }}</th><td class="num" data-health="mail-bounced">{{ $m['delivery']['mail_bounced'] }} ({{ $share($m['delivery']['mail_bounced'], $m['delivery']['mail_sent']) }})</td></tr>
            <tr><th scope="row">{{ __('Marked as spam') }}</th><td class="num">{{ $m['delivery']['mail_complained'] }}</td></tr>
            <tr><th scope="row">{{ __('Could not be sent') }}</th><td class="num">{{ $m['delivery']['mail_failed'] }}</td></tr>
            <tr><th scope="row">{{ __('Push attempts') }}</th><td class="num">{{ $m['delivery']['push_attempts'] }}</td></tr>
            <tr><th scope="row">{{ __('Push failures') }}</th><td class="num" data-health="push-failed">{{ $m['delivery']['push_failed'] }} ({{ $share($m['delivery']['push_failed'], $m['delivery']['push_attempts']) }})</td></tr>
            <tr><th scope="row">{{ __('The school\'s own sending status') }}</th><td class="num">{{ __('Not yet available') }}</td></tr>
        </tbody>
    </table>
</div>

<h2>{{ __('AI this month') }}</h2>
<p class="saas-card" data-health="ai">
    {{ __('Spent or reserved: :spent', ['spent' => $money($m['ai']['spent_cents'], $m['ai']['currency'])]) }}
    @if ($m['ai']['cap_cents'] !== null)
        {{ __('of a cap of :cap (:daily a day), :share.', ['cap' => $money($m['ai']['cap_cents'], $m['ai']['currency']), 'daily' => $money($m['ai']['daily_cap_cents'], $m['ai']['currency']), 'share' => $share($m['ai']['spent_cents'], $m['ai']['cap_cents'])]) }}
    @else
        {{ __('No cap is set.') }}
    @endif
    @if ($m['ai']['paused']) <span class="saas-badge saas-badge--warn">{{ __('Paused by the school') }}</span>@endif
    @if (! $m['ai']['enabled']) <span class="saas-badge">{{ __('AI is switched off on the platform') }}</span>@endif
</p>

<h2>{{ __('Failed jobs and errors') }}</h2>
<p class="saas-card" data-health="failed-jobs">
    {{ trans_choice(':count queued job of this school failed in the last 30 days.|:count queued jobs of this school failed in the last 30 days.', $m['failed_jobs']['count'], ['count' => $m['failed_jobs']['count']]) }}
    @foreach ($m['failed_jobs']['by_job'] as $job => $count)<span class="saas-badge">{{ $job }}: {{ $count }}</span> @endforeach
    <br>{{ __('Application errors: not yet available.') }}
</p>

<h2>{{ __('Storage') }}</h2>
<p class="saas-card">{{ __('Not yet available: file sizes are not recorded for most uploads.') }}</p>

<p class="saas-actions">
    <a class="saas-btn saas-btn--quiet" href="{{ route('saas.health') }}">{{ __('Compare schools') }}</a>
    <a class="saas-btn saas-btn--quiet" href="{{ route('saas.schools.show', $school['id']) }}">{{ __('Back to the school') }}</a>
</p>
@endsection
