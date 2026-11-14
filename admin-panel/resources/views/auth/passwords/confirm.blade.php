@extends('layouts.school-auth')
@section('title', __('Confirm your password'))
@section('description', __('Confirm your password to continue with this action.'))
@section('content')
    <form method="POST" action="{{ route('password.confirm') }}" class="school-auth__form">@csrf
        <x-page.field name="password" type="password" :label="__('Password')" :restore-old-input="false" required autocomplete="current-password" />
        <button type="submit" class="m3-button m3-button--filled">{{ __('Confirm password') }}</button>
        @if(Route::has('password.request'))<a href="{{ route('password.request') }}">{{ __('forgot_password') }}</a>@endif
    </form>
@endsection
