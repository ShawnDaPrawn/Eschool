@extends('layouts.master')
@section('title',__('Compare message drafts'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Compare message drafts')" :subtitle="__('The saved message changed. Copy your local text before opening the current draft and comparing it.')" />
    <h2>{{ __('Your unsaved text') }}</h2><p>{{ $submitted['title'] }}</p><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $submitted['markdown'] }}</pre>
    <h2>{{ __('Current saved text') }}</h2><p>{{ $current['title'] }}</p><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $current['markdown'] }}</pre>
    <a class="m3-button m3-button--tonal" href="{{ route('communication-centre.index',['message_id'=>$current['id']]) }}">{{ __('Open current message') }}</a>
</div>
@endsection
