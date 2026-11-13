@extends('layouts.master')
@section('title', __('Leave configuration'))
@section('content')
<div class="content-wrapper m3 page-content" data-leave-workspace data-leave-configuration data-delete-base="{{ url('leave-master') }}">
    <x-page.header :title="__('Leave configuration')" :subtitle="__('Set the monthly allowance and non-working days for a school year.')"
        :primary-label="request('view') !== 'create' ? __('Add year configuration') : null" :primary-href="route('leave-master.index', ['view' => 'create'])" />
    @if(request('view') === 'create')
        <x-page.form-card id="leave-configuration-create" :action="route('leave-master.store')" :title="__('Monthly allowance')" :redirect="route('leave-master.index')" :cancel-href="route('leave-master.index')">
            @include('leave.partials.configuration-fields', ['prefix' => 'create'])
        </x-page.form-card>
    @else
        <x-page.table id="table_list" :url="route('leave-master.show', 1)" query-params="leaveWorkspaceParams" :empty-title="__('No leave configuration for this year')"
            :empty-action-label="__('Add year configuration')" :empty-action-href="route('leave-master.index', ['view' => 'create'])">
            <x-slot:columns>
                <th data-field="session_year">{{ __('School year') }}</th><th data-field="total_leave">{{ __('Days per month') }}</th><th data-field="holiday_days">{{ __('Non-working days') }}</th>
                <th data-field="operate" data-formatter="leaveWorkspaceActions" data-events="leaveWorkspaceEvents">{{ __('Actions') }}</th>
            </x-slot:columns>
        </x-page.table>
        <dialog class="m3-dialog" id="leave-configuration-dialog" aria-labelledby="leave-configuration-edit-title">
            <h2 class="m3-dialog__headline" id="leave-configuration-edit-title">{{ __('Edit leave configuration') }}</h2>
            <div class="m3-dialog__content">
                <x-page.form-card id="leave-configuration-edit" :action="route('leave-master.update', 1)" method="PUT" :draft="false" refresh-table="table_list">
                    <input type="hidden" name="edit_id">
                    @include('leave.partials.configuration-fields', ['prefix' => 'edit'])
                </x-page.form-card>
            </div>
            <div class="m3-dialog__actions"><button type="button" class="m3-button m3-button--text" data-m3-dialog-close>{{ __('Cancel') }}</button></div>
        </dialog>
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/leave-workspace.js') }}" defer></script>@endsection
