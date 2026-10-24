@extends('layouts.master')
@section('title', __('Teaching groups'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$group?->name ?? __('New teaching group')" :subtitle="__('Use the grade reference and effective allocations rather than inferring a grade from a class name.')" />
    @include('academics.groups-status')
    <a href="{{ route('teaching-groups.index') }}">{{ __('All teaching groups') }}</a>
    <form method="POST" action="{{ $group ? route('teaching-groups.update', $group->id) : route('teaching-groups.store') }}" class="m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">@csrf
        @if ($group) @method('PUT')<input type="hidden" name="revision" value="{{ $group->revision }}">@endif
        <x-page.field name="name" :label="__('Group name')" :value="$group?->name" required maxlength="191" />
        @if ($group)
            @foreach (['subject_id', 'grade_level_id', 'session_year_id', 'class_section_id'] as $key)<input type="hidden" name="{{ $key }}" value="{{ $group->$key }}">@endforeach
            <p>{{ __('Academic context is preserved. Create another group for a different subject, grade, year or homeroom.') }}</p>
        @else
            <x-page.field name="subject_id" type="select" :label="__('Subject')" :options="$subjects" required />
            <x-page.field name="grade_level_id" type="select" :label="__('Grade')" :options="$grades" required />
            <x-page.field name="session_year_id" type="select" :label="__('School year')" :options="$years->pluck('name', 'id')->all()" required />
            <x-page.field name="class_section_id" type="select" :label="__('Homeroom (optional)')" :options="['' => __('Mixed homerooms')] + $rooms" />
        @endif
        <x-page.field name="active" type="select" :label="__('Group status')" :value="$group === null || $group->active ? '1' : '0'" :options="['1' => __('Active'), '0' => __('Inactive')]" required />
        <button class="m3-button m3-button--filled" type="submit">{{ __('Save teaching group') }}</button>
    </div></form>
</div>
@endsection
