@extends('layouts.master')

@section('title')
    {{ __('show_role') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('/assets/css/roles.css') }}?v={{ filemtime(public_path('assets/css/roles.css')) }}">
@endsection

@section('content')

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('show_role') }}
            </h3>
            <div class="d-flex" style="gap: 8px;">
                @can('role-edit')
                    <a class="btn btn-sm btn-theme" href="{{ route('roles.edit', $role->id) }}">{{ __('edit') }}</a>
                @endcan
                <a class="btn btn-sm btn-theme" href="{{ route('roles.index') }}">{{ __('back') }}</a>
            </div>
        </div>

        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <div class="role-permissions__summary">
                            <div>
                                <span class="role-permissions__role-name">{{ $role->name }}</span>
                                <span class="role-permissions__role-sub">{{ __('role') }}</span>
                            </div>
                            <span class="role-permissions__total">
                                {{ $rolePermissions->count() }} {{ __('permission') }}
                            </span>
                        </div>

                        @if (!empty($permissionGroups))
                            <div class="role-permissions__grid">
                                @foreach ($permissionGroups as $group)
                                    <div class="role-permissions__card">
                                        <div class="role-permissions__card-header">
                                            <span class="role-permissions__card-title">{{ $group['title'] }}</span>
                                            <span class="role-permissions__card-count">{{ count($group['items']) }}</span>
                                        </div>
                                        <div class="role-permissions__card-body">
                                            @foreach ($group['items'] as $item)
                                                <span class="role-permissions__badge">{{ $item['action_label'] }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="role-permissions__empty">
                                {{ __('no_data_found') }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
