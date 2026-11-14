@php
    $editing = $event !== null;
    $type = $event?->type ?? 'single';
    $prefix = $editing ? 'edit_events' : 'events';
@endphp
<x-page.form-card id="school-event-editor" :title="$editing ? __('Event details') : __('Create event')" :action="$editing ? route('events.update',$event->id) : route('events.store')" :method="$editing ? 'PUT' : 'POST'" :submit-label="$editing ? __('Save event') : __('Create event')" :redirect="route('events.index')" :draft="false" data-event-editor>
    @if($editing)<input type="hidden" name="edit_id" value="{{ $event->id }}">@endif
    <input type="hidden" name="session_year_id" value="{{ $event?->session_year_id ?? $selectedYear }}">
    <div class="panel-form-grid">
        <x-page.field id="school-event-title" name="title" :label="__('Event title')" :value="$event?->title" required maxlength="255" />
        <x-page.field id="school-event-description" name="description" type="textarea" :label="__('description')" :value="$event?->description" rows="4" />
        <x-page.field id="school-event-image" name="image" type="file" :label="__('Event image')" accept="image/png,image/jpeg" :help="__('Choose a JPEG or PNG. Leave empty to keep the existing image.')" />
        <x-page.field id="school-event-type" :name="$editing ? 'edit_event_type' : 'event_type'" type="select" :label="__('Programme type')" :options="['single'=>__('Single event'),'multiple'=>__('Event with several programme items')]" :value="$type" required data-event-type />
    </div>
    @if($event?->image)<img src="{{ $event->image }}" alt="{{ __('Current event image') }}" width="160" loading="lazy">@endif
    <fieldset data-event-single @if($type !== 'single') hidden disabled @endif>
        <legend>{{ __('Date and time') }}</legend>
        <div class="panel-form-grid">
            <x-page.field id="school-event-date" :name="$editing ? 'edit_date' : 'date'" type="date" :label="__('Event date')" :value="$event?->start_date ? \Carbon\Carbon::parse($event->start_date)->format('Y-m-d') : null" required />
            <div data-event-time-pair>
                <x-page.field id="school-event-start" name="single_start" type="time" :label="__('Start time (optional)')" :value="$event?->start_time" data-time-start />
                <x-page.field id="school-event-end" name="single_end" type="time" :label="__('End time (optional)')" :value="$event?->end_time" data-time-end />
                <input type="hidden" data-time-value name="{{ $editing ? 'edit_time' : 'time' }}" value="{{ $event?->start_time ? $event->start_time.' - '.$event->end_time : '' }}">
            </div>
        </div>
    </fieldset>
    <fieldset data-event-multiple @if($type !== 'multiple') hidden disabled @endif>
        <legend>{{ __('Programme') }}</legend>
        <div class="panel-form-grid">
            <x-page.field id="school-event-range-start" name="range_start" type="date" :label="__('First event date')" :value="$event?->start_date" required data-range-start />
            <x-page.field id="school-event-range-end" name="range_end" type="date" :label="__('Last event date')" :value="$event?->end_date" required data-range-end />
            <input type="hidden" name="date_range" data-range-value>
        </div>
        <p>{{ __('Programme dates must fall within the event dates. Each item has its own title, time and description.') }}</p>
        <div data-programme-rows>
            @forelse($event?->multipleEvent ?? [] as $index=>$item)
                @include('events.programme-row',['prefix'=>'edit_events','rowKey'=>(string)$index,'item'=>$item])
            @empty
                @include('events.programme-row',['prefix'=>'events','rowKey'=>'0','item'=>null])
            @endforelse
        </div>
        <button type="button" class="m3-button m3-button--outlined" data-programme-add>{{ __('Add programme item') }}</button>
        <template data-programme-template>@include('events.programme-row',['prefix'=>'events','rowKey'=>'__ROW__','item'=>null])</template>
    </fieldset>
    <x-slot:actions><a class="m3-button m3-button--text" href="{{ route('events.index') }}">{{ __('Cancel') }}</a></x-slot:actions>
</x-page.form-card>
