@extends('errors::minimal')

@section('title', __('Your session expired'))
@section('code', '419')
@section('message', __('Your session expired'))
@section('description', __('Sign in in a new tab, then reopen the latest saved record before continuing. Unsaved entries and attachments may need to be entered again.'))

@section('actions')
    <a href="{{ route('login') }}" target="_blank" rel="noopener" class="error-btn error-btn--filled">{{ __('Sign in in a new tab') }}</a>
    <a href="{{ url('/') }}" class="error-btn error-btn--outlined">{{ __('Go Home') }}</a>
@endsection
