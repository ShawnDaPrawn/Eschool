@extends('layouts.master')
@section('title', __('My push quiet hours'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('My push quiet hours')" :subtitle="__('Applies to your push notices across every school. Safety notices bypass quiet hours; email and in-app notices are unchanged.')" />
    @include('personal-notifications.nav')
    <x-page.form-card id="personal-quiet-hours" :action="route('panel.preferences.quiet.update')" :title="__('Quiet hours')" :submit-label="__('Save quiet hours')" :draft="false" :write="false">
        @include('personal-notifications.context')
        <div class="panel-form-grid">
            <x-page.field name="enabled" type="select" :label="__('Enable quiet hours')" :options="[0 => __('Off'), 1 => __('On')]" :value="(int) $quiet['enabled']" required />
            <x-page.field name="starts_at" type="time" :label="__('Start')" :value="$quiet['starts_at']" required />
            <x-page.field name="ends_at" type="time" :label="__('End')" :value="$quiet['ends_at']" required />
            <x-page.field name="timezone" type="select" :label="__('Time zone')" :options="$zones" :value="$quiet['timezone']" required />
        </div>
        <p>{{ __('Overnight ranges are supported. The start and end must be different.') }}</p>
    </x-page.form-card>
</div>
@endsection
