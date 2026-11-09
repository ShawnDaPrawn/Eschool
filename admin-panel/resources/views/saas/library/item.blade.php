@extends('saas.layout')

@section('title', $item['title'])

@section('content')
<p><a href="{{ route('saas.library.frameworks.show', $topic['framework_id']) }}">{{ $topic['framework'] }}</a></p>
<h1>#{{ $item['id'] }} {{ $item['title'] }}</h1>
<p class="saas-lead">{{ $item['kind_label'] }} · {{ $topic['subject_code'] }} · {{ $topic['grade'] }} · {{ $topic['name'] }}</p>
@if ($contribution)
    <p>{{ __('Shared by :teacher at :school on :date. Shown to other schools as: :credit.', ['teacher' => $contribution['teacher'], 'school' => $contribution['school'], 'date' => $contribution['date'], 'credit' => __($contribution['credit'])]) }} {{ __('Licence') }}: {{ $source['licence'] }}</p>
@else
    <p>{{ __('Source') }}: {{ $source['title'] }} · {{ $source['licence'] }} · {{ $source['attribution'] }}</p>
@endif
@if ($item['withdrawn_at'])
    <p class="saas-card"><span class="saas-badge saas-badge--warn">{{ __('Withdrawn by its school on :at', ['at' => $item['withdrawn_at']]) }}</span> {{ __('It is no longer offered to any school and cannot be changed or published again. Practice that used it keeps pointing at its version.') }}</p>
@elseif ($item['unpublished_at'])
    <p class="saas-card"><span class="saas-badge saas-badge--warn">{{ __('Unpublished on :at by :name', ['at' => $item['unpublished_at'], 'name' => $item['unpublished_by']]) }}</span> {{ $item['unpublish_reason'] }}</p>
@endif
@if ($ratings)
    <section class="saas-card" aria-label="{{ __('Teachers\' ratings') }}">
        <h2>{{ __('Teachers\' ratings') }}</h2>
        <p>{{ $ratings['count'] === 0 ? __('No counted ratings yet.') : __(':average out of 5 from :count counted ratings.', ['average' => number_format($ratings['average'], 1), 'count' => $ratings['count']]) }} @if ($ratings['own_school'] > 0){{ __(':count from the sharing school, not counted.', ['count' => $ratings['own_school']]) }}@endif</p>
        @foreach ($ratings['comments'] as $comment)<p>{{ str_repeat('★', $comment['stars']) }} {{ $comment['comment'] }}</p>@endforeach
    </section>
@endif
@if ($signal)
    <p class="saas-card">{{ __('Learners\' answers to the published version: :facility% right, from :learners learners across at least three schools.', ['facility' => $signal['facility'], 'learners' => $signal['learners']]) }}@if ($signal['label']) <span class="saas-badge saas-badge--warn">{{ $signal['label'] }}</span>@endif</p>
@endif

<h2>{{ __('Versions') }}</h2>
@foreach ($versions as $version)
    <section class="saas-card" aria-label="{{ __('Version :number', ['number' => $version['number']]) }}">
        <h3>{{ __('Version :number', ['number' => $version['number']]) }} · <span class="saas-badge {{ $version['state'] === 'rejected' ? 'saas-badge--warn' : '' }}">{{ $version['state_label'] }}</span>@if ($version['current']) · {{ __('what learners see') }}@endif</h3>
        <p>@if ($version['origin'] === 'ai_draft')<span class="saas-badge saas-badge--warn">{{ __('AI draft') }}</span> @endif @if ($version['origin'] === 'contributed')<span class="saas-badge">{{ __('Shared by a school') }}</span> @endif{{ $version['provenance'] }}</p>
        @if ($version['request'])<p>{{ __('Brief') }}: {{ $version['request']['brief'] }}</p>@endif
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

@if ($latest['state'] === 'draft' && ! $item['withdrawn_at'])
    <form method="POST" action="{{ route('saas.library.items.submit', $item['id']) }}" class="saas-card">
        @csrf
        <input type="hidden" name="revision" value="{{ $item['revision'] }}">
        <p>{{ __('When the draft is ready, send it to a reviewer for its subject.') }}</p>
        <button class="saas-btn" type="submit">{{ __('Send for review') }}</button>
    </form>
@endif

@if ($item['withdrawn_at'])
@elseif ($latest['state'] === 'in_review')
    <p class="saas-card">{{ __('The latest version is waiting for review. It can change again once a reviewer has decided.') }}</p>
@else
<h2>{{ $latest['state'] === 'draft' ? __('Change the draft') : __('Start a new draft') }}</h2>
<form method="POST" action="{{ route('saas.library.items.draft', $item['id']) }}" enctype="multipart/form-data" class="saas-card" aria-label="{{ __('Draft') }}">
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
