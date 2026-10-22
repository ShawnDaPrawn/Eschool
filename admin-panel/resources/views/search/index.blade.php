@extends('layouts.master')

@section('title')
    {{ __('search_title') }}
@endsection

{{-- Global search results (SearchController), on the page pattern and the Material 3 library. --}}
@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('search_title')" :subtitle="__('search_subtitle')" :breadcrumbs="false" />

        <form class="m3-card m3-card--outlined page-section page-search-form" role="search" method="GET" action="{{ route('search') }}">
            <div class="m3-card__content">
                <label for="search-page-input" class="page-card-title">{{ __('search_label') }}</label>
                <div class="m3-row m3-row--nowrap m3-gap-3 m3-items-center page-search-row">
                    <div class="m3-search">
                        <input id="search-page-input" type="search" name="q" class="m3-search__input" value="{{ $term }}"
                            maxlength="{{ \App\Services\Panel\GlobalSearch::MAX_LENGTH }}" autocomplete="off"
                            placeholder="{{ __('search_placeholder') }}">
                    </div>
                    <button type="submit" class="m3-button m3-button--filled">{{ __('search_button') }}</button>
                </div>
            </div>
        </form>

        @if ($term === '' || $tooShort)
            <x-page.empty-state icon="search" :title="__('search_start_title')" :message="__('search_start_hint')" class="m3-card m3-card--outlined" />
        @elseif ($groups === [])
            <x-page.empty-state icon="search" :title="__('search_nothing_title', ['term' => $term])" :message="__('search_nothing_hint')" class="m3-card m3-card--outlined" />
        @else
            <p class="m3-visually-hidden" role="status">{{ trans_choice('search_results_count', array_sum(array_map(fn ($g) => count($g['items']), $groups)), ['count' => array_sum(array_map(fn ($g) => count($g['items']), $groups))]) }}</p>
            @foreach ($groups as $group)
                <section class="m3-card m3-card--outlined page-section search-group" aria-labelledby="search-group-{{ $group['key'] }}" data-search-group="{{ $group['key'] }}">
                    <h2 class="page-section-title search-group__title" id="search-group-{{ $group['key'] }}">{{ $group['label'] }}</h2>
                    <ul class="m3-list m3-list--divided search-results">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a class="m3-list__item m3-list__item--interactive search-result" href="{{ $item['url'] }}" data-search-result>
                                    <span class="m3-list__content">
                                        <span class="m3-list__headline search-result-title">{{ $item['title'] }}</span>
                                        @if ($item['subtitle'])
                                            <span class="m3-list__supporting search-result-subtitle">{{ $item['subtitle'] }}</span>
                                        @endif
                                    </span>
                                    <x-icon name="chevron-right" :size="20" class="m3-list__trailing search-result-chevron" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
@endsection
