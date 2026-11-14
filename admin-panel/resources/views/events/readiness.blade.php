@extends('layouts.master')
@section('title', __('Event readiness'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$event->title" :subtitle="__('Event readiness')"><x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('events.index') }}">{{ __('All school events') }}</a></x-slot:actions></x-page.header>
    <x-panel-view-nav :current="$view" :items="[
        'overview'=>['label'=>'Readiness and consent roster','href'=>route('event-readiness.show',$event->id)],
        'links'=>['label'=>'Linked records and transport','href'=>route('event-readiness.show',['event'=>$event->id,'view'=>'links']),'visible'=>$canWrite],
    ]" />
    <p>{{ $event->start_date }} @if($event->end_date)– {{ $event->end_date }}@endif</p>
    <section class="m3-card m3-card--outlined panel-record-card">
        <h2>{{ __('Event details') }}</h2>
        @if($event->start_time)<p>{{ $event->start_time }} – {{ $event->end_time }} · {{ __('School time') }}</p>@endif
        @if($event->description)<p class="panel-preserve-lines">{{ $event->description }}</p>@endif
        @if($event->image)<img class="panel-record-image" src="{{ $event->image }}" alt="{{ __('School event image') }}" width="360" loading="lazy">@endif
        <div class="panel-actions">
            @if($event->type === 'multiple')<a class="m3-button m3-button--outlined" href="{{ route('view_schedule.index',$event->id) }}">{{ __('Open programme') }}</a>@endif
            @if($canWrite)<a class="m3-button m3-button--text" href="{{ route('events.index',['event_id'=>$event->id]) }}">{{ __('Edit event details') }}</a>@endif
        </div>
    </section>
    @if($view === 'links' && $canWrite)
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Find a consent request') }}</h2>
            <form method="GET" action="{{ route('event-readiness.show',$event->id) }}"><input type="hidden" name="view" value="links">
                <x-page.field name="activity_group_id" type="select" :label="__('Managed activity group')" :options="$choices['groups']" :value="$group" :placeholder="__('Choose a group')" />
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Find consent requests') }}</button>
            </form>
            @if($choices['consents']['next_page'])<a class="m3-button m3-button--text" href="{{ route('event-readiness.show',['event'=>$event->id,'view'=>'links','activity_group_id'=>$group,'choice_page'=>$choices['consents']['next_page']]) }}">{{ __('More consent requests') }}</a>@endif
        </section>
        @if($choices['canLinkTickets'])
            <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Find an admission event') }}</h2>
                <form method="GET" action="{{ route('event-readiness.show',$event->id) }}">
                    <input type="hidden" name="view" value="links">@if($group)<input type="hidden" name="activity_group_id" value="{{ $group }}">@endif
                    <x-page.field name="ticket_search" type="search" :label="__('Admission event name')" :value="$choices['ticketSearch']" :restore-old-input="false" maxlength="191" />
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Find admission events') }}</button>
                </form>
                <p>{{ __('The admission menu shows up to 25 matching events on this page and any currently linked event.') }}</p>
                <nav class="panel-actions" aria-label="{{ __('Admission choice pages') }}">
                    @if($choices['ticketPage']>1)<a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['ticket_page'=>$choices['ticketPage']-1]) }}">{{ __('Previous admission events') }}</a>@endif
                    @if($choices['ticketNextPage'])<a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['ticket_page'=>$choices['ticketNextPage']]) }}">{{ __('More admission events') }}</a>@endif
                </nav>
            </section>
        @endif
        @php
            $consentOptions = [''=>__('No linked consent request')] + collect($choices['consents']['items'])->mapWithKeys(fn($item)=>[$item['id']=>$item['title'].' · '.$item['status']])->all();
            if($consent) $consentOptions[$consent['id']]=$consent['title'].' · '.$consent['status'];
            $ticketOptions = [''=>__('No linked admission event')] + $choices['tickets'];
            if($ticket) $ticketOptions[$ticket['id']]=$ticket['name'];
        @endphp
        <x-page.form-card id="event-readiness-links" :action="route('event-readiness.save',$event->id)" :title="__('Link existing school records')" :submit-label="__('Save event links')" :draft="false">
            <input type="hidden" name="expected_account_id" value="{{ $account }}"><input type="hidden" name="expected_school_id" value="{{ $school }}"><input type="hidden" name="revision" value="{{ $link?->revision ?? 0 }}">
            <x-page.field name="consent_request_id" type="select" :label="__('Consent request')" :options="$consentOptions" :value="$link?->consent_request_id ?? ''" :restore-old-input="false" />
            @if($choices['canLinkTickets'])<x-page.field name="ticket_event_id" type="select" :label="__('Admission event')" :options="$ticketOptions" :value="$link?->ticket_event_id ?? ''" :restore-old-input="false" />@else<p>{{ __('Linking an admission event requires school ticket-management access.') }}</p>@endif
            <x-page.field name="transport_notes" type="textarea" :label="__('Office transport arrangements')" :value="$link?->transport_notes" :restore-old-input="false" maxlength="2000" rows="5" :help="__('Record the transport arrangement and office contact. Keep learner health information in its authorised source.')" />
            <p>{{ __('Links are explicit. Saving them does not send a consent request, book transport, sell a ticket, or change any guardian response.') }}</p>
            <x-slot:actions><a class="m3-button m3-button--text" href="{{ route('event-readiness.show',$event->id) }}">{{ __('Cancel') }}</a></x-slot:actions>
        </x-page.form-card>
    @else
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('Readiness summary') }}</h2>
            @if(!$link)<p>{{ __('No records have been linked to this event yet.') }}</p>@endif
            <dl class="panel-summary-grid">
                <div><dt>{{ __('Participants on the linked consent request') }}</dt><dd>{{ $participants ?? __('No consent request linked') }}</dd></div>
                <div><dt>{{ __('Effective consent on this page') }}</dt><dd>{{ $ready ?? '—' }}</dd></div>
                <div><dt>{{ __('Not cleared on this page') }}</dt><dd>{{ $consent !== null ? count($consent['recipients'])-$ready : '—' }}</dd></div>
            </dl>
            <p>{{ __('The participant total covers the linked request. Consent readiness counts cover only the recipients on this page; review every roster page before proceeding. A past yes response may no longer provide consent. No additional participants are inferred.') }}</p>
            <h3>{{ __('Transport') }}</h3><p class="panel-preserve-lines">{{ $link?->transport_notes ?: __('No transport arrangements recorded.') }}</p>
            <h3>{{ __('Ticketing') }}</h3>
            @if($ticket)<p>{{ $ticket['name'] }} · {{ __(ucfirst($ticket['status'])) }} · {{ __(':count tickets issued', ['count'=>$ticket['issued']]) }}</p><p>{{ $ticket['starts_at'] }} – {{ $ticket['ends_at'] }} · {{ __('School time') }}</p><a class="m3-button m3-button--outlined" href="{{ route('ticket-events.index',['event_id'=>$ticket['id']]) }}">{{ __('Open linked admission event') }}</a>@else<p>{{ __('No admission event linked.') }}</p>@endif
        </section>
        @if($consent)
            <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ $consent['title'] }}</h2><p>{{ __(ucfirst($consent['status'])) }} · {{ __('Reply by') }} {{ $consent['respond_by'] }}</p>
                <a class="m3-button m3-button--outlined" href="{{ route('family-forms-panel.managed',['consent'=>$consent['id']]) }}">{{ __('Open linked consent request') }}</a>
                <div class="panel-table-scroll"><table class="page-table"><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Recorded decision') }}</th><th scope="col">{{ __('Current effective consent') }}</th></tr></thead><tbody>
                @foreach($consent['recipients'] as $recipient)<tr><th scope="row">{{ $recipient['name'] }}</th><td>{{ $recipient['decision'] === null ? __('Awaiting response') : __($recipient['decision'] === 'yes' ? 'Yes' : 'No') }}</td><td>{{ $recipient['effective'] ? __('Ready') : __('Not cleared') }}</td></tr>@endforeach
                </tbody></table></div>
                <nav class="panel-actions" aria-label="{{ __('Consent roster pages') }}">@if($page>1)<a class="m3-button m3-button--outlined" href="{{ route('event-readiness.show',['event'=>$event->id,'page'=>$page-1]) }}">{{ __('Previous') }}</a>@endif @if($consent['next_page'])<a class="m3-button m3-button--outlined" href="{{ route('event-readiness.show',['event'=>$event->id,'page'=>$consent['next_page']]) }}">{{ __('Next') }}</a>@endif</nav>
            </section>
        @endif
    @endif
</div>
@endsection
