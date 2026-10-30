@extends('saas.layout')

@section('title', __('Support access'))

@section('content')
<p><a href="{{ route('saas.support.index') }}">{{ __('All support access') }}</a></p>
<h1>{{ __('Support access to :school', ['school' => $grant['school']]) }}</h1>

<dl class="saas-card">
    <dt>{{ __('State') }}</dt><dd><span class="saas-badge">{{ $grant['state_label'] }}</span>@if ($grant['emergency']) {{ __('(emergency override)') }}@endif</dd>
    <dt>{{ __('Asked by') }}</dt><dd>{{ $grant['requester'] }} · {{ $grant['role'] }} · {{ $grant['requested_at'] }}</dd>
    <dt>{{ __('Reason') }}</dt><dd>{{ $grant['reason'] }}</dd>
    <dt>{{ __('What they may do') }}</dt><dd>{{ $grant['scope'] }} · {{ trans_choice('{1} :count minute|[2,*] :count minutes', $grant['minutes'], ['count' => $grant['minutes']]) }}</dd>
    @if ($grant['decided_at'])<dt>{{ $grant['emergency'] ? __('Opened in an emergency by') : __('Decided by') }}</dt><dd>{{ $grant['decided_by'] }} · {{ $grant['decided_at'] }}</dd>@endif
    @if ($grant['emergency_reason'])<dt>{{ __('Emergency reason') }}</dt><dd>{{ $grant['emergency_reason'] }}</dd>@endif
    @if ($grant['starts_at'])<dt>{{ __('Open') }}</dt><dd>{{ __(':from to :to (South African time)', ['from' => $grant['starts_at'], 'to' => $grant['expires_at']]) }}</dd>@endif
    @if ($grant['ended_at'])<dt>{{ __('Ended early') }}</dt><dd>{{ $grant['ended_at'] }} · {{ $grant['ended_by'] }} · {{ $grant['ended_side'] === 'school' ? __('by the school') : __('by Navigate Ed') }}</dd>@endif
</dl>

<div class="saas-actions">
    @if ($grant['state'] === 'active' && $grant['requester_id'] === $me)<a class="saas-btn" href="{{ route('support-session.show', $grant['id']) }}">{{ __('Open the school (read-only)') }}</a>@endif
    @if (in_array($grant['state'], ['pending', 'active'], true) && ($grant['requester_id'] === $me || $canOverride))
        <form method="POST" action="{{ route('saas.support.end', $grant['id']) }}">
            @csrf
            <button class="saas-btn saas-btn--quiet" type="submit">{{ $grant['state'] === 'pending' ? __('Withdraw the request') : __('End the session now') }}</button>
        </form>
    @endif
</div>

@if ($grant['state'] === 'pending' && $canOverride)
<h2>{{ __('Emergency override') }}</h2>
<form method="POST" action="{{ route('saas.support.emergency', $grant['id']) }}" class="saas-card" aria-label="{{ __('Emergency override') }}">
    @csrf
    <p>{{ __('Only when the school cannot be reached and waiting would cause harm. Access opens now, for the length asked, and the school\'s Super Admins are told at once. The reason is recorded and shown to the school.') }}</p>
    <div class="saas-field">
        <label for="emergency-reason">{{ __('Why this cannot wait') }}</label>
        <textarea id="emergency-reason" name="emergency_reason" required minlength="20" maxlength="500" rows="3">{{ old('emergency_reason') }}</textarea>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Open emergency access') }}</button></div>
</form>
@endif

<h2>{{ __('Pages viewed') }}</h2>
@if ($views->isEmpty())
    <p class="saas-card">{{ __('No page has been viewed.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Pages viewed') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('When (South African time)') }}</th><th scope="col">{{ __('Page') }}</th><th scope="col">{{ __('Route') }}</th><th scope="col">{{ __('Record') }}</th></tr></thead>
        <tbody>
        @foreach ($views as $view)
            <tr><td>{{ $view['at'] }}</td><th scope="row">{{ $view['page'] }}</th><td><code>{{ $view['route'] }}</code></td><td>{{ $view['record'] ?? '—' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<p>
    @if ($views->previousPageUrl())<a href="{{ $views->previousPageUrl() }}">{{ __('Newer pages') }}</a>@endif
    @if ($views->nextPageUrl())<a href="{{ $views->nextPageUrl() }}">{{ __('Older pages') }}</a>@endif
</p>
@endif
@endsection
