@extends('layouts.master')
@section('title', __('Events and admission tickets'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench ticket-workbench">
@if(isset($validationErrors))<div role="alert"><h2>{{ __('Review the highlighted information') }}</h2><ul>@foreach($validationErrors as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
<x-page.header :title="$event?->name??__('Events and admission tickets')" :subtitle="__('Keep admission event setup, ticket capacity and gate entry as separate steps.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($readOnly)<p role="status">{{ __('Ticketing is read-only. Keep original event, ticket and redemption history.') }}</p>@endif
@php
$tabs=['events'=>['label'=>__('Admission events'),'url'=>route('ticket-events.index')]];
if($event){$tabs['event-details']=['label'=>__('Event and admission types'),'url'=>route('ticket-events.index',['event_id'=>$event->id])];
if(!$readOnly && $event->status!=='cancelled'){$tabs['event']=['label'=>__('Edit sale and access windows'),'url'=>route('ticket-events.index',['event_id'=>$event->id,'section'=>'event'])];$tabs['type']=['label'=>__($type?'Edit admission type':'Configure admission type'),'url'=>route('ticket-events.index',['event_id'=>$event->id,'type_id'=>$type?->id,'section'=>'type'])];$tabs['cancel']=['label'=>__('Cancel admission event'),'url'=>route('ticket-events.index',['event_id'=>$event->id,'section'=>'cancel'])];}}
@endphp
@include('money.billing.tabs')
@if($section==='events')
@if(!$readOnly)<x-m3.button :href="route('ticket-events.index',['new_event'=>1])">{{ __('Prepare a new event') }}</x-m3.button>@endif
<div class="page-table__scroll" role="region" aria-label="{{ __('Retained school events') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Retained school events') }}</caption><thead><tr><th scope="col">{{ __('Event') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Revision') }}</th></tr></thead><tbody>@forelse($events as $item)<tr><td><a href="{{ route('ticket-events.index',['event_id'=>$item->id]) }}">{{ $item->name }}</a></td><td>{{ __($item->status) }}</td><td>{{ $item->revision }}</td></tr>@empty<tr><td colspan="3">{{ __('No admission events have been configured.') }}</td></tr>@endforelse</tbody></table></div>{{ $events->links() }}
@elseif($section==='event')
@include('money.tickets.event-form')
@elseif($section==='type')
<x-m3.button variant="text" :href="route('vendor-catalogues.index',['section'=>'product'])">{{ __('Prepare an uncounted admission product') }}</x-m3.button>
@include('money.tickets.type-form')
@elseif($section==='cancel')
@if($event->status==='cancelled')<p role="status">{{ __('This admission event is cancelled and cannot reopen.') }}</p>
@else
<form method="post" action="{{ route('ticket-events.event') }}" aria-label="{{ __('Cancel admission event') }}" data-year-safe autocomplete="off">
@include('money.fee-plans.hidden',['revision'=>$event->revision])
<input type="hidden" name="id" value="{{ $event->id }}"><input type="hidden" name="name" value="{{ $event->name }}"><input type="hidden" name="description" value="{{ $event->description }}"><input type="hidden" name="status" value="cancelled">
@foreach(['sales_from','sales_until','starts_at','ends_at'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $event->getAttribute($field)->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d\TH:i') }}">@endforeach
<fieldset @disabled($readOnly)><legend>{{ __('Cancel admission event') }}</legend><p>{{ __('Cancellation stops new admissions and cannot be reversed for this event. Original tickets, redemptions and money history remain. Refunds require separate Finance action.') }}</p>
<x-page.field name="private_reason" :value="$draft['private_reason']??''" type="textarea" :label="__('Private reason for this event revision')" minlength="10" maxlength="2000" :restore-old-input="false" required />
<x-m3.checkbox name="confirmed_cancellation" :label="__('I understand this event cannot reopen and refunds are separate.')" value="1" required />
<x-m3.button variant="text" :href="route('ticket-events.index',['event_id'=>$event->id])">{{ __('Keep event') }}</x-m3.button><x-m3.button type="submit">{{ __('Retain cancellation') }}</x-m3.button></fieldset></form>
@endif
@else
<section class="workspace-section"><h2>{{ $type?__('Retained admission type'):__('Admission event details') }}</h2>
<dl><dt>{{ __('Status') }}</dt><dd>{{ __($type?->status??$event->status) }}</dd><dt>{{ __('Revision') }}</dt><dd>{{ $type?->revision??$event->revision }}</dd>
@if($type)<dt>{{ __('Original admission product') }}</dt><dd>{{ $productOptions[$type->product_id] }}</dd><dt>{{ __('Admission kind') }}</dt><dd>{{ __($type->kind==='named'?'Named learner admission':'General admission') }}</dd><dt>{{ __('Issued tickets') }}</dt><dd>{{ $issued }}</dd><dt>{{ __('Explicit admission capacity') }}</dt><dd>{{ $type->capacity }}</dd><dt>{{ __('School ticket transfer rule') }}</dt><dd>{{ __(['none'=>'Transfers are disabled','families'=>'Current school families may receive','wallet_profiles'=>'Current wallet profiles may receive'][$type->transfer_rule]) }}</dd>
@else
@foreach(['sales_from'=>'Ticket sales start','sales_until'=>'Ticket sales end','starts_at'=>'Admission opens','ends_at'=>'Admission closes'] as $field=>$label)<dt>{{ __($label) }}</dt><dd>{{ $event->getAttribute($field)->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d H:i T') }}</dd>@endforeach
@endif</dl>
@if($event->status==='cancelled')<p role="status">{{ __('This admission event is cancelled and cannot reopen.') }}</p><p>{{ __('Original ticket and financial history remains. Cancellation does not refund money automatically.') }}</p>@endif
</section>
@if(!$type)
<h2>{{ __('Admission types and original capacity') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Admission types') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Admission types') }}</caption><thead><tr><th scope="col">{{ __('Admission type') }}</th><th scope="col">{{ __('Admission kind') }}</th><th scope="col">{{ __('Issued tickets') }}</th><th scope="col">{{ __('Capacity') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead><tbody>@forelse($types as $item)<tr><td><a href="{{ route('ticket-events.index',['event_id'=>$event->id,'type_id'=>$item->id]) }}">{{ __('Admission type') }} {{ $item->id }}</a></td><td>{{ __($item->kind==='named'?'Named learner admission':'General admission') }}</td><td>{{ $counts[$item->id]??0 }}</td><td>{{ $item->capacity }}</td><td>{{ __($item->status) }}</td></tr>@empty<tr><td colspan="5">{{ __('No admission types have been configured for this event.') }}</td></tr>@endforelse</tbody></table></div>{{ $types->withQueryString()->links() }}
<p>{{ __('Gate admission is performed by the authorised operator at the original vendor counter.') }}</p>
@endif
<section class="workspace-section"><h2>{{ __('Retained configuration revisions') }}</h2>@forelse($revisions as $revision)<article><h3>{{ __('Revision') }} {{ $revision->revision }}</h3><p>{{ $revision->created_at->toIso8601String() }}</p><p>{{ $revision->private_reason }}</p><dl>
@foreach(($type?['status'=>'Status','capacity'=>'Explicit admission capacity','transfer_rule'=>'School ticket transfer rule']:['name'=>'Event name','status'=>'Status','sales_from'=>'Ticket sales start','sales_until'=>'Ticket sales end','starts_at'=>'Admission opens','ends_at'=>'Admission closes']) as $field=>$label)
<dt>{{ __($label) }}</dt><dd>{{ $revision->snapshot[$field]??'—' }}</dd>
@endforeach
</dl></article>@empty<p>{{ __('No configuration revisions yet.') }}</p>@endforelse{{ $revisions->withQueryString()->links() }}</section>
@endif
</div>
@endsection
