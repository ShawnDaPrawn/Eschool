@extends('layouts.master')
@section('title', __('Private staff notices'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Private staff notices')" :subtitle="__('Only currently authorised notices are shown. Sensitive content stays in its original workspace.')" />
    @include('personal-notifications.nav')
    <p>{{ __('Unread') }}: {{ $inbox['unread_count'] }}</p>
    @forelse($inbox['items'] as $notice)
        <article class="panel-record-card">
            <h2 class="page-card-title">{{ $notice['title'] }}</h2>
            <p>{{ $notice['message'] }}</p>
            @if($notice['source'])<a class="m3-button m3-button--outlined" href="{{ $notice['source']['url'] }}">{{ __('Open') }} {{ $notice['source']['label'] }}</a>@endif
            <p>{{ $notice['read_at'] ? __('Read') : __('Unread') }} @if($notice['read_at'])<time datetime="{{ $notice['read_at'] }}">{{ \Carbon\Carbon::parse($notice['read_at'])->format('d M Y H:i') }}</time>@endif</p>
            @if(!$notice['read_at'] && $inbox['writable'] && $notice['writable'])
                <x-page.form-card :id="'read-notice-'.$notice['id']" :action="route('panel.inbox.read', $notice['id'])" :draft="false" :submit-label="__('Mark as read')">
                    @include('personal-notifications.context')
                </x-page.form-card>
            @endif
        </article>
    @empty
        <x-page.empty-state :title="__('No private staff notices')" :message="__('No current private updates are available to your account.')" />
    @endforelse
    @include('personal-notifications.pagination')
</div>
@endsection
