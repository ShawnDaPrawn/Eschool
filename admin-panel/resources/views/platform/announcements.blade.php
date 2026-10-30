@extends('layouts.master')

@section('title', __('Announcements from Navigate Ed'))

@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Announcements from Navigate Ed')" />
    <p>{{ __('Release notes, maintenance windows and policy changes Navigate Ed sent to this school. Only the school\'s Super Admins see them.') }}</p>
    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif
    @forelse ($announcements as $announcement)
        @include('platform.partials.announcement-card', ['announcement' => $announcement])
    @empty
        <p>{{ __('No announcements from Navigate Ed.') }}</p>
    @endforelse
    @if ($announcements->hasPages())
        <nav aria-label="{{ __('Announcement pages') }}">
            @if ($announcements->previousPageUrl())<a class="m3-button m3-button--text" href="{{ $announcements->previousPageUrl() }}">{{ __('Newer') }}</a>@endif
            @if ($announcements->nextPageUrl())<a class="m3-button m3-button--text" href="{{ $announcements->nextPageUrl() }}">{{ __('Older') }}</a>@endif
        </nav>
    @endif
</div>
@endsection
