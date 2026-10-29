@extends('vendor.layout')
@section('title', __('Event admission counter'))
@section('content')
<h1>{{ __('Event admission counter') }}</h1>
<p><a href="{{ route('vendor.index',['vendor_id'=>$vendor]) }}">{{ __('Return to this counter order queue') }}</a></p>
<form method="post" action="{{ route('vendor.tickets.lookup',['vendor'=>$vendor]) }}" aria-label="{{ __('Review admission before entry') }}" autocomplete="off">@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}">
<fieldset><legend>{{ __('Scan or type an admission ticket') }}</legend>
<x-page.field id="admission-lookup-number" :restore-old-input="false" name="ticket_number" :label="__('Current ticket number')" maxlength="23" autocomplete="off" />
<x-page.field id="admission-lookup-qr" :restore-old-input="false" name="qr_token" :label="__('Scanned admission QR')" maxlength="82" autocomplete="off" data-vendor-qr data-vendor-qr-purpose="ticket" />
<button type="button" class="m3-button m3-button--outlined" data-vendor-camera disabled>{{ __('Scan admission QR with camera') }}</button>
<video data-vendor-video hidden autoplay playsinline muted aria-label="{{ __('Admission QR camera preview') }}"></video>
<button type="button" class="m3-button m3-button--text" data-vendor-stop hidden>{{ __('Stop camera') }}</button>
<p role="status" data-vendor-camera-status data-unavailable="{{ __('Camera scanning is unavailable here. Type the current ticket number or paste its QR code.') }}" data-ready="{{ __('Admission QR ready. Review this original ticket before confirming entry.') }}" data-stopped="{{ __('Camera scanning stopped. Type the current ticket number or paste its QR code.') }}"></p>
<button type="submit" class="m3-button m3-button--filled">{{ __('Review admission before entry') }}</button></fieldset></form>
@if($ticket)
<section><h2>{{ $ticket['event_name'] }}</h2><p>{{ $ticket['product_name'] }}</p>
@if($ticket['named_learner_name'])<p>{{ __('Original named learner') }}: {{ $ticket['named_learner_name'] }}</p>@endif
@if($outcome)<p role="status">{{ $outcome['replayed']?__('Admission was already recorded. Check the original record; do not admit this ticket again.'):__('One admission recorded. Admit this ticket once.') }}</p>@endif
@if($ticket['status']==='redeemed')<p>{{ __('Original admission recorded at') }} {{ $ticket['redeemed_at'] }} · {{ __('Operator') }} {{ $ticket['redeemed_by'] }}</p>
@elseif($ticket['can_redeem'])
<form method="post" action="{{ route('vendor.tickets.redeem',['vendor'=>$vendor]) }}" aria-label="{{ __('Confirm one actual admission') }}" autocomplete="off">@csrf
<input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $ticket['revision'] }}">
@foreach($credentials as $key => $value)@if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
@endforeach
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I verified this original admission and will admit it once.') }}</label>
<button type="submit" class="m3-button m3-button--filled">{{ __('Confirm one actual admission') }}</button></form>
@else<p role="alert">{{ __('This admission is unavailable under the current event window, holder or school rules. Keep its original record.') }}</p>@endif
</section>
@endif
@endsection
