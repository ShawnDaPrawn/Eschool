@extends('layouts.master')
@section('title', __('Monthly learner attendance'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/attendance.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content" data-attendance-monthly data-months-url="{{ url('get-session-year-months') }}" data-attendance-url="{{ url('attendance/monthly-ajax') }}">
<x-page.header :title="__('Monthly learner attendance')" :subtitle="__('Read a complete class register across the selected month. Each cell includes a status label as well as colour.')" />
<x-page.filters id="monthly-filters">
<x-page.field id="filter_class_section_id" name="class_section_id" type="select" :label="__('Class and section')" :options="$class_sections->mapWithKeys(fn($section)=>[$section->id=>$section->class->name.' '.$section->section->name.' '.($section->class->medium->name ?? '')])->all()" />
<label class="m3-field" for="filter_month"><span class="m3-field__label">{{ __('Month') }}</span><select id="filter_month" name="month" class="m3-field__input">                                    @php
                                        $sessionStart = \Carbon\Carbon::parse($currentSessionYear->start_date);
                                        $sessionEnd = \Carbon\Carbon::parse($currentSessionYear->end_date);
                                        $current = $sessionStart->copy()->startOfMonth();
                                    @endphp
                                    @while ($current->lte($sessionEnd))
                                        <option value="{{ $current->format('Y-m') }}"
                                            {{ $current->format('Y-m') === date('Y-m') ? 'selected' : '' }}>
                                            {{ $current->format('F Y') }}
                                        </option>
                                        @php $current->addMonth() @endphp
                                    @endwhile
</select></label>
<button type="button" class="m3-button m3-button--outlined" data-attendance-refresh>{{ __('Refresh') }}</button>
</x-page.filters>
<p>{{ __('P: present · A: absent · HD: half day · H: holiday · L: leave · —: no record') }}</p>
<p data-attendance-message role="status"></p>
<div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('Monthly learner attendance') }}"><table class="page-table__table" id="monthly-attendance-table"><thead></thead><tbody></tbody></table></div>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/attendance-monthly.js') }}?v={{ filemtime(public_path('assets/js/custom/attendance-monthly.js')) }}" defer></script>@endsection
