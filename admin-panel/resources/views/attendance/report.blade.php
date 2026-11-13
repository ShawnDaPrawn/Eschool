@extends('layouts.master')
@section('title', __('Attendance report'))
@section('content')
<div class="content-wrapper m3 page-content" data-attendance-workspace="report" data-class-options-url="{{ url('get-class-sections-with-terms') }}" data-class-scope="{{ auth()->user()->hasRole('Teacher') ? 'teacher' : '' }}">
<x-page.header :title="__('Attendance report')" :subtitle="__('Compare recorded attendance across the selected school year and class.')"><x-slot:actions><button type="button" class="m3-button m3-button--outlined" data-attendance-export="{{ url('student-attendance-report') }}">{{ __('Export report CSV') }}</button></x-slot:actions></x-page.header>
<x-page.filters id="attendance-filters"><x-page.field id="filter_class_section_id" name="class_section_id" type="select" :label="__('Class and section')" :options="[]" :placeholder="__('Choose a class')" data-filter="table_list" /></x-page.filters>
<p data-attendance-message role="status"></p>
<x-page.table id="table_list" :url="url('student-attendance-report')" toolbar="#attendance-filters" query-params="attendanceWorkspaceParams" sort-name="id" :caption="__('Attendance report')" :empty-title="__('No attendance records')" :empty-message="__('Choose a class and date range with recorded attendance.')">
<x-slot:columns>

                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false"  data-switchable="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="user_id" data-sortable="true" data-visible="false"  data-switchable="false">
                                            {{ __('user_id') }}</th>
                                        <th scope="col" data-field="student_id" data-escape="false" data-sortable="true"
                                            data-visible="false" data-switchable="false">{{ __('student_id') }}</th>
                                        <th scope="col" data-field="name" data-sortable="false"
                                            data-width="260" data-min-width="240"
                                            >{{ __('student') }}</th>
                                        <th scope="col" data-field="admission_no" data-sortable="true"
                                            data-visible="false">
                                            {{ __('admission_no') }}</th>
                                        <th scope="col" data-field="roll_no" data-sortable="true">{{ __('roll_no') }}
                                        </th>
                                        <th scope="col" data-field="total_days" data-sortable="false">
                                            {{ __('total_days') }}</th>
                                        <th scope="col" data-field="present_days" data-sortable="false">
                                            {{ __('present_days') }}</th>
                                        <th scope="col" data-field="absent_days" data-sortable="false">
                                            {{ __('absent_days') }}</th>
                                        <th scope="col" data-field="percentage" data-sortable="false">
                                            {{ __('percentage') }}</th>

                                </x-slot:columns>
</x-page.table>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/attendance-workspace.js') }}" defer></script>@endsection
