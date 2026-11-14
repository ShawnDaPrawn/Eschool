@extends('layouts.master')
@section('title', __('create_new_role'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('create_new_role')">
        <x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('roles.index') }}">{{ __('All school roles') }}</a></x-slot:actions>
    </x-page.header>
    @if($errors->any())<div class="m3-banner m3-banner--danger" role="alert">{{ $errors->first() }}</div>@endif
    <section class="m3-card m3-card--outlined panel-record-card"><form method="POST" action="{{ route('roles.store') }}">@csrf
        <x-page.field name="name" :label="__('name')"  required />
        <h2>{{ __('Individual permissions') }}</h2>
        @include('roles._permissions', ['permissionGroups'=>$permissionGroups])
        <div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('roles.index') }}">{{ __('Cancel') }}</a><button type="submit" class="m3-button m3-button--filled">{{ __('Save role') }}</button></div>
    </form></section>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/roles-permissions.js') }}?v={{ filemtime(public_path('assets/js/roles-permissions.js')) }}" defer></script>@endsection
