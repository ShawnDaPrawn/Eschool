@extends('saas.layout')

@section('title', __('Audit log'))

@section('content')
<h1>{{ __('Audit log') }}</h1>
<p class="saas-lead">{{ __('Who at Navigate Ed did what to which school, when and why. Only platform actions are listed: a school\'s own history stays in that school.') }}</p>

<form method="GET" action="{{ route('saas.audit') }}" class="saas-card" aria-label="{{ __('Filter the audit log') }}">
    <div class="saas-grid">
        <div class="saas-field">
            <label for="audit-school">{{ __('School') }}</label>
            <select id="audit-school" name="school">
                <option value="">{{ __('All schools') }}</option>
                @foreach ($choices['schools'] as $id => $name)
                    <option value="{{ $id }}" @selected($filters['school'] === (int) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="audit-action">{{ __('Action') }}</label>
            <select id="audit-action" name="action">
                <option value="">{{ __('All actions') }}</option>
                @foreach ($choices['actions'] as $action => $label)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="audit-actor">{{ __('Staff member') }}</label>
            <select id="audit-actor" name="actor">
                <option value="">{{ __('Anyone') }}</option>
                @foreach ($choices['actors'] as $id => $name)
                    <option value="{{ $id }}" @selected($filters['actor'] === (int) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="audit-from">{{ __('From') }}</label>
            <input id="audit-from" name="from" type="date" value="{{ $filters['from'] }}">
        </div>
        <div class="saas-field">
            <label for="audit-to">{{ __('To') }}</label>
            <input id="audit-to" name="to" type="date" value="{{ $filters['to'] }}">
        </div>
    </div>
    <div class="saas-actions">
        <button class="saas-btn" type="submit">{{ __('Filter') }}</button>
        @if (app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'audit.export'))
            <a class="saas-btn saas-btn--quiet" href="{{ route('saas.audit.export', array_filter($filters, fn ($value) => $value !== null)) }}">{{ __('Export CSV') }}</a>
        @endif
        <a class="saas-btn saas-btn--quiet" href="{{ route('saas.audit') }}">{{ __('Clear filters') }}</a>
    </div>
</form>

<h2>{{ __('Entries: :count', ['count' => $rows->total()]) }}</h2>
<div class="saas-card">
    @if ($rows->isEmpty())
        <p>{{ __('Nothing matches these filters.') }}</p>
    @else
        <table class="saas-table saas-audit">
            <caption>{{ __('Platform actions, newest first, in South African time') }}</caption>
            <thead>
            <tr>
                <th scope="col">{{ __('When') }}</th>
                <th scope="col">{{ __('Who') }}</th>
                <th scope="col">{{ __('School') }}</th>
                <th scope="col">{{ __('Action') }}</th>
                <th scope="col">{{ __('Details') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td><time datetime="{{ $row['at']->toIso8601String() }}">{{ $row['at']->format('Y-m-d H:i') }}</time></td>
                    <td>{{ $row['actor'] }}@if ($row['actor_email'])<br><small>{{ $row['actor_email'] }}</small>@endif</td>
                    <td>
                        @if ($row['school_id'] !== null)
                            <a href="{{ route('saas.audit', ['school' => $row['school_id']]) }}">{{ $row['school'] }}</a>
                        @else
                            {{ $row['school'] }}
                        @endif
                    </td>
                    <td>{{ $row['label'] }}@if ($row['subject'])<br><small>{{ $row['subject'] }}</small>@endif</td>
                    <td>
                        @if ($row['reason'])
                            <p class="saas-audit-reason"><strong>{{ __('Reason') }}:</strong> {{ $row['reason'] }}</p>
                        @endif
                        @if ($row['changes'] !== [])
                            <ul class="saas-audit-changes">@foreach ($row['changes'] as $change)<li>{{ $change }}</li>@endforeach</ul>
                        @endif
                        @if ($row['ip'])
                            <small>{{ __('From :ip', ['ip' => $row['ip']]) }}</small>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

@if ($rows->lastPage() > 1)
    <nav class="saas-actions" aria-label="{{ __('Audit log pages') }}">
        @if ($rows->previousPageUrl())
            <a class="saas-btn saas-btn--quiet" href="{{ $rows->previousPageUrl() }}" rel="prev">{{ __('Newer') }}</a>
        @endif
        <span>{{ __('Page :page of :pages', ['page' => $rows->currentPage(), 'pages' => $rows->lastPage()]) }}</span>
        @if ($rows->nextPageUrl())
            <a class="saas-btn saas-btn--quiet" href="{{ $rows->nextPageUrl() }}" rel="next">{{ __('Older') }}</a>
        @endif
    </nav>
@endif
@endsection
