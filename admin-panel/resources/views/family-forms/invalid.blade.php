@extends('layouts.master')
@section('title', __('Family forms'))
@section('content')<div class="content-wrapper m3 page-content"><h1>{{ __('Review the family request') }}</h1><div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div><a href="{{ route('family-forms-panel.index') }}">{{ __('Return to family forms') }}</a></div>@endsection
