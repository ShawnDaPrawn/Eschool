@extends('layouts.master')
@section('title', __('My SMS and WhatsApp preferences'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('My SMS and WhatsApp preferences')" :subtitle="__('Only your recorded number is used. Availability depends on the school, your role and verification.')" />
    @include('personal-notifications.nav')
    <section class="panel-record-card">
        <h2 class="page-card-title">{{ __('Recorded mobile number') }}</h2>
        <p>{{ $number['on_record'] ? __('Number ending in').' '.$number['ending'] : __('No eligible number on record') }}</p>
        <p>{{ $number['verified'] ? __('Verified') : __('Not verified') }}</p>
        @if($number['locked_until'])<p role="status">{{ __('Try again after') }} {{ $number['locked_until'] }}</p>@endif
        @if($number['next_code_at'])<p role="status">{{ __('A new code is available after') }} {{ $number['next_code_at'] }}</p>@endif
        @if($number['can_verify'] && !$number['verified'] && !$number['locked_until'] && !$number['next_code_at'])
            <x-page.form-card id="request-phone-code" :action="route('panel.preferences.texts.code')" :draft="false" :write="false" :redirect="route('panel.preferences.texts')" :submit-label="__('Send verification code')">
                @include('personal-notifications.context')
            </x-page.form-card>
        @endif
        @if($number['code_sent'] && !$number['locked_until'])
            <x-page.form-card id="confirm-phone-code" :action="route('panel.preferences.texts.confirm')" :draft="false" :write="false" :redirect="route('panel.preferences.texts')" :submit-label="__('Verify number')">
                @include('personal-notifications.context')
                <x-page.field name="code" :label="__('Six-digit verification code')" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required :restore-old-input="false" />
                <p>{{ __('Code expires at') }} {{ $number['code_expires_at'] }}</p>
            </x-page.form-card>
        @endif
    </section>
    @forelse($notices as $notice)
        <x-page.form-card :id="'text-choice-'.str_replace('.', '-', $notice['type'])" :action="route('panel.preferences.texts.update')" :title="$notice['label']" :draft="false" :write="false" :submit-label="__('Save this choice')">
            @include('personal-notifications.context')
            <input type="hidden" name="type" value="{{ $notice['type'] }}">
            <div class="panel-form-grid">
                @foreach(['sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $channel => $label)
                    @if(in_array($channel, $notice['offered'], true))
                        <x-page.field :id="'text-'.$channel.'-'.str_replace('.', '-', $notice['type'])" :name="$channel" type="select" :label="$label" :options="[0 => __('Off'), 1 => __('On')]" :value="(int) $notice[$channel]" required />
                    @else
                        <input type="hidden" name="{{ $channel }}" value="0"><p>{{ $label }}: {{ __('Not offered by this school') }}</p>
                    @endif
                @endforeach
            </div>
        </x-page.form-card>
    @empty
        <x-page.empty-state :title="__('No text channels offered')" :message="__('There are no SMS or WhatsApp notice choices available to your account at this school.')" />
    @endforelse
</div>
@endsection
