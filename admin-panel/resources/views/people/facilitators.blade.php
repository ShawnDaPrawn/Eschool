@extends('layouts.master')
@section('title', __('Facilitators'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Facilitators')" :subtitle="__('People facilitating school activities, with their dated group allocations.')">
        <x-slot:actions>@can('staff-create')<a class="m3-button m3-button--filled" href="{{ route('staff.index', ['view' => 'create', 'role' => 'Facilitator']) }}">{{ __('Add facilitator') }}</a>@endcan @if($canManage)<a class="m3-button m3-button--outlined" href="{{ route('activity-groups.index') }}">{{ __('Manage group allocations') }}</a>@endif</x-slot:actions>
    </x-page.header>
    <form method="GET" action="{{ route('people.facilitators.index') }}" class="panel-form-grid" role="search">
        <x-page.field name="search" :label="__('Search facilitators')" :value="request('search')" maxlength="100" :restore-old-input="false" />
        <button type="submit" class="m3-button m3-button--outlined">{{ __('Search') }}</button>
    </form>
    <p>{{ __('School year') }}: {{ $year?->name ?? __('Not configured') }}</p>
    <div class="panel-record-list">
    @forelse($people as $person)
        <section class="m3-card m3-card--outlined"><div class="m3-card__content">
            <h2>{{ $person->full_name }}</h2>
            <p>{{ $person->status ? __('Active account') : __('Inactive account') }}</p>
            <ul class="m3-list m3-list--divided">
            @forelse($allocations->get($person->id, collect()) as $allocation)
                <li class="m3-list__item"><div class="m3-list__content">
                    <span class="m3-list__headline">{{ $groups->get($allocation->activity_group_id)?->name }}</span>
                    <span class="m3-list__supporting">{{ $allocation->starts_on->toDateString() }} – {{ $allocation->ends_on->toDateString() }} · {{ $allocation->active ? __('Allocated') : __('Cancelled') }}</span>
                    @if($canManage)<a class="m3-button m3-button--text" href="{{ route('activity-groups.index', ['group_id' => $allocation->activity_group_id, 'edit' => 'staff', 'record_id' => $allocation->id]) }}">{{ __('Review allocation') }}</a>@endif
                </div></li>
            @empty<li class="m3-list__item">{{ __('No activity allocation in the selected year.') }}</li>@endforelse
            </ul>
        </div></section>
    @empty<x-page.empty-state :title="__('No facilitators found')" :message="__('Facilitator memberships and staff allocated as facilitators appear here. Try another search or review activity allocations.')" />@endforelse
    </div>
    {{ $people->links() }}
</div>
@endsection
