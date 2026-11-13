@extends('layouts.master')
@section('title', __('Staff leave requests'))
@section('content')
<div class="content-wrapper m3 page-content" data-leave-workspace data-delete-base="{{ url('leave') }}">
    <x-page.header :title="request('view') === 'create' ? __('Record staff leave') : __('Staff leave requests')"
        :subtitle="__('Review the original request and day portions before approving or rejecting staff leave.')"
        :primary-label="request('view') !== 'create' ? __('Record staff leave') : null" :primary-href="route('leave-request.index', ['view' => 'create'])" />
    @if(request('view') === 'create')
        @include('leave.partials.create-form', ['adminRequest' => true])
    @else
        <x-page.filters id="leave-filters">
            <x-page.field name="filter_upcoming" type="select" :label="__('Date range')" :options="['All' => __('All'), 'Today' => __('Today'), 'Tomorrow' => __('Tomorrow'), 'Upcoming' => __('Upcoming')]" data-filter="table_list" />
            <x-page.field name="month_id" type="select" :label="__('Month')" :options="['' => __('All')] + $months" data-filter="table_list" />
            <x-page.field name="user_id" type="select" :label="__('Staff member')" :options="['' => __('All')] + $users->filter(fn ($person) => $person->user)->mapWithKeys(fn ($person) => [$person->user->id => $person->user->full_name])->all()" data-filter="table_list" />
        </x-page.filters>
        <x-page.table id="table_list" :url="route('leave-request.show')" toolbar="#leave-filters" query-params="leaveWorkspaceParams" :empty-title="__('No staff leave requests')">
            <x-slot:columns>
                <th data-field="name">{{ __('Staff member') }}</th><th data-field="from_date">{{ __('From date') }}</th><th data-field="to_date">{{ __('To date') }}</th>
                <th data-field="days">{{ __('Days') }}</th><th data-field="status" data-formatter="leaveWorkspaceStatus">{{ __('Status') }}</th>
                <th data-field="operate" data-formatter="leaveWorkspaceActions" data-events="leaveWorkspaceEvents">{{ __('Actions') }}</th>
            </x-slot:columns>
        </x-page.table>
        @include('leave.partials.record-dialog', ['leaveMode' => 'staff'])
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/leave-workspace.js') }}" defer></script>@endsection
