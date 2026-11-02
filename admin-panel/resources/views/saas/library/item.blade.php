@extends('saas.layout')

@section('title', $item['title'])

@section('content')
<p><a href="{{ route('saas.library.frameworks.show', $topic['framework_id']) }}">{{ $topic['framework'] }}</a></p>
<h1>#{{ $item['id'] }} {{ $item['title'] }}</h1>
<p class="saas-lead">{{ $item['kind_label'] }} · {{ $topic['subject_code'] }} · {{ $topic['grade'] }} · {{ $topic['name'] }}</p>
<p>{{ __('Source') }}: {{ $source['title'] }} · {{ $source['licence'] }} · {{ $source['attribution'] }}</p>

<h2>{{ __('Versions') }}</h2>
@foreach ($versions as $version)
    <section class="saas-card" aria-label="{{ __('Version :number', ['number' => $version['number']]) }}">
        <h3>{{ __('Version :number', ['number' => $version['number']]) }} · <span class="saas-badge {{ $version['state'] === 'rejected' ? 'saas-badge--warn' : '' }}">{{ $version['state_label'] }}</span>@if ($version['current']) · {{ __('what learners see') }}@endif</h3>
        <p>{{ __('Written by :name', ['name' => $version['author']]) }}@if ($version['published_at']) · {{ __('published :at by :name', ['at' => $version['published_at'], 'name' => $version['published_by']]) }}@endif</p>
        @include('saas.library.content', ['content' => $version['content']])
        @foreach ($version['reviews'] as $review)
            <p>{{ $review['decision'] === 'approved' ? __('Approved by :name on :at', ['name' => $review['reviewer'], 'at' => $review['at']]) : __('Rejected by :name on :at', ['name' => $review['reviewer'], 'at' => $review['at']]) }}@if ($review['reason']): {{ $review['reason'] }}@endif</p>
        @endforeach
        @if ($version['state'] === 'approved')
            <form method="POST" action="{{ route('saas.library.items.publish', $item['id']) }}">
                @csrf
                <input type="hidden" name="version_id" value="{{ $version['id'] }}">
                <input type="hidden" name="revision" value="{{ $item['revision'] }}">
                <button class="saas-btn" type="submit">{{ __('Publish version :number', ['number' => $version['number']]) }}</button>
            </form>
        @endif
    </section>
@endforeach

@if ($latest['state'] === 'draft')
    <form method="POST" action="{{ route('saas.library.items.submit', $item['id']) }}" class="saas-card">
        @csrf
        <input type="hidden" name="revision" value="{{ $item['revision'] }}">
        <p>{{ __('When the draft is ready, send it to a reviewer for its subject.') }}</p>
        <button class="saas-btn" type="submit">{{ __('Send for review') }}</button>
    </form>
@endif

@if ($latest['state'] === 'in_review')
    <p class="saas-card">{{ __('The latest version is waiting for review. It can change again once a reviewer has decided.') }}</p>
@else
<h2>{{ $latest['state'] === 'draft' ? __('Change the draft') : __('Start a new draft') }}</h2>
<form method="POST" action="{{ route('saas.library.items.draft', $item['id']) }}" class="saas-card" aria-label="{{ __('Draft') }}">
    @csrf @method('PUT')
    <input type="hidden" name="revision" value="{{ $item['revision'] }}">
    @if ($latest['state'] !== 'draft')<p>{{ __('Published and reviewed versions never change: saving starts version :number as a new draft.', ['number' => $versions[0]['number'] + 1]) }}</p>@endif
    <div class="saas-field">
        <label for="draft-title">{{ __('Title') }}</label>
        <input id="draft-title" name="title" required minlength="3" maxlength="191" value="{{ old('title', $item['title']) }}">
    </div>
    @include('saas.library.content-fields', ['kind' => $item['kind'], 'fields' => $fields, 'prefix' => 'draft'])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the draft') }}</button></div>
</form>
@endif
@endsection
