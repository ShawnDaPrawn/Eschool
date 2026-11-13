@extends('layouts.master')
@section('title', __('Monthly leave balances'))
@section('content')
<div class="content-wrapper m3 page-content" data-leave-workspace>
    <x-page.header :title="__('Monthly leave balances')" :subtitle="__('All twelve months of the selected school year. CL is casual leave; LWP is leave without pay.')" />
    <x-page.filters id="leave-filters">
        @if($users)
            <x-page.field name="staff_id" type="select" :label="__('Staff member')" :options="$users->filter(fn ($person) => $person->user)->mapWithKeys(fn ($person) => [$person->user->id => $person->user->full_name])->all()" data-filter="table_list" />
        @endif
    </x-page.filters>
    <p>{{ __('A dash means the report has no recorded usage or remaining amount for that month. It is not a calculated available balance.') }}</p>
    <x-page.table id="table_list" :url="route('leave-details-list')" toolbar="#leave-filters" query-params="leaveWorkspaceParams"
        :search="false" :pagination="false" :page-size="12" :caption="__('Monthly leave balances for the selected staff member')">
        <x-slot:columns>
            <th data-field="month">{{ __('Month') }}</th><th data-field="allocated">{{ __('Allocated') }}</th>
            <th data-field="used_cl">{{ __('Used CL') }}</th><th data-field="lwp">{{ __('Used LWP') }}</th><th data-field="total">{{ __('Total used') }}</th>
            <th data-field="remaining_cl">{{ __('Remaining CL') }}</th><th data-field="remaining_total">{{ __('Remaining total') }}</th>
        </x-slot:columns>
    </x-page.table>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/leave-workspace.js') }}" defer></script>@endsection
