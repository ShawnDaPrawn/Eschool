@extends('layouts.master')
@section('title', __('Practice reminders'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Practice reminders')" :subtitle="__('Gentle reminders when a learner has not practised for a few school days. Both start off. Weekends, holidays and school closures are never counted, nobody gets more than one reminder a week, and nothing is sent during a person’s quiet hours. Reminders never name a child, a score or a comparison.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <form method="POST" action="{{ route('practice-motivation.update') }}" class="m3-card m3-card--outlined page-section" aria-label="{{ __('Practice reminder settings') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="operation_id" value="{{ $operationId }}">
        <input type="hidden" name="revision" value="{{ $settings['revision'] }}">
        <div class="m3-card__content panel-form-grid">
        <x-page.field name="guardian_nudge_enabled" id="guardian-nudge-enabled" type="select" :label="__('Remind guardians')" :options="[0 => __('Off'), 1 => __('On')]" :value="(int) $settings['guardian_nudge_enabled']" required />
        <x-page.field name="learner_nudge_enabled" id="learner-nudge-enabled" type="select" :label="__('Remind learners')" :options="[0 => __('Off'), 1 => __('On')]" :value="(int) $settings['learner_nudge_enabled']" required />
        <x-page.field name="nudge_after_school_days" id="nudge-after-school-days" type="number" :label="__('School days without practice before a reminder')" :value="$settings['nudge_after_school_days']" :min="$minDays" :max="$maxDays" required />
        <p>{{ __('Reminders go out on school-day afternoons, only to learners who have practised before and who are in a stage with daily practice. Early years learners never get one.') }}</p>
        <button class="m3-button m3-button--filled" type="submit">{{ __('Save practice reminders') }}</button>
        </div>
    </form>
</div>
@endsection
