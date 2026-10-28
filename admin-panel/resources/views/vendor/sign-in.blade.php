@extends('vendor.layout')
@section('title', __('Vendor sign-in'))
@section('content')
<h1>{{ __('Vendor sign-in') }}</h1>
<p>{{ __('Use the dedicated vendor identity and school code supplied by Finance.') }}</p>
@if($requirements)<p role="status">{{ __('Complete the current password and policy requirements before viewing vendor orders.') }}</p>@endif
<form method="post" action="{{ route('vendor.sign-in') }}" aria-label="{{ __('Vendor sign-in') }}">@csrf
<x-page.field id="vendor-login-school" :restore-old-input="false" name="school" :label="__('School code')" required maxlength="191" autocomplete="organization" />
<x-page.field id="vendor-login-email" :restore-old-input="false" name="email" :label="__('Email or handle')" required maxlength="191" autocomplete="username" />
<x-page.field id="vendor-login-password" :restore-old-input="false" type="password" name="password" :label="__('Password')" required maxlength="128" autocomplete="current-password" />
@if($requirements && $requirements['password_change_required'])
<x-page.field id="vendor-login-new" :restore-old-input="false" type="password" name="new_password" :label="__('Choose a new password')" required minlength="10" maxlength="72" autocomplete="new-password" />
<x-page.field id="vendor-login-confirm" :restore-old-input="false" type="password" name="new_password_confirmation" :label="__('Confirm new password')" required minlength="10" maxlength="72" autocomplete="new-password" />
@endif
@foreach($requirements['documents']??[] as $document)
<section aria-labelledby="vendor-policy-{{ $document['document'] }}"><h2 id="vendor-policy-{{ $document['document'] }}">{{ $document['title'] }}</h2><p class="vendor-policy">{{ $document['text'] }}</p><label><input type="checkbox" name="acceptances[{{ $document['document'] }}]" value="{{ $document['version'] }}" required> {{ __('I read and accept these current terms.') }}</label></section>
@endforeach
<button type="submit" class="m3-button m3-button--filled">{{ __('Sign in to vendor counter') }}</button>
</form>
@endsection
