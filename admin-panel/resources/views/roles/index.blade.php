@extends('layouts.master')
@section('title', __('role_management'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('School roles')" :subtitle="__('Manage role permissions and review the access they grant in this school.')" :primary-label="__('Create a role by module')" :primary-href="route('role-access.create')" primary-permission="role-create">
        <x-slot:actions>@can('role-create')<a class="m3-button m3-button--outlined" href="{{ route('roles.create') }}">{{ __('Create role with individual permissions') }}</a>@endcan</x-slot:actions>
    </x-page.header>
    <x-page.table :url="route('roles.data')" :empty-title="__('No school roles')" :empty-message="__('Create a role to group the permissions needed for a school job.')" :empty-action-label="Auth::user()->can('role-create') ? __('Create a role by module') : null" :empty-action-href="Auth::user()->can('role-create') ? route('role-access.create') : null">
        <x-slot:columns><th data-field="name" data-sortable="true">{{ __('Role') }}</th><th data-field="operate" data-escape="false">{{ __('Actions') }}</th></x-slot:columns>
    </x-page.table>
</div>
@endsection
