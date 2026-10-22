@extends('layouts.master')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/student-report.css') }}?v={{ filemtime(public_path('assets/css/student-report.css')) }}">
@endsection

@section('title')
    {{ __('student_report') }}
@endsection

@section('content')
<div class="content-wrapper" id="student-report-page"
    data-report-url="{{ route('student.report', $student->id) }}"
    data-loading-text="{{ __('loading') }}"
    data-resolved-session-year-id="{{ $sessionYearId ?? '' }}">
    <div class="container-fluid">

        <div class="page-header">
            <h3 class="page-title">{{ __('student_report') }}</h3>
            <a href="{{ route('students.report') }}" class="btn btn-secondary btn-sm">
                <x-icon name="back" :size="18" /> {{ __('back') }}
            </a>
        </div>

        {{-- HEADER --}}
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center">
                <img src="{{ $student->user?->image }}" class="sr-profile-avatar mr-3">
                <div>
                    <h4>{{ $student->user?->full_name }}</h4>
                    <small>ID: {{ $student->admission_no }}</small>
                </div>
            </div>
        </div>

        {{-- TABS + CONTENT --}}
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-fill" id="student-tabs">

                    <li class="nav-item">
                        <a class="nav-link tab-link {{ ($tab ?? 'personal') == 'personal' ? 'active' : '' }}"
                            data-tab="personal" href="javascript:void(0)">
                            {{ __('personal_info') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tab-link {{ ($tab ?? '') == 'attendance' ? 'active' : '' }}"
                            data-tab="attendance" href="javascript:void(0)">
                            {{ __('attendance') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tab-link {{ ($tab ?? '') == 'fees' ? 'active' : '' }}"
                            data-tab="fees" href="javascript:void(0)">
                            {{ __('fees') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tab-link {{ ($tab ?? '') == 'exam' ? 'active' : '' }}"
                            data-tab="exam" href="javascript:void(0)">
                            {{ __('exam') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tab-link {{ ($tab ?? '') == 'leave' ? 'active' : '' }}"
                            data-tab="leave" href="javascript:void(0)">
                            {{ __('leave') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tab-link {{ ($tab ?? '') == 'assignment' ? 'active' : '' }}"
                            data-tab="assignment" href="javascript:void(0)">
                            {{ __('assignment') }}
                        </a>
                    </li>

                </ul>
            </div>

            <div class="card-body" id="tab-content">
                @if(($tab ?? 'personal') == 'attendance')
                    @include('students.report.attendance')
                @elseif(($tab ?? '') == 'fees')
                    @include('students.report.fees')
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