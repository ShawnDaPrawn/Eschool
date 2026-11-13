@extends('layouts.master')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/student-report.css') }}?v={{ filemtime(public_path('assets/css/student-report.css')) }}">
@endsection

@section('title')
    {{ __('student_report') }}
@endsection

@section('content')
<div class="content-wrapper m3 page-content" id="student-report-page"
    data-report-url="{{ route('student.report', $student->id) }}"
    data-loading-text="{{ __('loading') }}"
    data-load-error="{{ __('The report could not be loaded. Your previous view is still available. Try again.') }}"
    data-resolved-session-year-id="{{ $sessionYearId ?? '' }}">
    <div class="container-fluid">

        <x-page.header :title="__('student_report')" :subtitle="$student->user?->full_name" />
        <a class="m3-button m3-button--text" href="{{ route('students.report', in_array($tab ?? 'personal', ['attendance', 'leave'], true) ? ['report_tab' => $tab] : []) }}">{{ __('Back to learners') }}</a>

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center">
                <img src="{{ $student->user?->image }}" class="sr-profile-avatar mr-3" alt="{{ $student->user?->full_name }}">
                <div>
                    <h4>{{ $student->user?->full_name }}</h4>
                    <x-page.learner-identifiers :identifier="$identifier ?? null" :admission-no="$student->admission_no" />
                </div>
            </div>
        </div>

        {{-- TABS + CONTENT --}}
        <div class="card">
            <nav class="workspace-tabs" id="student-tabs" aria-label="{{ __('Learner report sections') }}">
                @foreach(['personal' => 'personal_info', 'attendance' => 'attendance', 'exam' => 'exam', 'leave' => 'leave', 'assignment' => 'assignment'] as $section => $label)
                    <a class="m3-button m3-button--text tab-link {{ ($tab ?? 'personal') === $section ? 'active' : '' }}" data-tab="{{ $section }}"
                       href="{{ route('student.report', ['id' => $student->id, 'tab' => $section]) }}" @if(($tab ?? 'personal') === $section) aria-current="page" @endif>{{ __($label) }}</a>
                @endforeach
            </nav>
            <p role="status" aria-live="polite" data-report-status></p>

            <div class="card-body" id="tab-content">
                @if(($tab ?? 'personal') == 'attendance')
                    @include('students.report.attendance')
                @elseif(($tab ?? '') == 'exam')
                    @include('students.report.exam')
                @elseif(($tab ?? '') == 'leave')
                    @include('students.report.leave')
                @elseif(($tab ?? '') == 'assignment')
                    @include('students.report.assignment')
                @else
                    @include('students.report.personal')
                @endif
            </div>
        </div>

    </div>
</div>

@endsection


@section('script')
    <script src="{{ asset('/assets/js/student-report.js') }}?v={{ filemtime(public_path('assets/js/student-report.js')) }}"></script>
@endsection