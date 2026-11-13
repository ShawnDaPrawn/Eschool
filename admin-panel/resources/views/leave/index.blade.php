@extends('layouts.master')
@section('title', __('My leave'))
@section('content')
<div class="content-wrapper m3 page-content" data-leave-workspace data-delete-base="{{ url('leave') }}">
    <x-page.header :title="request('view') === 'create' ? __('Request leave') : __('My leave')"
        :subtitle="__('Review requests, supporting documents and saved decisions for the selected school year.')"
        :primary-label="request('view') !== 'create' ? __('Request leave') : null" :primary-href="route('leave.index', ['view' => 'create'])" />
    @if(request('view') === 'create')
        @include('leave.partials.create-form', ['adminRequest' => false])
    @else
        <x-page.filters id="leave-filters">
            <x-page.field name="filter_upcoming" type="select" :label="__('Date range')" :options="['All' => __('All'), 'Today' => __('Today'), 'Tomorrow' => __('Tomorrow'), 'Upcoming' => __('Upcoming')]" data-filter="table_list" />
            <x-page.field name="month_id" type="select" :label="__('Month')" :options="['' => __('All')] + $months" data-filter="table_list" />
        </x-page.filters>
        <p>{{ __('To change an existing request, remove it where permitted and submit a new request. Final decisions remain read-only.') }}</p>
        <x-page.table id="table_list" :url="route('leave.show', 1)" toolbar="#leave-filters" query-params="leaveWorkspaceParams"
            :empty-title="__('No leave requests')" :empty-message="__('Your leave requests will appear here after submission.')"
            :empty-action-label="__('Request leave')" :empty-action-href="route('leave.index', ['view' => 'create'])">
            <x-slot:columns>
                <th data-field="from_date">{{ __('From date') }}</th><th data-field="to_date">{{ __('To date') }}</th>
                <th data-field="days">{{ __('Days') }}</th><th data-field="reason">{{ __('Reason') }}</th>
                <th data-field="status" data-formatter="leaveWorkspaceStatus">{{ __('Status') }}</th>
                <th data-field="operate" data-formatter="leaveWorkspaceActions" data-events="leaveWorkspaceEvents">{{ __('Actions') }}</th>
            </x-slot:columns>
        </x-page.table>
        @include('leave.partials.record-dialog', ['leaveMode' => 'own'])
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/leave-workspace.js') }}" defer></script>@endsection
