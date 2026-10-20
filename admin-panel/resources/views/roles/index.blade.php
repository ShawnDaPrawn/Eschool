@extends('layouts.master')

@section('title') {{__('role_management')}} @endsection

@section('content')

<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
             {{__('role_management')}}
        </h3>
        @can('role-create')
            <a class="btn btn-theme" href="{{ route('roles.create') }}">{{ __('create_new_role') }}</a>
        @endcan
    </div>

    <div class="row">
        <div class="col-md-12 grid-margin  stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">
                        {{ __('list') . ' ' . __('roles') }}
                    </h4>
                    <table aria-describedby="mydesc" class='table' id='table_list'
                    data-toggle="table" data-url="{{route('roles.data')}}" data-click-to-select="true" data-search="true"
                    data-side-pagination="server" data-pagination="true"
                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                    data-toolbar="#toolbar" data-show-columns="true"
                    data-show-refresh="true" data-fixed-columns="true"
                    data-trim-on-search="false" data-mobile-responsive="true"
                    data-sort-name="id" data-sort-order="desc"
                    data-maintain-selected="true" data-export-types='["txt","excel"]'
                    data-export-options='{ "fileName": "role-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}' data-escape="true">
                    <thead>
                        <tr>
                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                            <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                            <th scope="col" data-field="name" data-sortable="false">{{__('name')}}</th>
                            @can('role-edit')
                                <th scope="col" data-escape="false" data-field="operate" data-sortable="false">{{__('action')}}</th>
                            @endcan

                        </tr>
                    </thead>
                </table>
                    {!! $roles->render() !!}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
