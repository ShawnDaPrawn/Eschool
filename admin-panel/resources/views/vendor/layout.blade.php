<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', __('Vendor counter')) · NavigateEd</title>
<link rel="stylesheet" href="{{ asset('assets/m3/m3.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-counter.css') }}"></head>
<body class="m3" data-vendor-private><a class="m3-skip-link" href="#vendor-main">{{ __('skip_to_content') }}</a>
<header class="vendor-shell"><strong>NavigateEd · {{ __('Vendor counter') }}</strong>
@if(isset($profile))<p>{{ $profile['school_name'] }} · {{ $profile['name'] }}</p><form method="post" action="{{ route('vendor.sign-out') }}">@csrf<input type="hidden" name="expected_account_id" value="{{ $profile['user_id'] }}"><input type="hidden" name="expected_school_id" value="{{ $profile['school_id'] }}"><button type="submit" class="m3-button m3-button--outlined">{{ __('Sign out') }}</button></form>@endif</header>
<main id="vendor-main" class="vendor-shell" tabindex="-1">@yield('content')</main>
<script src="{{ asset('assets/js/vendor-counter.js') }}" defer></script>
</body></html>
