@extends('layouts.master')
@section('title', __('Teaching groups'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Teaching groups')" :subtitle="__('Dated learner memberships and teacher allocations for each subject, grade and year.')" />
    @include('academics.groups-status')
    @php($workspaceView = request('view') === 'configuration' && $manage ? 'configuration' : 'groups')
    <nav class="workspace-tabs" aria-label="{{ __('Teaching group workspace') }}">
        <a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['view' => 'groups']) }}" @if($workspaceView === 'groups') aria-current="page" @endif>{{ __('Teaching groups') }}</a>
        @if($manage)<a class="m3-button m3-button--text" href="{{ request()->fullUrlWithQuery(['view' => 'configuration']) }}" @if($workspaceView === 'configuration') aria-current="page" @endif>{{ __('Configuration') }}</a>@endif
    </nav>
    @if($workspaceView === 'groups')
    @if ($manage)<a class="m3-button m3-button--filled" href="{{ route('teaching-groups.create') }}">{{ __('New teaching group') }}</a>
    <a class="m3-button m3-button--outlined" href="{{ route('academic-allocations.index') }}">{{ __('Academic allocations') }}</a>@endif
    @forelse ($groups as $group)
        <section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h2><a href="{{ route('teaching-groups.show', $group->id) }}">{{ $group->name }}</a></h2>
            <p>{{ $group->subject->name }} · {{ $group->gradeLevel->name }} · {{ $group->year->name }} · {{ $group->active ? __('Active') : __('Inactive') }}</p>
        </div></section>
    @empty<p role="status">{{ __('No teaching groups are available for your allocations.') }}</p>@endforelse
    @include('academics.groups-pagination', ['page' => $groups])
    @endif
    @if($workspaceView === 'configuration')
        @include('academics.workspaces.group-configuration')
    @endif
</div>
@endsection
