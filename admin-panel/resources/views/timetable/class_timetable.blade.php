@extends('layouts.master')

@section('title')
    {{ __('class_timetable') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('class_timetable') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        @if (Auth::user()->hasRole('Teacher'))
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4 col-lg-3">
                                    <label>{{ __('class') }} {{ __('section') }}</label>
                                    <select required name="class_section_id" id="class_timetable_class_section"
                                        class="form-control" style="width:100%;" tabindex="-1" aria-hidden="true"
                                        data-scope="teacher">
                                        <option value="">{{ __('select') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="timetable-empty warning_no_data" role="alert">
                                <strong>{{ __('no_data_found') }}</strong>
                            </div>
                            <div class="set_timetable timetable-grid"></div>
                        @else
                            @can('class-timetable')
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-4 col-lg-3">
                                        <label>{{ __('class') }} {{ __('section') }}</label>
                                        <select required name="class_section_id" id="class_timetable_class_section"
                                            class="form-control" style="width:100%;" tabindex="-1" aria-hidden="true">
                                            <option value="">{{ __('select') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="timetable-empty warning_no_data" role="alert">
                                    <strong>{{ __('no_data_found') }}</strong>
                                </div>
                                <div class="set_timetable timetable-grid"></div>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
