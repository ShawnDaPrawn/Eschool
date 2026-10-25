@extends('layouts.master')
@section('title', __($title))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__($title)" />
    <div role="alert"><p>{{ $message }}</p><p>{{ __('Your changes were not applied. Compare the current record before trying again.') }}</p></div>
    <dl>@foreach ($draft as $field)<dt>{{ __($field['label']) }}</dt><dd style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $field['value'] }}</dd>@endforeach</dl>
    <a href="{{ $returnUrl }}">{{ __('Back') }}</a>
</div>
@endsection
