@extends('layouts.master')
@section('title', __('Teaching groups'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Teaching groups')" :subtitle="__('Dated learner memberships and teacher allocations for each subject, grade and year.')" />
    @include('academics.groups-status')
    @if ($manage)<a class="m3-button m3-button--filled" href="{{ route('teaching-groups.create') }}">{{ __('New teaching group') }}</a>
    <a class="m3-button m3-button--outlined" href="{{ route('academic-allocations.index') }}">{{ __('Academic allocations') }}</a>@endif
    @forelse ($groups as $group)
        <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h2><a href="{{ route('teaching-groups.show', $group->id) }}">{{ $group->name }}</a></h2>
            <p>{{ $group->subject->name }} · {{ $group->gradeLevel->name }} · {{ $group->year->name }} · {{ $group->active ? __('Active') : __('Inactive') }}</p>
        </div></section>
    @empty<p role="status">{{ __('No teaching groups are available for your allocations.') }}</p>@endforelse
    @include('academics.groups-pagination', ['page' => $groups])
    @if ($manage)
        <h2>{{ __('Class grade mappings') }}</h2>
        <p>{{ __('Map grades explicitly before assigning learners. Existing dated group history protects used mappings.') }}</p>
        @foreach ($classes as $class)
            <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                <h3>{{ $class->name }}</h3>
                @can('class-edit')<form method="POST" action="{{ route('academic-structure.map', $class->id) }}">@csrf @method('PUT')
                    <label class="m3-text-field m3-text-field--select" for="class-grade-{{ $class->id }}"><select class="m3-text-field__input" name="grade_level_id" id="class-grade-{{ $class->id }}" aria-label="{{ __('Grade for class') }} {{ $class->name }}">
                        <option value="">{{ __('Not mapped') }}</option>@foreach ($grades as $id => $name)<option value="{{ $id }}" @selected((string) $id === (string) $class->grade_level_id)>{{ $name }}</option>@endforeach
                    </select><span class="m3-text-field__label">{{ __('Grade for class') }} {{ $class->name }}</span></label>
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save grade mapping') }}</button>
                </form>@else<p>{{ $class->gradeLevel?->name ?? __('Not mapped') }}</p>@endcan
            </div></section>
        @endforeach
    @endif
</div>
@endsection
