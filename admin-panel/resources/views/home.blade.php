@extends('layouts.master')
@section('title')
    {{ __('nav_home') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('/assets/css/dashboard.css') }}?v={{ filemtime(public_path('assets/css/dashboard.css')) }}">
@endsection

{{-- Home: page header, setup checklist and today's numbers (home/top), then the dashboard widgets. --}}
@section('content')
    @if ($is_super_admin)
        @include('dashboard.super-admin')
    @elseif ($is_teacher)
        @include('dashboard.teacher')
    @else
        @include('dashboard.staff')
    @endif
@endsection

@section('script')
    @if ($is_super_admin || $is_teacher || $is_staff)
        <script src="{{ asset('/assets/apexcharts/apexcharts.min.js') }}"></script>
        <script src="{{ asset('/assets/js/custom/dashboard.js') }}?v={{ filemtime(public_path('assets/js/custom/dashboard.js')) }}"></script>
    @endif
@endsection
