@extends('layouts.master')
@section('title', $ticket->reference())
@section('content')
@php
    $statusChips = ['open' => 'info', 'waiting_on_school' => 'warning', 'resolved' => 'success', 'closed' => 'neutral'];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$ticket->reference().': '.$ticket->subject" :subtitle="__('support_category_'.$ticket->category).' · '.__('support_priority_'.$ticket->priority)" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <p><x-m3.chip :status="$statusChips[$ticket->status] ?? 'neutral'">{{ __('support_status_'.$ticket->status) }}</x-m3.chip></p>

    <section class="page-section" aria-labelledby="support-thread">
        <h2 id="support-thread">{{ __('Messages') }}</h2>
        <ol class="support-thread">
            @foreach ($messages as $message)
                <li class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                    <p><strong>{{ $message['side'] === 'school' ? ($message['author'] ?? __('Someone at your school')) : __('Navigate Ed support') }}</strong>
                        · <time datetime="{{ $message['at']?->toIso8601String() }}">{{ $message['at']?->timezone('Africa/Johannesburg')->format('j M Y H:i') }}</time></p>
                    <p class="support-thread__body">{{ $message['body'] }}</p>
                </div></li>
            @endforeach
        </ol>
    </section>

    @if ($ticket->status === 'closed')
        <p>{{ __('This ticket is closed. Open a new ticket if you still need help.') }}</p>
    @else
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="support-reply"><div class="m3-card__content">
            <h2 id="support-reply">{{ __('Add a message') }}</h2>
            <p>{{ __('Do not paste learners\' personal information here: no names, ID numbers, contact details, marks or health details.') }}</p>
            <form method="POST" action="{{ route('support-tickets.messages.store', $ticket->id) }}" aria-label="{{ __('Add a message') }}">
                @csrf
                <x-page.field name="body" id="support-message" type="textarea" :label="__('Your message')" minlength="2" maxlength="5000" rows="4" required />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Send to Navigate Ed') }}</button>
            </form>
            <form method="POST" action="{{ route('support-tickets.close', $ticket->id) }}" aria-label="{{ __('Close the ticket') }}">
                @csrf
                <button class="m3-button m3-button--outlined" type="submit" data-confirm="{{ __('Close this ticket if you no longer need help with it. You cannot add to it once it is closed.') }}"
                    data-confirm-title="{{ __('Close the ticket') }}" data-confirm-label="{{ __('Close the ticket') }}">{{ __('Close the ticket') }}</button>
            </form>
        </div></section>
    @endif
    <p><a class="m3-button m3-button--text" href="{{ route('support-tickets.index') }}">{{ __('All your school\'s tickets') }}</a></p>
</div>
@endsection
