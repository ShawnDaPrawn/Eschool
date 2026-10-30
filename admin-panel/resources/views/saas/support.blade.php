@extends('saas.layout')

@section('title', __('Support access'))

@use('App\Services\Platform\Saas\SupportAccess')

@section('content')
<h1>{{ __('Support access') }}</h1>
<p class="saas-lead">{{ __('The only way into a school\'s records. Ask the school with a reason; its Super Admin approves or declines in the school panel. Access is read-only, lasts at most four hours, never reaches wellbeing, counselling, health, safeguarding, discipline, early-warning or support-plan records, and every page you open is logged for the school to see.') }}</p>

<h2>{{ __('Open requests and sessions') }}</h2>
@if ($open === [])
    <p class="saas-card">{{ __('No request is waiting and no session is open.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Open requests and sessions') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Asked by') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Asked') }}</th><th scope="col">{{ __('Ends') }}</th><th scope="col">{{ __('Pages viewed') }}</th><th scope="col">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($open as $grant)
            <tr>
                <th scope="row"><a href="{{ route('saas.support.show', $grant['id']) }}">{{ $grant['school'] }}</a></th>
                <td>{{ $grant['requester'] }} · {{ $grant['role'] }}</td>
                <td><span class="saas-badge">{{ $grant['state_label'] }}</span>@if ($grant['emergency']) {{ __('(emergency)') }}@endif
                    @if ($grant['no_approver'])<br>{{ __('Nobody at this school can approve it') }}@endif</td>
                <td>{{ $grant['requested_at'] }}</td>
                <td>{{ $grant['expires_at'] ?? __(':minutes minutes once approved', ['minutes' => $grant['minutes']]) }}</td>
                <td>{{ $grant['views'] }}</td>
                <td>
                    @if ($grant['state'] === 'active' && $grant['requester_id'] === $me)<a class="saas-btn" href="{{ route('support-session.show', $grant['id']) }}">{{ __('Open the school') }}</a>@endif
                    <a href="{{ route('saas.support.show', $grant['id']) }}">{{ __('Details') }}</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h2 id="request">{{ __('Ask a school for access') }}</h2>
<form method="POST" action="{{ route('saas.support.store') }}" class="saas-card" aria-label="{{ __('Ask a school for access') }}">
    @csrf
    <div class="saas-grid">
        <div class="saas-field">
            <label for="support-school">{{ __('School') }}</label>
            <select id="support-school" name="school_id" required>
                <option value="">{{ __('Choose a school') }}</option>
                @foreach ($schoolChoices as $id => $name)<option value="{{ $id }}" @selected((string) old('school_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="support-minutes">{{ __('How long') }}</label>
            <select id="support-minutes" name="minutes" required>
                @foreach (SupportAccess::durations() as $minutes)<option value="{{ $minutes }}" @selected((int) old('minutes', 60) === $minutes)>{{ trans_choice('{1} :count minute|[2,59] :count minutes|[60,*] :hours hours', $minutes, ['count' => $minutes, 'hours' => rtrim(rtrim(number_format($minutes / 60, 1), '0'), '.')]) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="support-scope">{{ __('What you may do') }}</label>
            <select id="support-scope" name="scope" required>
                @foreach ((array) config('support_access.scopes') as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="support-reason">{{ __('Why you need access (the school reads this; refer to tickets and records by id, not by name)') }}</label>
            <textarea id="support-reason" name="reason" required minlength="20" maxlength="500" rows="4">{{ old('reason') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Send the request') }}</button></div>
</form>

@if ($recent !== [])
<h2>{{ __('Earlier requests') }}</h2>
<ul>
    @foreach ($recent as $grant)
        <li><a href="{{ route('saas.support.show', $grant['id']) }}">{{ $grant['school'] }}</a> · {{ $grant['requester'] }} · {{ $grant['state_label'] }}@if ($grant['emergency']) {{ __('(emergency)') }}@endif · {{ $grant['requested_at'] }} · {{ trans_choice('{0} no pages viewed|{1} 1 page viewed|[2,*] :count pages viewed', $grant['views'], ['count' => $grant['views']]) }}</li>
    @endforeach
</ul>
@endif
@endsection
