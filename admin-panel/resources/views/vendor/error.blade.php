@extends('vendor.layout')
@section('content')
<h1>{{ __('Review the vendor request') }}</h1><div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>
<p>{{ __('No changed request was applied. Reopen the assigned counter and check the original order before handover.') }}</p><a href="{{ route('vendor.sign-in-form') }}">{{ __('Vendor sign-in') }}</a>
@endsection
