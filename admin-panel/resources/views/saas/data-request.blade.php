@extends('saas.layout')

@use('App\Services\Platform\Privacy\DataRequests')

@section('title', $request['reference'])

@section('content')
<p><a href="{{ route('saas.data-requests.index') }}">{{ __('All data requests') }}</a></p>
<h1>{{ $request['reference'] }} · {{ __(DataRequests::KINDS[$request['kind']]) }}</h1>
@if (session('success'))<p role="status">{{ session('success') }}</p>@endif
@foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

<div class="saas-card">
    <dl>
        <dt>{{ __('School') }}</dt><dd>{{ $request['school'] }}</dd>
        <dt>{{ __('Status') }}</dt><dd>{{ __(DataRequests::STATUSES[$request['status']]) }}@if ($request['overdue']) <span class="saas-badge">{{ __('Overdue') }}</span>@endif</dd>
        <dt>{{ __('Received') }}</dt><dd>{{ $request['received_on'] }} · {{ __(DataRequests::CHANNELS[$request['channel']]) }} · {{ __('logged by :name', ['name' => $request['logged_by'] ?? __('someone no longer here')]) }}</dd>
        <dt>{{ __('Answer due') }}</dt><dd>{{ $request['due_on'] }}</dd>
        <dt>{{ __('Who is asking') }}</dt><dd>{{ $request['requester_name'] }} ({{ __(DataRequests::REQUESTER_ROLES[$request['requester_role']]) }}) · {{ $request['requester_contact'] }}</dd>
        <dt>{{ __('What they asked for') }}</dt><dd style="white-space: pre-line">{{ $request['details'] }}</dd>
        @if ($request['passed_at'])<dt>{{ __('Passed to the school') }}</dt><dd>{{ $request['passed_at'] }} · {{ $request['passed_by'] }}</dd>@endif
        @if ($request['answered_at'])
            <dt>{{ __('The school\'s answer') }}</dt><dd>{{ __(DataRequests::OUTCOMES[$request['outcome']]) }} · {{ $request['answered_at'] }} · {{ $request['answered_by'] }}</dd>
            <dt>{{ __('The school\'s note') }}</dt><dd style="white-space: pre-line">{{ $request['outcome_note'] }}</dd>
        @endif
        @if ($request['closed_at'])<dt>{{ __(DataRequests::STATUSES[$request['status']]) }}</dt><dd>{{ $request['closed_at'] }} · {{ $request['closed_by'] }} · {{ $request['closing_note'] }}</dd>@endif
    </dl>
</div>

@if ($contacts !== [])
<h2>{{ __('The school\'s privacy contacts') }}</h2>
<ul>
    @foreach ($contacts as $contact)
        <li>{{ $contact['name'] }} ({{ $contact['role'] === 'information_officer' ? __('Information Officer') : __('Principal') }}) · {{ $contact['email'] }} {{ $contact['phone'] }}</li>
    @endforeach
</ul>
@endif

@if ($request['status'] === 'received')
<form method="POST" action="{{ route('saas.data-requests.pass', $request['id']) }}" class="saas-card" aria-label="{{ __('Pass to the school') }}">
    @csrf
    <p>{{ __('The school\'s Super Admins are told a request is waiting, without any names, and see it under Policies, Data requests.') }}</p>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Pass to the school') }}</button></div>
</form>
@endif

@if ($request['status'] === 'answered')
<form method="POST" action="{{ route('saas.data-requests.close', $request['id']) }}" class="saas-card" aria-label="{{ __('Close the request') }}">
    @csrf
    <div class="saas-field">
        <label for="close-note">{{ __('What Navigate Ed did to finish (for example, deleted the backups the school asked about)') }}</label>
        <textarea id="close-note" name="note" required minlength="5" maxlength="500" rows="3"></textarea>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Close the request') }}</button></div>
</form>
@endif

@if (in_array($request['status'], ['received', 'with_school'], true))
<form method="POST" action="{{ route('saas.data-requests.withdraw', $request['id']) }}" class="saas-card" aria-label="{{ __('Record as withdrawn') }}">
    @csrf
    <div class="saas-field">
        <label for="withdraw-note">{{ __('How the requester withdrew it') }}</label>
        <textarea id="withdraw-note" name="note" required minlength="5" maxlength="500" rows="3"></textarea>
    </div>
    <div class="saas-actions"><button class="saas-btn saas-btn--quiet" type="submit">{{ __('Record as withdrawn') }}</button></div>
</form>
@endif
@endsection
