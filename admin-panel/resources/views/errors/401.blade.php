@extends('errors::minimal')

@section('title', __('Sign in to continue'))
@section('code', '401')
@section('message', __('Sign in to continue'))
@section('description', __('Your session is no longer available. Sign in, then return to the page you were working on.'))

@section('actions')
    <a href="{{ route('login') }}" target="_blank" rel="noopener" class="error-btn error-btn--filled">{{ __('Sign in in a new tab') }}</a>
    <a href="{{ url('/') }}" class="error-btn error-btn--outlined">{{ __('Go Home') }}</a>
@endsection
