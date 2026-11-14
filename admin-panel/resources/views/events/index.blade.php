@extends('layouts.master')
@section('title', __('events'))
@section('content')
@php
    $eventView = (request('view') === 'create' || !Auth::user()->can('event-list')) && Auth::user()->can('event-create') ? 'create' : ($editingEvent ? 'edit' : 'list');
    $selectedYear = app(\App\Services\Panel\SessionYearContext::class)->selectedId();
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$editingEvent ? __('Edit event') : __('School events')" :subtitle="__('Plan school events and their programmes. Ticket sales and admissions remain linked to the event.')"
        :primary-label="$eventView === 'list' ? __('Create event') : null" :primary-href="$eventView === 'list' ? route('events.index',['view'=>'create']) : null" primary-permission="event-create" />
    <x-panel-view-nav :current="$eventView === 'edit' ? 'list' : $eventView" label="Event workspace" :items="[
        'list'=>['label'=>'Events','href'=>route('events.index')],
        'create'=>['label'=>'Create event','href'=>route('events.index',['view'=>'create']),'visible'=>Auth::user()->can('event-create')],
    ]" />
    @if($eventView === 'list')
        <p id="event-action-status" role="alert" tabindex="-1" hidden></p>
        <x-page.table id="school-events" :url="url('events/show')" query-params="eventListParams" :empty-title="__('No school events yet')" :empty-message="__('Create the first event for the selected school year.')" :empty-action-label="Auth::user()->can('event-create') ? __('Create event') : null" :empty-action-href="Auth::user()->can('event-create') ? route('events.index',['view'=>'create']) : null">
            <x-slot:columns>
                <th data-field="title" data-sortable="true">{{ __('title') }}</th>
                <th data-field="type">{{ __('type') }}</th>
                <th data-field="date">{{ __('date') }}</th>
                <th data-field="time">{{ __('timing') }}</th>
                <th data-field="description">{{ __('description') }}</th>
                <th data-field="image" data-formatter="imageFormatter" data-escape="false">{{ __('image') }}</th>
                <th data-field="operate" data-escape="false">{{ __('action') }}</th>
            </x-slot:columns>
        </x-page.table>
    @else
        @include('events.form', ['event'=>$editingEvent,'selectedYear'=>$selectedYear])
    @endif
</div>
@endsection
@section('script')
<script src="{{ asset('assets/js/events-workspace.js') }}?v={{ filemtime(public_path('assets/js/events-workspace.js')) }}" defer></script>
@endsection
