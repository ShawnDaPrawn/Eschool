@extends('layouts.master')
@section('title', __('Mastery configuration changed'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Mastery configuration changed')" :subtitle="__('Your changes were not saved. Compare them with the current graph before editing again.')" />
    @php($topicNames = array_column($current['nodes'] ?? [], 'name', 'id'))
    <div class="m3-grid">
        @foreach([['title' => __('Your submitted prerequisites'), 'links' => $submitted['links'] ?? [], 'revision' => $submitted['expected_revision'] ?? null], ['title' => __('Current prerequisites'), 'links' => $current['links'] ?? [], 'revision' => $current['settings']['revision'] ?? null]] as $comparison)
        <section class="m3-col-12 m3-col-expanded-6 m3-card m3-card--outlined workspace-section"><div class="m3-card__content">
            <h2>{{ $comparison['title'] }}</h2><p>{{ __('Revision') }} {{ $comparison['revision'] }}</p>
            @forelse($comparison['links'] as $edge)
                <article class="workspace-section"><h3>{{ $topicNames[$edge['topic_id']] ?? __('Topic no longer available') }}</h3><dl><dt>{{ __('Prerequisite') }}</dt><dd>{{ $topicNames[$edge['prerequisite_id']] ?? __('Topic no longer available') }}</dd><dt>{{ __('Source citation') }}</dt><dd>{{ $edge['source'] ?? '' }}</dd></dl></article>
            @empty<p>{{ __('No prerequisites have been recorded.') }}</p>@endforelse
        </div></section>
        @endforeach
    </div>
    <a class="m3-button m3-button--filled" href="{{ route('mastery.index', ['subject_id' => $current['subject_id']]) }}">{{ __('Reload current graph') }}</a>
</div>
@endsection
