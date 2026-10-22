@extends('layouts.master')

@section('title')
    {{ __('create_new_role') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('/assets/css/roles.css') }}?v={{ filemtime(public_path('assets/css/roles.css')) }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('create_new_role') }}
            </h3>
            <a class="btn btn-sm btn-secondary" href="{{ route('roles.index') }}">{{ __('back') }}</a>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {!! Form::open(['route' => 'roles.store', 'method' => 'POST']) !!}
                        <div class="row">
                            <div class="col-xs-12 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label><strong>{{ __('name') }}:</strong></label>
                                    {!! Form::text('name', null, ['placeholder' => __('name'), 'class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label><strong>{{ __('permission') }}:</strong></label>
                                    @include('roles._permissions', [
                                        'permissionGroups' => $permissionGroups,
                                    ])
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-theme">{{ __('submit') }}</button>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('/assets/js/roles-permissions.js') }}?v={{ filemtime(public_path('assets/js/roles-permissions.js')) }}"></script>
@endsection
