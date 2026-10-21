@extends('errors::minimal')

@section('title', __('403 - Forbidden'))
@section('code', '403')
@section('message', __('System Access Denied'))
@section('description', $exception->getMessage() ?: __('You do not have permission to access this page.'))

@section('actions')
    <a href="{{ url('/') }}" class="error-btn error-btn--filled">{{ __('Refresh Page') }}</a>
    <a href="#" onclick="window.history.back(); return false;" class="error-btn error-btn--outlined">{{ __('Go Back') }}</a>
@endsection
