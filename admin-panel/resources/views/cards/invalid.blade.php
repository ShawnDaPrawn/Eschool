@extends('layouts.master')
@section('title', __('School identity cards'))
@section('content')<main class="content-wrapper m3 page-content"><h1>{{ __('Review the card request') }}</h1><div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div><a href="{{ route('cards-panel.index') }}">{{ __('Return to school cards') }}</a></main>@endsection
