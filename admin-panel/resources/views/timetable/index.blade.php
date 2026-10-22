@extends('layouts.master')

@section('title')
    {{ __('timetable') }}
@endsection

@section('content')
    @php
        $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];
    @endphp
    <div class="content-wrapper" id="timetable-page"
        data-url-class-sections="{{ url('get-class-sections-with-semesters') }}"
        data-url-subjects="{{ url('get-subject-by-class-section') }}"
        data-url-teachers="{{ url('getteacherbysubject') }}" data-url-check="{{ url('checkTimetable') }}">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('create') . ' ' . __('timetable') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <div class="row tt-filters">
                            <div class="form-group col-sm-12 col-md-4 col-lg-3">
                                <label for="session_year" class="form-label text-nowrap">
                                    {{ __('session_year') }} <span class="text-danger">*</span>
                                </label>
                                <select name="session_year" id="session_year" class="form-control session_year">
                                    <option value="">{{ __('select') }}</option>
                                    @foreach ($session_years as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="select_error text-danger d-none">{{ __('This field is required') }}.</div>
                            </div>

                            <div class="form-group col-sm-12 col-md-4 col-lg-3">
                                <label class="form-label">{{ __('class') }} {{ __('section') }} <span
                                        class="text-danger">*</span></label>
                                <select required name="class_section_id" id="timetable_class_section" class="form-control"
                                    aria-hidden="true">
                                    <option value="">{{ __('select') }}</option>
                                </select>
                                <div class="select_error text-danger d-none">{{ __('This field is required') }}.</div>
                                <input type="hidden" name="active_tab" id="active_tab">
                            </div>
                        </div>

                        <div id="timetable-loader" class="d-none">
                            <div class="tt-loader">
                                <x-icon name="refresh" class="tt-loader__spinner" :size="40" />
                                <p class="tt-loader__text">{{ __('loading') }}</p>
                            </div>
                        </div>

                        <div id="timetable-div" class="d-none">
                            <ul class="nav nav-tabs tt-days timetable_nav" role="tablist">
                                @foreach ($days as $key => $label)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $key }}-tab"
                                            data-id="{{ $key }}" data-toggle="tab" href="#{{ $key }}" role="tab"
                                            aria-controls="{{ $key }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ __($label) }}</a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content">
                                @foreach ($days as $key => $label)
                                    <div class="tab-pane fade {{ $loop->first ? 'active show' : '' }}" id="{{ $key }}"
                                        role="tabpanel" aria-labelledby="{{ $key }}-tab">
                                        @include('timetable.tab_title')
                                        <div data-repeater-list="{{ $key }}_group" class="set_{{ $key }} tt-list">
                                            <div data-repeater-item class="tt-row {{ $key }}_count">
                                                @include('timetable.addmore')
                                            </div>
                                        </div>
                                        @include('timetable.tab_footer')
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('/assets/js/custom/timetable.js') }}"></script>
@endsection
