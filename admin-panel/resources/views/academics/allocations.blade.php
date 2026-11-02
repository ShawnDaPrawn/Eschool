@extends('layouts.master')
@section('title', __('Academic allocations'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Academic allocations')" :subtitle="__('Appoint homeroom teachers, grade heads and principals with explicit dates and school-year scope.')" />
    @include('academics.groups-status')
    <a href="{{ route('teaching-groups.index') }}">{{ __('Teaching groups') }}</a>
    <section class="m3-card m3-card--outlined page-section" aria-labelledby="staff-draft-start"><div class="m3-card__content">
        <h2 id="staff-draft-start">{{ __('Draft from last year') }}</h2>
        <p>{{ __('Start a new year\'s subject teachers and homeroom teachers from last year\'s, change what is different, then apply the draft. Teachers see nothing until it is applied.') }}</p>
        @if ($draftYears === [])
            <p>{{ __('No school year is running or still to come.') }}</p>
        @else
            <form method="GET" action="{{ route('academic-allocations.draft') }}" aria-label="{{ __('Draft from last year') }}">
                <x-page.field id="staff-draft-year" name="year" type="select" :label="__('School year')" :options="$draftYears" :value="$draftYear" :restore-old-input="false" required />
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Build the draft') }}</button>
            </form>
        @endif
    </div></section>
    <h2>{{ __('Homeroom teachers') }}</h2>
    @foreach ($homerooms as $row)@include('academics.assignment-history', ['kind' => 'homerooms', 'manage' => true])@endforeach
    @include('academics.groups-pagination', ['page' => $homerooms])
    <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
        <h3>{{ __('Allocate a homeroom teacher') }}</h3>
        <form method="POST" action="{{ route('academic-allocations.store', 'homerooms') }}" aria-label="{{ __('Allocate a homeroom teacher') }}">@csrf
            <x-page.field id="new-homeroom-teacher" name="teacher_id" type="select" :label="__('Teacher')" :options="$teacherChoices" required />
            <x-page.field id="new-homeroom-room" name="class_section_id" type="select" :label="__('Homeroom')" :options="$rooms" required />
            <x-page.field id="new-homeroom-year" name="session_year_id" type="select" :label="__('School year')" :options="$years->pluck('name', 'id')->all()" required />
            @include('academics.assignment-dates', ['prefix' => 'new-homeroom', 'start' => substr($years->first()?->start_date ?? '', 0, 10), 'end' => substr($years->first()?->end_date ?? '', 0, 10)])
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save homeroom allocation') }}</button>
        </form>
    </div></section>
    <h2>{{ __('Academic leadership') }}</h2>
    @foreach ($leadership as $row)@include('academics.assignment-history', ['kind' => 'leadership', 'manage' => true])@endforeach
    @include('academics.groups-pagination', ['page' => $leadership])
    <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
        <h3>{{ __('Appoint academic leader') }}</h3>
        <form method="POST" action="{{ route('academic-allocations.store', 'leadership') }}" aria-label="{{ __('Appoint academic leader') }}">@csrf
            <x-page.field id="new-leader-person" name="user_id" type="select" :label="__('Staff member')" :options="$staff" required />
            <x-page.field id="new-leader-kind" name="kind" type="select" :label="__('Leadership role')" :options="['grade_head' => __('Grade head'), 'principal' => __('Principal')]" required />
            <x-page.field id="new-leader-grade" name="grade_level_id" type="select" :label="__('Grade (leave blank for principal)')" :options="['' => __('All grades')] + $grades" />
            <x-page.field id="new-leader-year" name="session_year_id" type="select" :label="__('School year')" :options="$years->pluck('name', 'id')->all()" required />
            @include('academics.assignment-dates', ['prefix' => 'new-leader', 'start' => substr($years->first()?->start_date ?? '', 0, 10), 'end' => substr($years->first()?->end_date ?? '', 0, 10)])
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save leadership appointment') }}</button>
        </form>
    </div></section>
</div>
@endsection
