@extends('layouts.master')
@section('title', __('Daily attendance records'))
@section('content')
<div class="content-wrapper m3 page-content" data-attendance-workspace="records" data-class-options-url="{{ url('get-class-sections-with-terms') }}" data-class-scope="{{ auth()->user()->hasRole('Teacher') ? 'teacher' : '' }}">
<x-page.header :title="__('Daily attendance records')" :subtitle="__('Choose a class and date to review recorded learner attendance.')" />
<x-page.filters id="attendance-filters"><x-page.field id="timetable_class_section" name="class_section_id" type="select" :label="__('Class and section')" :options="[]" :placeholder="__('Choose a class')" data-filter="table_list" />
<x-page.field id="attendance-date" name="date" type="date" :label="__('Date')" data-filter="table_list" />
<x-page.field id="attendance-type" name="attendance_type" type="select" :label="__('Status')" :options="['1'=>__('Present'),'0'=>__('Absent'),'3'=>__('Holiday'),'4'=>__('First half absent'),'5'=>__('Second half absent')]" :placeholder="__('All statuses')" data-filter="table_list" /></x-page.filters>
<p data-attendance-message role="status"></p>
<x-page.table id="table_list" :url="url('student-attendance-list')" toolbar="#attendance-filters" query-params="attendanceWorkspaceParams" sort-name="student_id" :caption="__('Daily attendance records')" :empty-title="__('No attendance records')" :empty-message="__('Choose a class and date range with recorded attendance.')">
<x-slot:columns>

                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}
                                    </th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="user_id" data-sortable="true" data-visible="false">
                                        {{ __('user_id') }}
                                    </th>
                                    <th scope="col" data-field="student_id" data-sortable="true"
                                        data-visible="false">{{ __('student_id') }}</th>
                                    <th scope="col" data-field="admission_no" data-sortable="true">
                                        {{ __('admission_no') }}
                                    </th>
                                    <th scope="col" data-field="roll_no" data-sortable="true">{{ __('roll_no') }}
                                    </th>
                                    <th scope="col" data-field="name" data-sortable="false">{{ __('name') }}
                                    </th>
                                    <th scope="col" data-escape="false" data-field="type" data-sortable="false">
                                        {{ __('type') }}
                                    </th>
                                    {{-- <th scope="col" data-field="note" data-sortable="false">{{__('note')}}</th> --}}

                            </x-slot:columns>
</x-page.table>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/attendance-workspace.js') }}" defer></script>@endsection
