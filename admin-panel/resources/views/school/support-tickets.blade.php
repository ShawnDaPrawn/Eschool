@extends('layouts.master')
@section('title', __('Navigate Ed support'))
@section('content')
@php
    $statusChips = ['open' => 'info', 'waiting_on_school' => 'warning', 'resolved' => 'success', 'closed' => 'neutral'];
    $categories = collect(\App\Models\Platform\SupportTicket::CATEGORIES)->mapWithKeys(fn ($c) => [$c => __('support_category_'.$c)])->all();
    $priorities = collect(\App\Models\Platform\SupportTicket::PRIORITIES)->mapWithKeys(fn ($p) => [$p => __('support_priority_'.$p)])->all();
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Navigate Ed support')" :subtitle="__('Ask Navigate Ed for help with a question, a problem, a request or billing. Everyone at your school who looks after the settings sees these tickets and the replies.')"
        :primary-label="__('Open a ticket')" primary-href="#support-new" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="support-list">
        <h2 id="support-list">{{ __('Your school\'s tickets') }}</h2>
        @if ($tickets->isEmpty())
            <x-page.empty-state icon="inbox" :title="__('No tickets yet')" :message="__('When you open a ticket, it and Navigate Ed\'s replies appear here.')" />
        @else
            <div class="page-table__scroll" role="region" aria-label="{{ __('Your school\'s tickets') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Your school\'s tickets') }}</caption>
                <thead><tr><th scope="col">{{ __('Ticket') }}</th><th scope="col">{{ __('Ticket subject') }}</th><th scope="col">{{ __('Ticket status') }}</th><th scope="col">{{ __('Last change') }}</th></tr></thead>
                <tbody>
                @foreach ($tickets as $ticket)
                    <tr>
                        <td><a href="{{ route('support-tickets.show', $ticket->id) }}">{{ $ticket->reference() }}</a></td>
                        <th scope="row">{{ $ticket->subject }}@if ($ticket->priority === 'urgent') <x-m3.chip status="danger">{{ __('support_priority_urgent') }}</x-m3.chip>@endif</th>
                        <td><x-m3.chip :status="$statusChips[$ticket->status] ?? 'neutral'">{{ __('support_status_'.$ticket->status) }}</x-m3.chip></td>
                        <td>{{ $ticket->updated_at?->timezone('Africa/Johannesburg')->format('j M Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $tickets->links() }}
        @endif
    </section>

    <section class="m3-card m3-card--outlined page-section" id="support-new" aria-labelledby="support-new-title"><div class="m3-card__content">
        <h2 id="support-new-title">{{ __('Open a ticket') }}</h2>
        <p>{{ __('Do not paste learners\' personal information here: no names, ID numbers, contact details, marks or health details. Describe what you were doing, the page and what went wrong; Navigate Ed will ask if they need more.') }}</p>
        <form method="POST" action="{{ route('support-tickets.store') }}" aria-label="{{ __('Open a ticket') }}">
            @csrf
            <x-page.field name="subject" id="support-subject" :label="__('Ticket subject')" minlength="5" maxlength="150" required />
            <x-page.field name="category" id="support-category" type="select" :label="__('What is it about?')" :options="$categories" :placeholder="__('Choose one')" required />
            <x-page.field name="priority" id="support-priority" type="select" :label="__('How urgent is it?')" :options="$priorities" value="normal" :help="__('Urgent: something your school cannot work without today.')" required />
            <x-page.field name="body" id="support-body" type="textarea" :label="__('Your message')" minlength="10" maxlength="5000" rows="6" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Send to Navigate Ed') }}</button>
        </form>
    </div></section>
</div>
@endsection
