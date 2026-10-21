@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('role') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('/assets/css/roles.css') }}?v={{ filemtime(public_path('assets/css/roles.css')) }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('role') }}
            </h3>
            <a class="btn btn-sm btn-secondary" href="{{ route('roles.index') }}">{{ __('back') }}</a>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('roles.update', $role->id) }}">
                            @csrf
                            @method('PATCH')
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-6">
                                    <div class="form-group">
                                        <label><strong> {{ __('name') }}:</strong></label>
                                        <input type="text" name="name" value="{{ old('name', $role->name) }}" placeholder="{{ __('name') }}" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label><strong>{{ __('permission') }}:</strong></label>
                                        @include('roles._permissions', [
                                            'permissionGroups' => $permissionGroups,
                                            'selectedPermissions' => array_values($rolePermissions),
                                        ])
                                    </div>
                                </div>
                                <div class="col-12">
                                    <input type="submit" class="btn btn-theme" value="{{ __('submit') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('/assets/js/roles-permissions.js') }}?v={{ filemtime(public_path('assets/js/roles-permissions.js')) }}"></script>
@endsection
