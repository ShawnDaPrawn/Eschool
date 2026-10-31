@extends('layouts.master')
@section('title', __('Teaching groups'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$group->name" :subtitle="$group->subject->name.' · '.$group->gradeLevel->name.' · '.$group->year->name" />
    @include('academics.groups-status')
    <a href="{{ route('teaching-groups.index') }}">{{ __('All teaching groups') }}</a>
    @if ($manage)<a class="m3-button m3-button--outlined" href="{{ route('teaching-groups.edit', $group->id) }}">{{ __('Edit group') }}</a>@endif
    <p>{{ __('Effective dates preserve history. Future teacher allocations allow planning; capture requires an allocation effective today.') }}</p>
    <h2>{{ __('Teacher allocations') }}</h2>
    @foreach ($teachers as $row)@include('academics.assignment-history', ['kind' => 'teachers'])@endforeach
    @include('academics.groups-pagination', ['page' => $teachers])
    @if ($manage)
        <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h3>{{ __('Allocate a teacher') }}</h3>
            <form method="POST" action="{{ route('teaching-groups.rows.store', [$group->id, 'teachers']) }}" aria-label="{{ __('Allocate a teacher') }}">@csrf
                <x-page.field id="new-group-teacher" name="teacher_id" type="select" :label="__('Teacher')" :options="$teacherChoices" required />
                @include('academics.assignment-dates', ['prefix' => 'new-teacher', 'start' => substr($group->year->start_date, 0, 10), 'end' => substr($group->year->end_date, 0, 10)])
                <button class="m3-button m3-button--filled" type="submit">{{ __('Save teacher allocation') }}</button>
            </form>
        </div></section>
    @endif
    <h2>{{ __('Learner memberships') }}</h2>
    @foreach ($memberships as $row)@include('academics.assignment-history', ['kind' => 'memberships'])@endforeach
    @include('academics.groups-pagination', ['page' => $memberships])
    @if ($manage && $group->homeroom !== null)
        <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h3>{{ __('Add everyone in the homeroom') }}</h3>
            <form method="POST" action="{{ route('teaching-groups.fill', $group->id) }}" aria-label="{{ __('Add everyone in the homeroom') }}">@csrf
                <p>{{ __('Each learner in the homeroom on the start date becomes a member until they leave the homeroom or the year ends. Learners who are already members are left alone.') }}</p>
                <x-page.field id="fill-mark-rule" name="mark_rule" type="select" :label="__('Mark rule')" :options="['catch_up' => __('Catch-up work'), 'excused' => __('Excused earlier work'), 'carried' => __('Reviewed carried marks')]" required />
                <x-page.field id="fill-starts" name="starts_on" type="date" :label="__('Members from')" :value="max(substr($group->year->start_date, 0, 10), $today)" required />
                <button class="m3-button m3-button--filled" type="submit">{{ __('Add the homeroom') }}</button>
            </form>
        </div></section>
    @endif
    @if ($manage)
        <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h3>{{ __('Add learner membership') }}</h3>
            <form method="POST" action="{{ route('teaching-groups.rows.store', [$group->id, 'memberships']) }}" aria-label="{{ __('Add learner membership') }}">@csrf
                <x-page.field id="new-group-learner" name="learner_id" type="select" :label="__('Learner')" :options="$learners" required />
                <x-page.field id="new-group-mark-rule" name="mark_rule" type="select" :label="__('Mark rule')" :options="['catch_up' => __('Catch-up work'), 'excused' => __('Excused earlier work'), 'carried' => __('Reviewed carried marks')]" required />
                @include('academics.assignment-dates', ['prefix' => 'new-membership', 'start' => substr($group->year->start_date, 0, 10), 'end' => substr($group->year->end_date, 0, 10)])
                <p>{{ __('A dated enrolment in the matching grade must cover this full membership interval. Marks are handled separately by the school policy.') }}</p>
                <button class="m3-button m3-button--filled" type="submit">{{ __('Save learner membership') }}</button>
            </form>
        </div></section>
    @endif
</div>
@endsection
