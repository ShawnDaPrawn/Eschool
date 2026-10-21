@extends('layouts.master')

@section('title')
    {{ __('search_title') }}
@endsection

{{-- Global search results (SearchController), on the page pattern. --}}
@section('content')
    <div class="content-wrapper">
        <x-page.header :title="__('search_title')" :subtitle="__('search_subtitle')" :breadcrumbs="false" />

        <form class="page-search-form card page-section" role="search" method="GET" action="{{ route('search') }}">
            <div class="card-body">
                <label for="search-page-input" class="page-card-title">{{ __('search_label') }}</label>
                <div class="page-search-row">
                    <input id="search-page-input" type="search" name="q" class="form-control" value="{{ $term }}"
                        maxlength="{{ \App\Services\Panel\GlobalSearch::MAX_LENGTH }}" autocomplete="off"
                        placeholder="{{ __('search_placeholder') }}">
                    <button type="submit" class="btn btn-theme">{{ __('search_button') }}</button>
                </div>
            </div>
        </form>

        @if ($term === '' || $tooShort)
            <x-page.empty-state icon="search" :title="__('search_start_title')" :message="__('search_start_hint')" class="card" />
        @elseif ($groups === [])
            <x-page.empty-state icon="search" :title="__('search_nothing_title', ['term' => $term])" :message="__('search_nothing_hint')" class="card" />
        @else
            <p class="sr-only" role="status">{{ trans_choice('search_results_count', array_sum(array_map(fn ($g) => count($g['items']), $groups)), ['count' => array_sum(array_map(fn ($g) => count($g['items']), $groups))]) }}</p>
            @foreach ($groups as $group)
                <section class="card page-section search-group" aria-labelledby="search-group-{{ $group['key'] }}" data-search-group="{{ $group['key'] }}">
                    <div class="card-body">
                        <h2 class="page-section-title" id="search-group-{{ $group['key'] }}">{{ $group['label'] }}</h2>
                        <ul class="search-results">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a class="search-result" href="{{ $item['url'] }}">
                                        <span class="search-result-title">{{ $item['title'] }}</span>
                                        @if ($item['subtitle'])
                                            <span class="search-result-subtitle">{{ $item['subtitle'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection
