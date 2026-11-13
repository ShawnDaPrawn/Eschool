@extends('layouts.master')
@section('title', __('My school inbox'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('My school inbox')" :subtitle="__('Notices addressed to your account at the selected school.')">
        <x-slot:actions>@if($privateAvailable)<a class="m3-button m3-button--outlined" href="{{ route('panel.inbox.private') }}">{{ __('Private staff notices') }}</a>@endif</x-slot:actions>
    </x-page.header>
    @include('personal-notifications.nav')
    <section class="panel-record-list">
    @forelse($inbox['items'] as $notice)
        <article class="panel-record-card">
            <h2 class="page-card-title"><a href="{{ route('panel.inbox.show', $notice['id']) }}">{{ $notice['title'] }}</a></h2>
            <p>{{ $notice['excerpt'] }}</p>
            <time datetime="{{ $notice['date'] }}">{{ \Carbon\Carbon::parse($notice['date'])->format('d M Y H:i') }}</time>
        </article>
    @empty
        <x-page.empty-state :title="__('No notices yet')" :message="__('Your school notices will appear here when they are available to your account.')" />
    @endforelse
    </section>
    @include('personal-notifications.pagination')
</div>
@endsection
