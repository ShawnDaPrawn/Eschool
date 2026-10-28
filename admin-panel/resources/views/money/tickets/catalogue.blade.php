@extends('layouts.master')
@section('title', __('Events and admission tickets'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench ticket-workbench">
<x-page.header :title="__('Events and admission tickets')" :subtitle="__('Configure explicit sale windows, admission capacity and transfer rules. Digital tickets use the original wallet storefront and vendor settlement.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($readOnly)<p role="status">{{ __('Ticketing is read-only. Keep original event, ticket and redemption history.') }}</p>@endif
<p><a href="{{ route('ticket-events.index',['new_event'=>1]) }}">{{ __('Prepare a new event') }}</a> · <a href="{{ route('vendor-catalogues.index') }}">{{ __('Prepare an uncounted admission product') }}</a></p>
<h2>{{ __('Retained school events') }}</h2>
<ul>@forelse($events as $item)<li><a href="{{ route('ticket-events.index',['event_id'=>$item->id]) }}">{{ $item->name }}</a> · {{ $item->status }} · {{ __('Revision') }} {{ $item->revision }}</li>@empty<li>{{ __('No admission events have been configured.') }}</li>@endforelse</ul>
{{ $events->links() }}
<form method="post" action="{{ route('ticket-events.event') }}" aria-label="{{ __('Retain event sale and admission windows') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>$event?->revision??0])
@if($event)<input type="hidden" name="id" value="{{ $event->id }}">@endif
<fieldset @disabled($readOnly || $event?->status==='cancelled')><legend>{{ __('Event sale and admission windows') }}</legend>
<x-page.field id="ticket-event-name" :restore-old-input="false" name="name" :label="__('Event name')" :value="$event?->name" maxlength="191" required />
<x-page.field id="ticket-event-description" :restore-old-input="false" type="textarea" name="description" :label="__('Event description')" :value="$event?->description" maxlength="4000" />
<x-page.field id="ticket-event-status" :restore-old-input="false" type="select" name="status" :label="__('Event status')" :options="['draft'=>__('Draft'),'active'=>__('Active'),'paused'=>__('Paused'),'cancelled'=>__('Cancelled')]" :value="$event?->status??'draft'" required />
@foreach(['sales_from'=>'Ticket sales start','sales_until'=>'Ticket sales end','starts_at'=>'Admission opens','ends_at'=>'Admission closes'] as $field => $label)
<x-page.field :id="'ticket-event-'.$field" :restore-old-input="false" type="datetime-local" :name="$field" :label="__($label)" :value="$event?->getAttribute($field)?->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d\TH:i')" required />
@endforeach
<p>{{ __('Enter event windows in the selected school timezone.') }}</p>
<x-page.field id="ticket-event-reason" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private reason for this event revision')" minlength="10" maxlength="2000" required />
<x-m3.button type="submit">{{ __('Retain event sale and admission windows') }}</x-m3.button></fieldset></form>
@if($event)
<h2>{{ __('Admission types and original capacity') }}</h2>
<ul>@forelse($types as $item)<li><a href="{{ route('ticket-events.index',['event_id'=>$event->id,'type_id'=>$item->id]) }}">{{ __('Admission type') }} {{ $item->id }}</a> · {{ $item->kind }} · {{ __('Issued') }} {{ $counts[$item->id]??0 }} / {{ $item->capacity }} · {{ $item->transfer_rule }}</li>@empty<li>{{ __('No admission types have been configured for this event.') }}</li>@endforelse</ul>
{{ $types->appends(['event_id'=>$event->id])->links() }}
<p><a href="{{ route('ticket-events.index',['event_id'=>$event->id]) }}">{{ __('Prepare another admission type') }}</a></p>
<form method="post" action="{{ route('ticket-events.type') }}" aria-label="{{ __('Retain admission capacity and transfer rule') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>$type?->revision??0])
<input type="hidden" name="event_id" value="{{ $event->id }}">@if($type)<input type="hidden" name="id" value="{{ $type->id }}">@endif
<fieldset @disabled($readOnly || $event->status==='cancelled')><legend>{{ __('Admission capacity and transfer rule') }}</legend>
<x-page.field id="ticket-type-product" :restore-old-input="false" type="select" name="product_id" :label="__('Original admission product')" :options="$productOptions" :value="$type?->product_id" required />
<x-page.field id="ticket-type-kind" :restore-old-input="false" type="select" name="kind" :label="__('Admission kind')" :options="[''=>__('Choose an explicit admission kind'),'general'=>__('General admission'),'named'=>__('Named learner admission')]" :value="$type?->kind??''" required />
<x-page.field id="ticket-type-capacity" :restore-old-input="false" type="number" name="capacity" :label="__('Explicit admission capacity')" :value="$type?->capacity" min="1" max="1000000" required />
<x-page.field id="ticket-type-transfer" :restore-old-input="false" type="select" name="transfer_rule" :label="__('School ticket transfer rule')" :options="[''=>__('Choose an explicit transfer rule'),'none'=>__('Transfers are disabled'),'families'=>__('Current school families may receive'),'wallet_profiles'=>__('Current wallet profiles may receive')]" :value="$type?->transfer_rule??''" required />
<x-page.field id="ticket-type-status" :restore-old-input="false" type="select" name="status" :label="__('Admission type status')" :options="['draft'=>__('Draft'),'active'=>__('Active'),'paused'=>__('Paused')]" :value="$type?->status??'draft'" required />
<x-page.field id="ticket-type-reason" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private reason for this admission revision')" minlength="10" maxlength="2000" required />
<p>{{ __('Named tickets keep their original learner. A receiving parent must currently be authorised for that learner. Capacity cannot fall below issued tickets.') }}</p>
<x-m3.button type="submit">{{ __('Retain admission capacity and transfer rule') }}</x-m3.button></fieldset></form>
@endif
</div>
@endsection
