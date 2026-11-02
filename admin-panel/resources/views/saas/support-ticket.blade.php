@extends('saas.layout')

@section('title', $ticket['reference'])

@use('App\Models\Platform\SupportTicket')

@section('content')
<h1>{{ $ticket['reference'] }}: {{ $ticket['subject'] }}</h1>
<p class="saas-lead">
    <a href="{{ route('saas.schools.show', $ticket['school_id']) }}">{{ $ticket['school'] }}</a>
    · {{ SupportTicket::CATEGORY_LABELS[$ticket['category']] }}
    · <span class="saas-badge {{ $ticket['priority'] === 'urgent' ? 'saas-badge--warn' : '' }}">{{ SupportTicket::PRIORITY_LABELS[$ticket['priority']] }}</span>
    · {{ SupportTicket::STATUS_LABELS[$ticket['status']] }}
    · {{ __('Opened :time by :name', ['time' => $ticket['opened_at'], 'name' => $ticket['opened_by'] ?? __('someone no longer at the school')]) }}
    @if ($ticket['closed_at']) · {{ __('Closed :time by :name', ['time' => $ticket['closed_at'], 'name' => $ticket['closed_by'] ?? __('someone')]) }}@endif
</p>

<h2 id="thread">{{ __('Thread') }}</h2>
<ol class="saas-thread">
    @foreach ($messages as $message)
        <li class="saas-card saas-thread__item {{ $message['internal'] ? 'saas-thread__item--note' : ($message['side'] === 'hq' ? 'saas-thread__item--hq' : '') }}">
            <p class="saas-thread__meta">
                <strong>{{ $message['author'] ?? __('Unknown') }}</strong>
                · {{ $message['side'] === 'school' ? $ticket['school'] : __('Navigate Ed') }}
                @if ($message['internal']) · <span class="saas-badge saas-badge--warn">{{ __('Internal note: the school never sees this') }}</span>@endif
                · <time>{{ $message['at'] }}</time>
            </p>
            <p class="saas-thread__body">{{ $message['body'] }}</p>
        </li>
    @endforeach
</ol>

<h2>{{ __('Reply or add a note') }}</h2>
<form method="POST" action="{{ route('saas.support.messages.store', $ticket['id']) }}" class="saas-card" aria-label="{{ __('Reply or add a note') }}">
    @csrf
    <div class="saas-field">
        <label for="support-kind">{{ __('Kind') }}</label>
        <select id="support-kind" name="kind" required>
            <option value="reply" @selected(old('kind', 'reply') === 'reply') @disabled($ticket['status'] === 'closed')>{{ __('Reply to the school (the person who opened it is told)') }}</option>
            <option value="note" @selected(old('kind') === 'note' || $ticket['status'] === 'closed')>{{ __('Internal note (Navigate Ed only)') }}</option>
        </select>
    </div>
    <div class="saas-field">
        <label for="support-body">{{ __('Message') }}</label>
        <textarea id="support-body" name="body" required minlength="2" maxlength="5000" rows="5">{{ old('body') }}</textarea>
        <small>{{ __('Never ask the school for a learner\'s personal information here.') }}</small>
    </div>
    @if ($ticket['status'] === 'closed')<p>{{ __('This ticket is closed: reopen it to reply to the school.') }}</p>@endif
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Send') }}</button></div>
</form>

<h2>{{ __('Status and assignment') }}</h2>
<div class="saas-grid">
    <form method="POST" action="{{ route('saas.support.status', $ticket['id']) }}" class="saas-card" aria-label="{{ __('Status') }}">
        @csrf
        @method('PUT')
        <div class="saas-field">
            <label for="support-status">{{ __('Status') }}</label>
            <select id="support-status" name="status" required>
                @foreach (SupportTicket::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected($ticket['status'] === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save status') }}</button></div>
    </form>
    <form method="POST" action="{{ route('saas.support.assignee', $ticket['id']) }}" class="saas-card" aria-label="{{ __('Assigned to') }}">
        @csrf
        @method('PUT')
        <div class="saas-field">
            <label for="support-assignee">{{ __('Assigned to') }}</label>
            <select id="support-assignee" name="assigned_to">
                <option value="">{{ __('Nobody') }}</option>
                @foreach ($staff as $id => $name)<option value="{{ $id }}" @selected($ticket['assigned_to'] === $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="saas-actions">
            <button class="saas-btn" type="submit">{{ __('Save assignment') }}</button>
            @if ($ticket['assigned_to'] !== auth()->id() && array_key_exists((int) auth()->id(), $staff))
                <button class="saas-btn saas-btn--quiet" type="submit" name="assigned_to" value="{{ auth()->id() }}">{{ __('Assign to me') }}</button>
            @endif
        </div>
    </form>
</div>
<p class="saas-actions"><a class="saas-btn saas-btn--quiet" href="{{ route('saas.support.index') }}">{{ __('Back to the queue') }}</a></p>
@endsection
