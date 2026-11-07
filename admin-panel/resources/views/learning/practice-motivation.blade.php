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
        <label for="guardian-nudge-enabled">{{ __('Remind guardians') }}
            <select id="guardian-nudge-enabled" name="guardian_nudge_enabled" required>
                <option value="0" @selected(! $settings['guardian_nudge_enabled'])>{{ __('Off') }}</option>
                <option value="1" @selected($settings['guardian_nudge_enabled'])>{{ __('On') }}</option>
            </select>
        </label>
        <label for="learner-nudge-enabled">{{ __('Remind learners') }}
            <select id="learner-nudge-enabled" name="learner_nudge_enabled" required>
                <option value="0" @selected(! $settings['learner_nudge_enabled'])>{{ __('Off') }}</option>
                <option value="1" @selected($settings['learner_nudge_enabled'])>{{ __('On') }}</option>
            </select>
        </label>
        <label for="nudge-after-school-days">{{ __('School days without practice before a reminder') }}
            <input id="nudge-after-school-days" type="number" name="nudge_after_school_days" min="{{ $minDays }}" max="{{ $maxDays }}" required value="{{ $settings['nudge_after_school_days'] }}">
        </label>
        <p>{{ __('Reminders go out on school-day afternoons, only to learners who have practised before and who are in a stage with daily practice. Early years learners never get one.') }}</p>
        <button class="m3-button m3-button--filled" type="submit">{{ __('Save practice reminders') }}</button>
    </form>
</div>
@endsection
