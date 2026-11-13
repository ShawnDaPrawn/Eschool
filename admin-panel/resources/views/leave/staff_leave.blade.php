@extends('layouts.master')
@section('title', __('Approved leave history'))
@section('content')
<div class="content-wrapper m3 page-content" data-leave-workspace data-leave-read-only>
    <x-page.header :title="__('Approved leave history')" :subtitle="__('Read approved leave for other school members. These records are shown without decision controls.')" />
    <x-page.filters id="leave-filters">
        <x-page.field name="filter_upcoming" type="select" :label="__('Date range')" :options="['All' => __('All'), 'Today' => __('Today'), 'Tomorrow' => __('Tomorrow'), 'Upcoming' => __('Upcoming')]" data-filter="table_list" />
        <x-page.field name="month_id" type="select" :label="__('Month')" :options="['' => __('All')] + $months" data-filter="table_list" />
    </x-page.filters>
    <x-page.table id="table_list" :url="route('staff-leave.show')" toolbar="#leave-filters" query-params="leaveWorkspaceParams" :empty-title="__('No approved leave')">
        <x-slot:columns>
            <th data-field="name">{{ __('Person') }}</th><th data-field="from_date">{{ __('From date') }}</th><th data-field="to_date">{{ __('To date') }}</th>
            <th data-field="days">{{ __('Days') }}</th><th data-field="status" data-formatter="leaveWorkspaceStatus">{{ __('Status') }}</th>
            <th data-field="operate" data-formatter="leaveWorkspaceActions" data-events="leaveWorkspaceEvents">{{ __('Actions') }}</th>
        </x-slot:columns>
    </x-page.table>
    @include('leave.partials.record-dialog', ['leaveMode' => 'history'])
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/leave-workspace.js') }}?v={{ filemtime(public_path('assets/js/custom/leave-workspace.js')) }}" defer></script>@endsection
