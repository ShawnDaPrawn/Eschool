@extends('layouts.master')
@section('title', __('Exam periods'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Exam periods')" :subtitle="__('The days each grade writes exams. On these days learners and families see an exam day in the timetable, and normal lessons are left out unless you keep them.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach (['name', 'grade_level_id', 'starts_on', 'ends_on', 'session_year_id'] as $key)@error($key)<p role="alert">{{ $message }}</p>@enderror @endforeach

    <form method="GET" action="{{ route('exam-periods.index') }}" aria-label="{{ __('Choose a school year') }}" class="page-section">
        <x-page.field name="session_year_id" id="exam-period-year" type="select" :label="__('School year')" :value="$year->id" :options="$years" :restore-old-input="false" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Show') }}</button>
    </form>

    <section class="page-section" aria-labelledby="exam-period-list">
        <h2 id="exam-period-list">{{ __('Exam periods in :year', ['year' => $year->name]) }}</h2>
        @forelse ($periods as $period)
            <article class="m3-card m3-card--outlined"><div class="m3-card__content">
                <h3>{{ $period['name'] }} · {{ $period['grade'] }}</h3>
                <p>{{ __(':from to :to', ['from' => $period['starts_on'], 'to' => $period['ends_on']]) }} · {{ $period['lessons_continue'] ? __('Lessons continue') : __('Lessons are left out') }}</p>
                <form method="POST" action="{{ route('exam-periods.update', $period['id']) }}" aria-label="{{ __('Change :name', ['name' => $period['name'].' · '.$period['grade']]) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="session_year_id" value="{{ $year->id }}">
                    <x-page.field name="name" :id="'period-name-'.$period['id']" :label="__('Name')" :value="$period['name']" :restore-old-input="false" maxlength="120" required />
                    <x-page.field name="grade_level_id" :id="'period-grade-'.$period['id']" type="select" :label="__('Grade')" :value="$period['grade_level_id']" :options="$grades" :restore-old-input="false" required />
                    <x-page.field name="starts_on" :id="'period-start-'.$period['id']" type="date" :label="__('First exam day')" :value="$period['starts_on']" :restore-old-input="false" required />
                    <x-page.field name="ends_on" :id="'period-end-'.$period['id']" type="date" :label="__('Last exam day')" :value="$period['ends_on']" :restore-old-input="false" required />
                    <label><input type="checkbox" name="lessons_continue" value="1" @checked($period['lessons_continue'])> {{ __('Normal lessons continue on exam days') }}</label>
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save changes') }}</button>
                </form>
                <form method="POST" action="{{ route('exam-periods.destroy', $period['id']) }}" aria-label="{{ __('Remove :name', ['name' => $period['name'].' · '.$period['grade']]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="m3-button m3-button--text" type="submit">{{ __('Remove exam period') }}</button>
                </form>
            </div></article>
        @empty
            <x-page.empty-state :title="__('No exam periods yet')" :message="__('Add the first exam period below.')" />
        @endforelse
    </section>

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="add-exam-period"><div class="m3-card__content">
        <h2 id="add-exam-period">{{ __('Add an exam period') }}</h2>
        <p>{{ __('Learners and their guardians are told whenever an upcoming exam\'s date, time or venue changes on the exam timetable, in a period or not.') }}</p>
        <form method="POST" action="{{ route('exam-periods.store') }}" aria-label="{{ __('Add an exam period') }}">
            @csrf
            <input type="hidden" name="session_year_id" value="{{ $year->id }}">
            <x-page.field name="name" id="new-period-name" :label="__('Name')" maxlength="120" required />
            <x-page.field name="grade_level_id" id="new-period-grade" type="select" :label="__('Grade')" :options="$grades" :placeholder="__('Choose a grade')" required />
            <x-page.field name="starts_on" id="new-period-start" type="date" :label="__('First exam day')" required />
            <x-page.field name="ends_on" id="new-period-end" type="date" :label="__('Last exam day')" required />
            <label><input type="checkbox" name="lessons_continue" value="1"> {{ __('Normal lessons continue on exam days') }}</label>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Add exam period') }}</button>
        </form>
    </div></section>
</div>
@endsection
