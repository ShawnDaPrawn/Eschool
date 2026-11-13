@extends('layouts.master')
@section('title', __('Learner leave requests'))
@section('content')
<div class="content-wrapper m3 page-content" data-leave-workspace data-delete-base="{{ url('leave') }}">
    <x-page.header :title="__('Learner leave requests')" :subtitle="__('Read the original request, supporting documents and each day before deciding.')" />
    <x-page.filters id="leave-filters">
        <x-page.field name="filter_upcoming" type="select" :label="__('Date range')" :options="['All' => __('All'), 'Today' => __('Today'), 'Tomorrow' => __('Tomorrow'), 'Upcoming' => __('Upcoming')]" data-filter="table_list" />
        <x-page.field name="month_id" type="select" :label="__('Month')" :options="['' => __('All')] + $months" data-filter="table_list" />
        <x-page.field name="class_id" type="select" :label="__('Homeroom')" :options="['' => __('All')] + $class_sections->pluck('full_name', 'id')->all()" data-filter="table_list" />
    </x-page.filters>
    <x-page.table id="table_list" :url="route('student-leave-request.show')" toolbar="#leave-filters" query-params="leaveWorkspaceParams" :empty-title="__('No learner leave requests')">
        <x-slot:columns>
            <th data-field="name">{{ __('Learner') }}</th><th data-field="from_date">{{ __('From date') }}</th><th data-field="to_date">{{ __('To date') }}</th>
            <th data-field="days">{{ __('Days') }}</th><th data-field="status" data-formatter="leaveWorkspaceStatus">{{ __('Status') }}</th>
            <th data-field="operate" data-formatter="leaveWorkspaceActions" data-events="leaveWorkspaceEvents">{{ __('Actions') }}</th>
        </x-slot:columns>
    </x-page.table>
    @include('leave.partials.record-dialog', ['leaveMode' => 'learner'])
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/leave-workspace.js') }}" defer></script>@endsection
