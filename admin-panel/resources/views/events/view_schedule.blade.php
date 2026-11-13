@extends('layouts.master')
@section('title', __('Event programme'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$event->title" :subtitle="__('Event programme')">
        <x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('events.index') }}">{{ __('Back to events') }}</a></x-slot:actions>
    </x-page.header>
    <p>{{ $event->start_date }} – {{ $event->end_date }}</p>
    @can('event-edit')
        <x-page.form-card id="school-programme-editor" :title="__('Edit programme')" :action="route('events.update-schedules')" :submit-label="__('Save programme')" :redirect="route('view_schedule.index',$event->id)" :draft="false" data-event-editor>
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            <input type="hidden" name="start_date" value="{{ $event->start_date }}" data-range-start>
            <input type="hidden" name="end_date" value="{{ $event->end_date }}" data-range-end>
            <input type="hidden" data-range-value>
            <div data-programme-rows>
                @forelse($multievents as $index=>$item)
                    @include('events.programme-row',['prefix'=>'edit_events','rowKey'=>(string)$index,'item'=>$item])
                @empty
                    @include('events.programme-row',['prefix'=>'events','rowKey'=>'0','item'=>null])
                @endforelse
            </div>
            <button type="button" class="m3-button m3-button--outlined" data-programme-add>{{ __('Add programme item') }}</button>
            <template data-programme-template>@include('events.programme-row',['prefix'=>'events','rowKey'=>'__ROW__','item'=>null])</template>
            <x-slot:actions><a class="m3-button m3-button--text" href="{{ route('events.index') }}">{{ __('Cancel') }}</a></x-slot:actions>
        </x-page.form-card>
    @else
        <div class="panel-record-list">
            @forelse($multievents as $item)
                <article class="m3-card m3-card--outlined"><div class="m3-card__content">
                    <h2>{{ $item->title }}</h2><p>{{ $item->date }} · {{ $item->start_time }} – {{ $item->end_time }}</p><p>{{ $item->description }}</p>
                </div></article>
            @empty<x-page.empty-state :title="__('No programme items')" :message="__('The event organiser has not added the programme yet.')" />@endforelse
        </div>
    @endcan
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/events-workspace.js') }}" defer></script>@endsection
