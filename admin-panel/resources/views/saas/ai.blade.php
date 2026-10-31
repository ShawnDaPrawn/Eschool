@extends('saas.layout')

@section('title', __('AI control centre'))

@php
    $yesNo = fn (bool $value): string => $value ? __('Yes') : __('No');
@endphp

@section('content')
<h1>{{ __('AI control centre') }}</h1>
<p class="saas-lead">{{ __('Which AI features and providers are qualified, what each school has spent against its caps, how many safeguarding flags wait for each school\'s reviewers, and which AI features each school has switched on. Only settings, totals and counts are shown here: never a prompt, an answer, a flag\'s content or a learner.') }}</p>
<p>
    {{ $platformOn ? __('Inference is switched on for the platform in config/ai.php.') : __('Inference is switched off for the whole platform in config/ai.php, so no AI feature answers anywhere, whatever a school has switched on.') }}
    {{ __('Amounts are in the currency the gateway meters in (:currency now). No exchange rate is applied.', ['currency' => $currency]) }}
</p>
@if ($costReport)
    <p><a href="{{ route('ai-platform.costs') }}">{{ __('Open the dated cost report (your AI cost grant)') }}</a></p>
@endif

<h2>{{ __('Providers') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Providers') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Provider') }}</th><th scope="col">{{ __('Qualification') }}</th><th scope="col">{{ __('Key set') }}</th><th scope="col">{{ __('Moderation model') }}</th><th scope="col">{{ __('Features using it') }}</th></tr></thead>
        <tbody>
        @foreach ($providers as $provider)
            <tr>
                <th scope="row">{{ $provider['name'] }}</th>
                <td><span class="saas-badge {{ $provider['qualified'] ? '' : 'saas-badge--warn' }}">{{ $provider['qualified'] ? __('Qualified') : __('Not qualified') }}</span></td>
                <td>{{ $yesNo($provider['key_set']) }}</td>
                <td>{{ $provider['moderation_model'] ?? __('None configured') }}</td>
                <td>{{ $provider['features'] === [] ? __('None') : implode(', ', $provider['features']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<h2>{{ __('Features') }}</h2>
@if ($features === [])
    <p class="saas-card">{{ __('No AI feature is configured.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Features') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr>
            <th scope="col">{{ __('Feature') }}</th><th scope="col">{{ __('Qualification') }}</th><th scope="col">{{ __('Provider and model') }}</th>
            <th scope="col">{{ __('Switched on for the platform') }}</th><th scope="col">{{ __('Price set') }}</th><th scope="col">{{ __('Evaluation') }}</th>
            <th scope="col">{{ __('Daily cap per school') }}</th><th scope="col">{{ __('Daily cap per person') }}</th><th scope="col">{{ __('Daily cap per learner') }}</th>
            <th scope="col">{{ __('School module') }}</th>
        </tr></thead>
        <tbody>
        @foreach ($features as $feature)
            <tr>
                <th scope="row">{{ $feature['label'] }}</th>
                <td><span class="saas-badge {{ $feature['qualified'] ? '' : 'saas-badge--warn' }}">{{ $feature['qualified'] ? __('Qualified') : __('Not qualified') }}</span></td>
                <td>{{ $feature['provider'] !== '' ? $feature['provider'] : __('No provider') }} · {{ $feature['model'] !== '' ? $feature['model'] : __('no model set') }}</td>
                <td>{{ $yesNo($feature['switched_on']) }}</td>
                <td>{{ $yesNo($feature['priced']) }}</td>
                <td>{{ __('No evaluation result recorded') }}@if ($feature['evaluation_cases'] !== null) · {{ trans_choice('{1} 1 case in its corpus|[2,*] :count cases in its corpus', $feature['evaluation_cases'], ['count' => $feature['evaluation_cases']]) }}@endif</td>
                @foreach (['school', 'actor', 'learner'] as $who)
                    <td>{{ $feature['caps'][$who.'_cents'] === null ? __('Not set') : \App\Services\Platform\Saas\PlatformAiControls::money($feature['caps'][$who.'_cents'], $currency) }}@if ($feature['caps'][$who.'_tokens'] !== null) · {{ __(':tokens tokens', ['tokens' => number_format($feature['caps'][$who.'_tokens'])]) }}@endif</td>
                @endforeach
                <td>{{ $feature['module'] === null ? __('None') : ($modules[$feature['module']]['name'] ?? $feature['module']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<p>{{ __('Evaluation pass marks (config/ai.php):') }}
    @foreach ($thresholds as $metric => $percent){{ \Illuminate\Support\Str::headline($metric) }} {{ $percent === null ? __('not set') : rtrim(rtrim(number_format($percent, 2), '0'), '.').'%' }}@if (! $loop->last), @endif @endforeach.
    {{ __('Results of ai:eval runs are not stored, so none is shown.') }}</p>

<h2>{{ __('Schools') }}</h2>
<p>{{ __('Spend counts actual and reserved charges. The daily cap is the highest feature cap, lowered by the school\'s own AI settings when it set a lower one; the gateway checks each day\'s whole spend against it. Today and this month follow each school\'s own clock.') }}</p>
@if ($schools === [])
    <p class="saas-card">{{ __('No schools yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Schools') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr>
            <th scope="col">{{ __('School') }}</th><th scope="col">{{ __('This month') }}</th><th scope="col">{{ __('Today against the daily cap') }}</th>
            <th scope="col">{{ __('Daily cap per learner') }}</th><th scope="col">{{ __('Safeguarding queue') }}</th><th scope="col">{{ __('AI features switched on') }}</th>
        </tr></thead>
        <tbody>
        @foreach ($schools as $school)
            <tr>
                <th scope="row">{{ $school['name'] }}@if ($school['status'] !== 'active') <span class="saas-badge saas-badge--warn">{{ __(ucfirst($school['status'])) }}</span>@endif @if ($school['paused']) <span class="saas-badge saas-badge--warn">{{ __('AI paused by the school') }}</span>@endif</th>
                <td>{{ $school['month'] === [] ? __('Nothing metered') : implode('; ', $school['month']) }}</td>
                <td>
                    @if ($school['cap'] === null){{ $school['today'] }} · {{ __('no cap configured') }}
                    @else{{ __(':spent of :cap', ['spent' => $school['today'], 'cap' => $school['cap']]) }} ({{ $school['used_percent'] }}%)@if ($school['over']) <span class="saas-badge saas-badge--warn">{{ __('Cap reached') }}</span>@endif
                    @endif
                    @if ($school['own_limits']) · {{ __('school set its own limits') }}@endif
                </td>
                <td>{{ $school['learner_cap'] ?? __('Not set') }}</td>
                <td>{{ trans_choice('{0} none waiting|{1} 1 waiting|[2,*] :count waiting', $school['queue']['open'], ['count' => $school['queue']['open']]) }}@if ($school['queue']['escalated'] > 0) · {{ trans_choice('{1} 1 escalated|[2,*] :count escalated', $school['queue']['escalated'], ['count' => $school['queue']['escalated']]) }}@endif</td>
                <td>
                    @php $on = array_keys(array_filter($school['modules'], fn ($state) => $state['enabled'])); @endphp
                    {{ $on === [] ? __('None') : implode(', ', array_map(fn ($key) => $modules[$key]['name'] ?? $key, $on)) }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if ($lastPage > 1)
    <p>
        @if ($page > 1)<a href="{{ route('saas.ai', ['page' => $page - 1]) }}">{{ __('Previous schools') }}</a>@endif
        {{ __('Page :page of :last', ['page' => $page, 'last' => $lastPage]) }}
        @if ($page < $lastPage)<a href="{{ route('saas.ai', ['page' => $page + 1]) }}">{{ __('More schools') }}</a>@endif
    </p>
@endif
@endif

@if ($canManage && $schoolChoices !== [] && $modules !== [])
<h2 id="switch">{{ __('Switch an AI feature for a school') }}</h2>
<form method="POST" action="{{ route('saas.ai.switch') }}" class="saas-card" aria-label="{{ __('Switch an AI feature for a school') }}">
    @csrf
    @method('PUT')
    <p>{{ __('This switches the school module the feature runs in, as the school\'s own module page does. A feature that is not qualified cannot be switched on. Give the reason without naming a learner, parent or staff member: it is kept in the audit log.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="ai-school">{{ __('School') }}</label>
            <select id="ai-school" name="school_id" required>
                @foreach ($schoolChoices as $id => $name)<option value="{{ $id }}" @selected((string) old('school_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="ai-module">{{ __('Feature') }}</label>
            <select id="ai-module" name="module" required>
                @foreach ($modules as $module)<option value="{{ $module['key'] }}" @selected(old('module') === $module['key'])>{{ $module['name'] }} ({{ implode(', ', $module['features']) }}){{ $module['qualified'] ? '' : ' · '.__('not qualified') }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="ai-enabled">{{ __('Switch') }}</label>
            <select id="ai-enabled" name="enabled" required>
                <option value="1" @selected(old('enabled') === '1')>{{ __('On') }}</option>
                <option value="0" @selected(old('enabled', '0') === '0')>{{ __('Off') }}</option>
            </select>
        </div>
        <div class="saas-field">
            <label for="ai-reason">{{ __('Reason') }}</label>
            <textarea id="ai-reason" name="reason" required minlength="10" maxlength="500" rows="3">{{ old('reason') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Switch') }}</button></div>
</form>
@endif
@endsection
