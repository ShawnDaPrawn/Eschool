@extends('saas.layout')

@section('title', __('Content library'))

@section('content')
<h1>{{ __('Content library') }}</h1>
<p class="saas-lead">{{ __('Navigate Ed\'s own practice questions, lessons, diagnostics and past-paper questions, by curriculum framework, subject and topic. Nothing a school writes ever comes into the library. A school\'s learners see published library questions only when the school is switched on below and has too few approved questions of its own.') }}</p>
@if ($canReview)
    <p><a href="{{ route('saas.library.reviews') }}">{{ trans_choice('{0} Nothing is waiting for review.|{1} 1 version is waiting for review.|[2,*] :count versions are waiting for review.', $waiting, ['count' => $waiting]) }}</a></p>
@else
    <p>{{ trans_choice('{0} Nothing is waiting for review.|{1} 1 version is waiting for review.|[2,*] :count versions are waiting for review.', $waiting, ['count' => $waiting]) }}</p>
@endif

<h2>{{ __('Frameworks') }}</h2>
@if ($frameworks === [])
    <p class="saas-card">{{ __('No frameworks yet. Add one below, such as CAPS.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Frameworks') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Code') }}</th><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Topics') }}</th><th scope="col">{{ __('Items') }}</th><th scope="col">{{ __('Published') }}</th></tr></thead>
        <tbody>
        @foreach ($frameworks as $framework)
            <tr>
                <td><a href="{{ route('saas.library.frameworks.show', $framework['id']) }}">{{ $framework['code'] }}</a></td>
                <th scope="row">{{ $framework['name'] }}</th>
                <td>{{ $framework['topics'] }}</td>
                <td>{{ $framework['items'] }}</td>
                <td>{{ $framework['published'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<form method="POST" action="{{ route('saas.library.frameworks.store') }}" class="saas-card" aria-label="{{ __('Add a framework') }}">
    @csrf
    <h3>{{ __('Add a framework') }}</h3>
    <p>{{ __('Use the code a school\'s curriculum carries (CAPS or IEB), so its subjects find their library topics.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="framework-code">{{ __('Code') }}</label>
            <input id="framework-code" name="code" required maxlength="16" pattern="[A-Z][A-Z0-9_\-]{1,15}" value="{{ old('code') }}">
        </div>
        <div class="saas-field">
            <label for="framework-name">{{ __('Name') }}</label>
            <input id="framework-name" name="name" required minlength="3" maxlength="191" value="{{ old('name') }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the framework') }}</button></div>
</form>

<h2>{{ __('Sources and licences') }}</h2>
<p>{{ __('Every item names where its content comes from and the licence that lets learners use it.') }}</p>
@if ($sources !== [])
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Sources and licences') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Source') }}</th><th scope="col">{{ __('Kind') }}</th><th scope="col">{{ __('Licence') }}</th><th scope="col">{{ __('Attribution') }}</th></tr></thead>
        <tbody>
        @foreach ($sources as $source)
            <tr>
                <th scope="row">@if ($source['url'])<a href="{{ $source['url'] }}" rel="noopener noreferrer">{{ $source['title'] }}</a>@else{{ $source['title'] }}@endif</th>
                <td>{{ $source['kind'] }}</td>
                <td>{{ $source['licence'] }}</td>
                <td>{{ $source['attribution'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<form method="POST" action="{{ route('saas.library.sources.store') }}" class="saas-card" aria-label="{{ __('Add a source') }}">
    @csrf
    <h3>{{ __('Add a source') }}</h3>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="source-title">{{ __('Title') }}</label>
            <input id="source-title" name="title" required minlength="3" maxlength="191" value="{{ old('title') }}">
        </div>
        <div class="saas-field">
            <label for="source-kind">{{ __('Kind') }}</label>
            <select id="source-kind" name="kind" required>
                @foreach ($sourceKinds as $value => $label)<option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="source-licence">{{ __('Licence') }}</label>
            <input id="source-licence" name="licence" required minlength="2" maxlength="191" value="{{ old('licence') }}">
        </div>
        <div class="saas-field">
            <label for="source-attribution">{{ __('Attribution') }}</label>
            <input id="source-attribution" name="attribution" required minlength="2" maxlength="500" value="{{ old('attribution') }}">
        </div>
        <div class="saas-field">
            <label for="source-url">{{ __('Web address (https), if any') }}</label>
            <input id="source-url" name="url" type="url" maxlength="2048" value="{{ old('url') }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the source') }}</button></div>
</form>

<h2>{{ __('Reviewers and their subjects') }}</h2>
<p>{{ __('Only a reviewer assigned to an item\'s subject can approve or reject it, and never a version they wrote or sent for review.') }}</p>
@if ($reviewers !== [])
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Reviewers') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Reviewer') }}</th><th scope="col">{{ __('Framework') }}</th><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Change') }}</th></tr></thead>
        <tbody>
        @foreach ($reviewers as $reviewer)
            <tr>
                <th scope="row">{{ $reviewer['name'] }}</th>
                <td>{{ $reviewer['framework'] }}</td>
                <td>{{ $reviewer['subject_code'] }}</td>
                <td>
                    <form class="saas-inline" method="POST" action="{{ route('saas.library.reviewers.destroy', $reviewer['id']) }}">
                        @csrf @method('DELETE')
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Remove') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@if ($frameworkChoices === [] || $reviewerChoices === [])
    <p class="saas-card">{{ __('To assign a reviewer, add a framework and give a platform staff member the Content reviewer role.') }}</p>
@else
<form method="POST" action="{{ route('saas.library.reviewers.store') }}" class="saas-card" aria-label="{{ __('Assign a reviewer') }}">
    @csrf
    <h3>{{ __('Assign a reviewer') }}</h3>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="reviewer-user">{{ __('Reviewer') }}</label>
            <select id="reviewer-user" name="user_id" required>
                @foreach ($reviewerChoices as $id => $name)<option value="{{ $id }}" @selected((string) old('user_id') === (string) $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="reviewer-framework">{{ __('Framework') }}</label>
            <select id="reviewer-framework" name="library_framework_id" required>
                @foreach ($frameworkChoices as $id => $code)<option value="{{ $id }}" @selected((string) old('library_framework_id') === (string) $id)>{{ $code }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="reviewer-subject">{{ __('Subject code') }}</label>
            <input id="reviewer-subject" name="subject_code" required maxlength="32" list="reviewer-subjects" value="{{ old('subject_code') }}">
            <datalist id="reviewer-subjects">
                @foreach ($subjectChoices as $choices)@foreach ($choices as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach @endforeach
            </datalist>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Assign') }}</button></div>
</form>
@endif

<h2>{{ __('Schools') }}</h2>
<p>{{ __('Off by default. When a school is on, a learning group with fewer than ten approved questions of its own is topped up with published library questions for its framework, subject and grade.') }}</p>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Schools') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('School') }}</th><th scope="col">{{ __('Library') }}</th><th scope="col">{{ __('Change') }}</th></tr></thead>
        <tbody>
        @foreach ($schools as $school)
            <tr>
                <th scope="row">{{ $school['name'] }}</th>
                <td><span class="saas-badge {{ $school['enabled'] ? '' : 'saas-badge--warn' }}">{{ $school['enabled'] ? __('On') : __('Off') }}</span></td>
                <td>
                    <form class="saas-inline" method="POST" action="{{ route('saas.library.schools.switch', $school['id']) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="enabled" value="{{ $school['enabled'] ? 0 : 1 }}">
                        <label for="switch-reason-{{ $school['id'] }}">{{ __('Reason') }}</label>
                        <input id="switch-reason-{{ $school['id'] }}" name="reason" required minlength="10" maxlength="500">
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ $school['enabled'] ? __('Switch off') : __('Switch on') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if ($lastPage > 1)
    <nav aria-label="{{ __('School pages') }}">
        @if ($page > 1)<a href="{{ route('saas.library.index', ['page' => $page - 1]) }}">{{ __('Previous') }}</a>@endif
        {{ __('Page :page of :last', ['page' => $page, 'last' => $lastPage]) }}
        @if ($page < $lastPage)<a href="{{ route('saas.library.index', ['page' => $page + 1]) }}">{{ __('Next') }}</a>@endif
    </nav>
@endif
@endsection
