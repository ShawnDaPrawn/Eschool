@extends('layouts.master')
@section('title', __('Academic timetable'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/timetable-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content timetable-workbench" data-timetable-workbench>
    <x-page.header :title="__('Academic timetable')" :subtitle="__('Configure rooms and bells, preview actual-time clashes, then save a week safely.')" />
    @include('academics.groups-status')
    @if ($conflict)<div role="alert"><p>{{ $conflict }}</p><p>{{ __('Your placement was not overwritten. Reload the week and compare the saved lesson before trying again.') }}</p></div>@endif
    <form method="GET" action="{{ route('academic-timetable.index') }}" aria-label="{{ __('Choose timetable week') }}" class="timetable-fields">
        <x-page.field name="session_year_id" type="select" :label="__('Timetable school year')" :value="$year->id" :options="$years->pluck('name', 'id')->all()" required />
        <x-page.field name="week_on" type="date" :label="__('Week starts on')" :value="$weekOn" required />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('View week') }}</button>
    </form>
    <nav aria-label="{{ __('Timetable sections') }}" class="timetable-links">
        <a href="#week">{{ __('Week') }}</a><a href="#placement">{{ __('Place a lesson') }}</a><a href="#rooms">{{ __('Rooms') }}</a><a href="#bells">{{ __('Bell schedules') }}</a><a href="#import">{{ __('Import timetable') }}</a>
    </nav>
    <section id="week" class="page-section" aria-labelledby="week-title">
        <h2 id="week-title">{{ __('Week') }}: {{ $week['starts_on'] }} – {{ $week['ends_on'] }}</h2>
        <div class="timetable-week">
            @for ($day = 0; $day < 7; $day++)
                @php $date = \Carbon\CarbonImmutable::parse($weekOn)->addDays($day)->toDateString(); $onDay = collect($week['lessons'])->where('date', $date); @endphp
                <section class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ __(\Carbon\CarbonImmutable::parse($date)->format('l')) }} {{ $date }}</h3>
                    @forelse ($onDay as $entry)
                        <article class="timetable-lesson"><h4>{{ $entry['title'] }}</h4><p>{{ $entry['starts_at'] }}–{{ $entry['ends_at'] }} · {{ $entry['room_name'] }}</p><p>{{ $entry['teacher_name'] }}</p>
                            <a href="{{ route('academic-timetable.index', ['session_year_id' => $year->id, 'week_on' => $weekOn, 'edit' => $entry['id']]) }}#placement">{{ __('Edit lesson') }}: {{ $entry['title'] }}</a>
                        </article>
                    @empty<p>{{ __('No lessons') }}</p>@endforelse
                </div></section>
            @endfor
        </div>
    </section>
    @if ($preview)
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="preview-title"><div class="m3-card__content">
            <h2 id="preview-title">{{ __('Placement preview') }}</h2>
            @if ($preview['can_save'])<p role="status">{{ __('No clashes. Ready to save.') }}</p>@else<p role="alert">{{ __('Resolve these clashes before saving.') }}</p>@endif
            @foreach ($preview['conflicts'] as $clash)<p>{{ $clash['date'] ?? '' }} · {{ implode(', ', array_map(fn ($code) => __($code), $clash['reasons'])) }}</p>@endforeach
            <p>{{ __('Lessons in this batch') }}: {{ count($preview['rows']) }}</p>
            @foreach ($preview['rows'] as $row)<p>{{ $row['active'] ? __('Place or update') : __('Cancel') }} · {{ $choices['teaching_group_id'][$row['teaching_group_id']] ?? $choices['class_section_id'][$row['class_section_id']] ?? __('Saved lesson') }} · {{ $row['starts_on'] }} – {{ $row['ends_on'] }}</p>@endforeach
            <form method="POST" action="{{ route('academic-timetable.commit') }}" aria-label="{{ __('Save previewed timetable') }}">@csrf
                @include('academics.timetable-context')
                <input type="hidden" name="payload" value="{{ json_encode($preview['rows'], JSON_THROW_ON_ERROR) }}"><input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
                <button type="submit" class="m3-button m3-button--filled" @disabled(!$preview['can_save'])>{{ __('Save previewed timetable') }}</button>
            </form>
        </div></section>
    @endif
    <section id="placement" class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
        <h2>{{ isset($draft['id']) ? __('Edit lesson') : __('Place a lesson') }}</h2>
        <form method="POST" action="{{ route('academic-timetable.preview') }}" aria-label="{{ __('Preview a lesson') }}">@csrf
            @include('academics.timetable-context')
            <input type="hidden" name="lesson[id]" value="{{ $draft['id'] ?? '' }}"><input type="hidden" name="lesson[revision]" value="{{ $draft['revision'] ?? '' }}"><input type="hidden" name="lesson[term_id]" value="{{ $draft['term_id'] ?? '' }}">
            <p>{{ __('Choose a teaching group or a homeroom. Leave the other blank.') }}</p>
            <div class="timetable-fields">
                @foreach (['teaching_group_id' => __('Teaching group'), 'class_section_id' => __('Homeroom'), 'teacher_id' => __('Teacher'), 'room_id' => __('Room'), 'bell_period_id' => __('Period'), 'weekday' => __('Day')] as $field => $label)
                    <x-page.field :name="'lesson['.$field.']'" type="select" :label="$label" :value="$draft[$field] ?? null" :options="$choices[$field]" :placeholder="__('Choose')" />
                @endforeach
                <x-page.field name="lesson[starts_on]" type="date" :label="__('Lesson starts on')" :value="$draft['starts_on']" required />
                <x-page.field name="lesson[ends_on]" type="date" :label="__('Lesson ends on')" :value="$draft['ends_on']" required />
                <x-page.field name="lesson[occurs_on]" type="date" :label="__('Specific date (optional)')" :value="$draft['occurs_on'] ?? null" />
                <x-page.field name="lesson[week_pattern]" type="select" :label="__('Weeks')" :value="$draft['week_pattern']" :options="['all' => __('All weeks'), 'odd' => __('Odd weeks'), 'even' => __('Even weeks')]" required />
            </div>
            @if (isset($draft['id']))<input type="hidden" name="lesson[active]" value="0"><label><input type="checkbox" name="lesson[active]" value="1" @checked($draft['active'])> {{ __('Lesson active') }}</label><p>{{ __('Cancelling preserves the saved placement. Other placement edits are not applied.') }}</p>
            @else<input type="hidden" name="lesson[active]" value="1">@endif
            <button class="m3-button m3-button--filled" type="submit">{{ __('Preview placement') }}</button>
        </form>
    </div></section>
    <details id="rooms" class="m3-card m3-card--outlined page-section" @if($room) open @endif><summary>{{ __('Rooms') }}</summary><div class="m3-card__content">
        <h2>{{ $room ? __('Edit room') : __('New room') }}</h2>
        <form method="POST" action="{{ route('academic-timetable.room') }}" aria-label="{{ __('Save a room') }}">@csrf @include('academics.timetable-context')
            <input type="hidden" name="room_id" value="{{ $room?->id }}"><input type="hidden" name="revision" value="{{ $room?->revision }}">
            <div class="timetable-fields">
                <x-page.field name="name" id="room-name" :label="__('Room name')" :value="$room?->name" maxlength="191" required />
                <x-page.field name="building" id="room-building" :label="__('Building (optional)')" :value="$room?->building" maxlength="191" />
                <x-page.field name="capacity" id="room-capacity" type="number" :label="__('Capacity (optional)')" :value="$room?->capacity" min="1" max="5000" />
                <x-page.field name="type" id="room-type" :label="__('Room type')" :value="$room?->type ?? 'classroom'" maxlength="64" required />
            </div>
            <input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" @checked($room?->active ?? true)> {{ __('Room active') }}</label>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save room') }}</button>
        </form>
        <ul>@foreach ($configuration['rooms'] as $item)<li><a href="{{ route('academic-timetable.index', ['session_year_id' => $year->id, 'week_on' => $weekOn, 'room' => $item['id']]) }}#rooms">{{ $item['name'] }}</a> · {{ $item['active'] ? __('Active') : __('Inactive') }}</li>@endforeach</ul>
    </div></details>
    <details id="bells" class="m3-card m3-card--outlined page-section" @if($schedule) open @endif><summary>{{ __('Bell schedules') }}</summary><div class="m3-card__content">
        <h2>{{ __('New bell schedule') }}</h2><p>{{ __('Copy a used schedule before changing its periods, so existing lessons keep their history.') }}</p>
        <form method="POST" action="{{ route('academic-timetable.bells') }}" aria-label="{{ __('Build bell schedule') }}" data-bell-form>@csrf @include('academics.timetable-context')
            <div class="timetable-fields"><x-page.field name="name" id="bells-name" :label="__('Bell schedule name')" :value="$schedule ? $schedule->name.' '.__('copy') : null" required maxlength="191" /><x-page.field name="section" id="bells-section" :label="__('School section')" :value="$schedule?->section" required maxlength="100" /></div>
            <div data-period-list>
                @foreach ($schedule?->periods ?? [['name' => '', 'starts_at' => '', 'ends_at' => '', 'is_break' => false]] as $i => $period)
                    @include('academics.timetable-period', ['position' => $i, 'period' => $period])
                @endforeach
            </div>
            <template data-period-template>@include('academics.timetable-period', ['position' => '__INDEX__', 'period' => ['name' => '', 'starts_at' => '', 'ends_at' => '', 'is_break' => false]])</template>
            <button type="button" class="m3-button m3-button--outlined" data-add-period>{{ __('Add period') }}</button><button type="submit" class="m3-button m3-button--filled">{{ __('Save bell schedule') }}</button>
        </form>
        <ul>@foreach ($configuration['bell_schedules'] as $item)<li>{{ $item['name'] }} · {{ $item['section'] }} <a href="{{ route('academic-timetable.index', ['session_year_id' => $year->id, 'week_on' => $weekOn, 'schedule' => $item['id']]) }}#bells">{{ __('Copy schedule') }}</a></li>@endforeach</ul>
    </div></details>
    @include('academics.timetable-import')
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/timetable-workbench.js') }}" defer></script>@endsection
