@extends('layouts.master')
@section('title', __('Facilitator leave'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Facilitator leave')" :subtitle="__('Dated facilitator absences and temporary activity session cover.')"><x-slot:actions>@if($canRequest)<a class="m3-button m3-button--outlined" href="{{ route('facilitator-leave.cover') }}">{{ __('My assigned session cover') }}</a><a class="m3-button m3-button--filled" href="{{ route('facilitator-leave.create') }}">{{ __('Request facilitator leave') }}</a>@endif</x-slot:actions></x-page.header>
<p>{{ __('Viewing school year') }}: {{ $year->name }}</p>
<form method="GET" class="page-filters"><x-page.field name="status" type="select" :label="__('Decision state')" :value="request('status','')" :options="['pending'=>__('Pending'),'approved'=>__('Approved'),'rejected'=>__('Rejected')]" :placeholder="__('All decisions')" /><button type="submit" class="m3-button m3-button--outlined">{{ __('Apply filters') }}</button></form>
<div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('Facilitator leave requests') }}"><table class="page-table__table"><thead><tr><th>{{ __('Facilitator') }}</th><th>{{ __('Dates') }}</th><th>{{ __('Decision') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $names->get($row->user_id)?->full_name ?? __('Former facilitator') }}</td><td>{{ $row->from_date->toDateString() }} – {{ $row->to_date->toDateString() }}</td><td>{{ __(ucfirst($row->status)) }}</td><td><a class="m3-button m3-button--text" href="{{ route('facilitator-leave.show',$row->id) }}">{{ __('View request') }}</a></td></tr>@empty<tr><td colspan="4">{{ __('No facilitator leave requests match these filters.') }}</td></tr>@endforelse
</tbody></table></div>
{{ $rows->links() }}
</div>
@endsection
