@extends('layouts.master')
@section('title', __('Capture daily attendance'))
@section('content')
<div class="content-wrapper m3 page-content" data-attendance-workspace="capture" data-roster-url="{{ url('student-list') }}" data-holiday-url="{{ url('getAttendanceData') }}" data-current-year="{{ $currentSessionYear->id }}">
<x-page.header :title="__('Capture daily attendance')" :subtitle="__('Select a class and date, review every learner, then save the register. Approved leave remains locked to its recorded day portion.')" />
<p class="page-notice">{{ __('Daily capture writes to the current school year.') }} {{ $currentSessionYear->name }}</p>
<x-page.form-card id="daily-attendance-form" :action="route('attendance.store')" :title="__('Class register')" :submit-label="__('Save attendance')" :draft="false">
<div class="m3-grid">
<label class="m3-field m3-col-12 m3-col-expanded-6" for="capture-class"><span class="m3-field__label">{{ __('Class and section') }}</span>
<select class="m3-field__input" id="capture-class" name="class_section_id" required><option value="">{{ __('Choose a class') }}</option>
@foreach($class_sections as $section)<option value="{{ $section->id }}" data-start="{{ $classSectionDateLimits[$section->id]['start_date'] ?? '' }}" data-end="{{ $classSectionDateLimits[$section->id]['end_date'] ?? '' }}">{{ $section->class->name }} – {{ $section->section->name }} {{ $section->class->medium->name }} {{ $section->class->streams->name ?? '' }}</option>@endforeach
</select></label>
<x-page.field id="capture-date" name="capture_date" type="date" :label="__('Date')" :min="\Carbon\Carbon::parse($currentSessionYear->start_date)->toDateString()" :max="min(\Carbon\Carbon::parse($currentSessionYear->end_date)->toDateString(),now()->toDateString())" class="m3-col-12 m3-col-expanded-6" required />
</div>
<input type="hidden" name="date" id="capture-date-payload">
<label class="m3-checkbox"><input class="m3-checkbox__input" type="checkbox" name="holiday" id="capture-holiday" value="3"><span class="m3-checkbox__control"></span><span>{{ __('Record the selected class date as a holiday') }}</span></label>
<p data-attendance-message role="status">{{ __('Choose a class and date to load the register.') }}</p>
<div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('Learner register') }}"><table class="page-table__table"><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Admission number') }}</th><th scope="col">{{ __('Roll number') }}</th><th scope="col">{{ __('Attendance') }}</th></tr></thead><tbody id="capture-roster"></tbody></table></div>
</x-page.form-card>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/attendance-workspace.js') }}?v={{ filemtime(public_path('assets/js/custom/attendance-workspace.js')) }}" defer></script>@endsection
