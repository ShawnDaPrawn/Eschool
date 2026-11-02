@extends('layouts.master')
@section('title', __('Wellbeing check-ins'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/progress-notes.css') }}">@endsection
@section('content')
@php
    $scaleNames = collect($scale)->map(fn ($label) => __($label))->all();
    $statusNames = ['scheduled' => __('Waiting for an answer'), 'answered' => __('Answered'), 'cancelled' => __('Cancelled')];
@endphp
<div class="content-wrapper progress-notes">
    <h1>{{ __('Wellbeing check-ins') }}</h1>
    <p>{{ __('Short private check-ins for learners on your caseload: one simple question, an answer from Not good to Great, and an optional note. Only the learner\'s authorised pastoral staff see the answers. A check-in is never scored, never shown to other learners and never used to rank anyone.') }}</p>
    <p>{{ __('Record the answer the learner gives you in person or on paper. Learners will answer in the app in a later step.') }}</p>
    @if ($errors->any())<div role="alert" class="m3 m3-banner m3-banner--warning"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if (session('success'))<p role="status" class="m3 m3-banner m3-banner--success">{{ session('success') }}</p>@endif

    <section class="note-card"><h2>{{ __('Schedule a check-in') }}</h2>
        @if ($learners === [])
            <p>{{ __('You have no learners on your risk caseload this year. A principal or grade head assigns caseloads in early warning.') }}</p>
        @else
            <form method="post" action="{{ route('wellbeing-check-ins.schedule') }}">@csrf
                <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                <fieldset class="m3-fieldset"><legend class="m3-fieldset__legend">{{ __('Learners (choose one, or several for a group, up to :max)', ['max' => $perGroup]) }}</legend>
                    <div class="m3-fieldset__options">@foreach ($learners as $person)<x-m3.checkbox name="learner_ids[]" :value="$person['learner_id']" :label="$person['name']" />@endforeach</div>
                </fieldset>
                <x-m3.select id="check-in-prompt" name="prompt" :label="__('Question')" required>@foreach ($prompts as $key => $prompt)<option value="{{ $key }}">{{ __($prompt) }}</option>@endforeach</x-m3.select>
                <x-m3.text-field id="check-in-due" name="due_on" type="date" :label="__('Day of the check-in')" :value="$today" :min="$today" required />
                <button type="submit" class="m3-button m3-button--filled" @disabled(! $canWrite)>{{ __('Schedule check-in') }}</button>
            </form>
        @endif
    </section>

    <section class="note-card"><h2>{{ __('Check-ins') }}</h2>
        @forelse ($check_ins as $item)
            <article class="note-list-item">
                <h3>{{ $item['learner_name'] }}</h3>
                <p>{{ __($prompts[$item['prompt']]) }} · {{ $item['due_on'] }} · {{ $statusNames[$item['status']] }}@if ($item['group']) · {{ __('Part of a group check-in') }}@endif</p>
                @if ($item['status'] === 'answered')
                    <p><strong>{{ __('Answer') }}</strong>: {{ $scaleNames[$item['scale']] ?? __('Unknown') }} · {{ $item['answer_source'] === 'learner' ? __('Answered by the learner') : __('Recorded by staff') }} · {{ $item['answered_on'] }}</p>
                    @if ($item['note'] !== null)<div class="note-body" aria-label="{{ __('Note') }}">{{ $item['note'] }}</div>@endif
                @elseif ($item['status'] === 'scheduled')
                    <form method="post" action="{{ route('wellbeing-check-ins.record', ['checkIn' => $item['id']]) }}">@csrf
                        <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                        <input type="hidden" name="expected_revision" value="{{ $item['revision'] }}">
                        <x-m3.radio-group name="scale" :legend="__('The learner\'s answer')" :options="$scaleNames" required />
                        <label for="check-in-note-{{ $item['id'] }}">{{ __('Note (optional)') }}</label>
                        <textarea id="check-in-note-{{ $item['id'] }}" name="note" rows="2" maxlength="500"></textarea>
                        <button type="submit" class="m3-button m3-button--filled" @disabled(! $canWrite)>{{ __('Save the learner\'s answer') }}</button>
                    </form>
                    <form method="post" action="{{ route('wellbeing-check-ins.cancel', ['checkIn' => $item['id']]) }}">@csrf
                        <input type="hidden" name="expected_account_id" value="{{ $actorId }}"><input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
                        <input type="hidden" name="expected_revision" value="{{ $item['revision'] }}">
                        <button type="submit" class="m3-button m3-button--outlined" @disabled(! $canWrite)>{{ __('Cancel this check-in') }}</button>
                    </form>
                @endif
            </article>
        @empty
            <p>{{ __('No check-ins for your learners this year yet.') }}</p>
        @endforelse
        @if ($last_page > 1)<nav class="note-pages" aria-label="{{ __('Check-in pages') }}">@if ($page > 1)<a href="{{ route('wellbeing-check-ins.index', ['page' => $page - 1]) }}">{{ __('Previous') }}</a>@endif @if ($page < $last_page)<a href="{{ route('wellbeing-check-ins.index', ['page' => $page + 1]) }}">{{ __('Next') }}</a>@endif</nav>@endif
    </section>
</div>
@endsection
