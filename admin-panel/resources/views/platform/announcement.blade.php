@extends('layouts.master')

@section('title', $announcement['title'])

@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Announcement from Navigate Ed')" />
    @include('platform.partials.announcement-card', ['announcement' => $announcement])
    <p><a class="m3-button m3-button--text" href="{{ route('platform-announcements.index') }}">{{ __('All announcements from Navigate Ed') }}</a></p>
</div>
@endsection
