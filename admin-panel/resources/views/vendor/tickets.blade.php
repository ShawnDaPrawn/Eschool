@extends('vendor.layout')
@section('title', __('Event admission counter'))
@section('content')
<div data-ticket-admission-counter data-school="{{ $profile['school_id'] }}" data-actor="{{ $profile['user_id'] }}" data-vendor="{{ $vendor }}" data-index="{{ route('vendor.tickets.index',['vendor'=>$vendor]) }}">
<p role="status" data-ticket-recovery-status data-storage-error="{{ __('This browser cannot retain the original admission request. Use another browser before admitting a ticket.') }}"></p>
<form method="post" action="{{ route('vendor.tickets.resolve',['vendor'=>$vendor]) }}" data-ticket-recover hidden autocomplete="off">@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="">
<h2>{{ __('Admission outcome unconfirmed') }}</h2><p>{{ __('Check the original request before admitting anyone or scanning another ticket. This check will not admit a second person.') }}</p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Check original admission outcome') }}</button></form>
@if($outcome)<div data-ticket-outcome="{{ $operation }}">
@if($outcome['abandoned']??false)<p role="status">{{ __('The original request did not record admission and has been closed. Look up the ticket again before a new confirmation.') }}</p>@endif
</div>@endif
@if($failure)<p role="alert">{{ $failure }}</p>@endif
<h1>{{ __('Event admission counter') }}</h1>
<p><a href="{{ route('vendor.index',['vendor_id'=>$vendor]) }}">{{ __('Return to this counter order queue') }}</a></p>
@if(!$ticket)
<form method="post" action="{{ route('vendor.tickets.lookup',['vendor'=>$vendor]) }}" aria-label="{{ __('Review admission before entry') }}" autocomplete="off" data-ticket-new>@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}">
<fieldset><legend>{{ __('Scan or type an admission ticket') }}</legend>
<p>{{ __('Use either the current ticket number or the scanned QR. Looking up a ticket does not admit its holder.') }}</p>
<x-page.field id="admission-lookup-number" :restore-old-input="false" name="ticket_number" :label="__('Current ticket number')" maxlength="23" autocomplete="off" />
<x-page.field id="admission-lookup-qr" :restore-old-input="false" name="qr_token" :label="__('Scanned admission QR')" maxlength="82" autocomplete="off" data-vendor-qr data-vendor-qr-purpose="ticket" />
<button type="button" class="m3-button m3-button--outlined" data-vendor-camera disabled>{{ __('Scan admission QR with camera') }}</button>
<video data-vendor-video hidden autoplay playsinline muted aria-label="{{ __('Admission QR camera preview') }}"></video>
<button type="button" class="m3-button m3-button--text" data-vendor-stop hidden>{{ __('Stop camera') }}</button>
<p role="status" data-vendor-camera-status data-unavailable="{{ __('Camera scanning is unavailable here. Type the current ticket number or paste its QR code.') }}" data-ready="{{ __('Admission QR ready. Review this original ticket before confirming entry.') }}" data-stopped="{{ __('Camera scanning stopped. Type the current ticket number or paste its QR code.') }}"></p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Review admission before entry') }}</button></fieldset></form>
@endif
@if($ticket)
<section>@if($outcome && !($outcome['abandoned']??false))<p role="status" class="vendor-ticket-outcome">{{ $outcome['replayed']?__('Admission was already recorded. Check the original record; do not admit this ticket again.'):__('One admission recorded. Admit this ticket once.') }}</p>@endif
<h2>{{ $ticket['event_name'] }}</h2><p>{{ $ticket['product_name'] }}</p>
<dl><dt>{{ __('Admission state') }}</dt><dd>{{ __($ticket['status']==='redeemed'?'Admitted':($ticket['status']==='valid'?'Valid and unused':$ticket['status'])) }}</dd><dt>{{ __('Admission opens') }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($ticket['starts_at'])->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d H:i T') }}</dd><dt>{{ __('Admission closes') }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($ticket['ends_at'])->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d H:i T') }}</dd></dl>
@if($ticket['named_learner_name'])<p>{{ __('Original named learner') }}: {{ $ticket['named_learner_name'] }}</p>@endif

@if($ticket['status']==='redeemed')<p>{{ __('Original admission recorded at') }} {{ \Illuminate\Support\Carbon::parse($ticket['redeemed_at'])->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone??'UTC')->format('Y-m-d H:i T') }} · {{ __('Operator') }} {{ $ticket['redeemed_by'] }}</p>
@elseif($ticket['can_redeem'])
<form method="post" action="{{ route('vendor.tickets.redeem',['vendor'=>$vendor]) }}" aria-label="{{ __('Confirm one actual admission') }}" autocomplete="off" data-ticket-confirm>@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $ticket['revision'] }}">
@foreach($credentials as $key => $value)@if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
@endforeach
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I verified this original admission and will admit it once.') }}</label>
<button type="submit" class="m3-button m3-button--filled">{{ __('Confirm one actual admission') }}</button></form>
@else
@php($refusals=['ticket_not_valid'=>__('This ticket is no longer valid. Review its retained state.'),'event_cancelled'=>__('This admission event is cancelled. No admission override is available.'),'event_not_active'=>__('This admission event is not active.'),'type_not_active'=>__('This admission type is not active.'),'outside_window'=>__('The admission window is not open.'),'holder_not_active'=>__('The current holder or named learner is no longer eligible for admission.'),'module_paused'=>__('Admission is paused by the school. The original record is retained.')])
<p role="alert">{{ $refusals[$ticket['unavailable_reason']??'']??__('This admission is unavailable under the current event window, holder or school rules. Keep its original record.') }}</p>
@endif
</section>
@endif
<p><a href="{{ route('vendor.tickets.index',['vendor'=>$vendor]) }}">{{ __('Look up another ticket') }}</a></p>
</div>
<script src="{{ asset('assets/js/vendor-ticket-admission.js') }}?v={{ filemtime(public_path('assets/js/vendor-ticket-admission.js')) }}" defer></script>
@endsection
