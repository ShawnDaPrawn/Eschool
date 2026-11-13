@extends('layouts.school-auth')
@section('title', __('Verify your email address'))
@section('description', __('Check your email for the verification link before continuing.'))
@section('content')
    @if(session('resent'))<p class="m3-banner" role="status">{{ __('A fresh verification link has been sent to your email address.') }}</p>@endif
    <form method="POST" action="{{ route('verification.resend') }}" class="school-auth__form">@csrf
        <button type="submit" class="m3-button m3-button--filled">{{ __('Send another verification link') }}</button>
    </form>
@endsection
