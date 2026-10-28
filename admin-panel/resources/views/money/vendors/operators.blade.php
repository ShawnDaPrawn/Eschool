@extends('layouts.master')
@section('title', __('Vendor operators'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
<x-page.header :title="__('Vendor operators')" :subtitle="__('Give a dedicated vendor identity access only to its assigned counters.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p role="status">{{ __('Commerce is paused. New identities and access grants are unavailable; existing counter access can still be revoked.') }}</p>@endif
<form method="post" action="{{ route('vendor-operators.provision') }}" aria-label="{{ __('Create dedicated vendor identity') }}" autocomplete="off" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Create dedicated vendor identity') }}</legend>
<p>{{ __('Use a new vendor account. Existing staff and family identities are not converted or given new credentials here.') }}</p>
<x-page.field id="vendor-operator-first" :restore-old-input="false" name="first_name" :label="__('Operator first name')" required maxlength="100" />
<x-page.field id="vendor-operator-last" :restore-old-input="false" name="last_name" :label="__('Operator last name')" required maxlength="100" />
<x-page.field id="vendor-operator-email" :restore-old-input="false" type="email" name="email" :label="__('Operator email address')" required maxlength="191" />
<x-page.field id="vendor-operator-password" :restore-old-input="false" type="password" name="initial_password" :label="__('Temporary operator password')" required minlength="10" maxlength="72" autocomplete="new-password" />
<p>{{ __('The operator must choose a new password and accept current policies before seeing orders. No password is shown in a receipt or saved form.') }}</p>
<label><input type="checkbox" name="confirmed_operator_identity" value="1" required> {{ __('I verified the real operator and will share the temporary credential privately.') }}</label>
<x-m3.button type="submit">{{ __('Create dedicated vendor identity') }}</x-m3.button>
</fieldset></form>
<form method="post" action="{{ route('vendor-operators.bind') }}" aria-label="{{ __('Assign a vendor counter') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Assign a vendor counter') }}</legend>
<x-page.field id="vendor-operator-counter" :restore-old-input="false" type="select" name="vendor_id" :label="__('Vendor counter')" :options="$vendors" :placeholder="__('Choose a vendor')" required />
<x-page.field id="vendor-operator-person" :restore-old-input="false" type="select" name="user_id" :label="__('Dedicated vendor operator')" :options="$operators" :placeholder="__('Choose a vendor identity')" required />
<input type="hidden" name="revision" value="0"><input type="hidden" name="status" value="active">
<x-page.field id="vendor-operator-reason" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private access reason')" required minlength="3" maxlength="2000" />
<label><input type="checkbox" name="confirmed_operator_identity" value="1" required> {{ __('I checked this operator and this specific vendor counter.') }}</label>
<x-m3.button type="submit">{{ __('Assign a vendor counter') }}</x-m3.button>
</fieldset></form>
<h2>{{ __('Retained counter assignments') }}</h2>
@foreach($bindings as $binding)
@php($action=$actions->get($binding->id))
<section class="m3-card m3-card--outlined"><div class="m3-card__content">
<p>{{ $vendors[$binding->vendor_id]??'' }} · {{ $operators[$binding->user_id]??__('Departed operator') }} · {{ __($action?->status??'revoked') }} · {{ __('Revision') }} {{ $action?->revision??0 }}</p>
<form method="post" action="{{ route('vendor-operators.bind') }}" aria-label="{{ __('Change vendor access').' '.$binding->id }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<input type="hidden" name="vendor_id" value="{{ $binding->vendor_id }}"><input type="hidden" name="user_id" value="{{ $binding->user_id }}"><input type="hidden" name="revision" value="{{ $action?->revision??0 }}"><input type="hidden" name="status" value="{{ $action?->status==='active'?'revoked':'active' }}">
<fieldset @disabled(!$canWrite && $action?->status!=='active')><legend>{{ __('Review this counter assignment') }}</legend>
<x-page.field :id="'vendor-operator-change-'.$binding->id" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private access change reason')" required minlength="3" maxlength="2000" />
<label><input type="checkbox" name="confirmed_operator_identity" value="1" required> {{ __('I checked this operator and this specific vendor counter.') }}</label>
<x-m3.button type="submit">{{ $action?->status==='active'?__('Revoke this counter access'):__('Restore this counter access') }}</x-m3.button>
</fieldset></form></div></section>
@endforeach
{{ $bindings->links() }}
<p><a href="{{ route('vendor.sign-in-form') }}">{{ __('Vendor counter sign-in') }}</a></p>
</div>
@endsection
