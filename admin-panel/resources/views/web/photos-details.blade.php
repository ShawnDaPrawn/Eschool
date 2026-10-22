@extends('web.master')

@section('title')
    {{ __('photos_details') }}
@endsection
@section('content')
    @include('web.partials.banner', [
        'title' => __('photos_details'),
        'crumbs' => [[__('home'), url('/')], [__('gallery'), null], [__('photos'), route('photo')], [__('photos_details'), null]],
    ])

    @php
        $images = $images ?? collect();
        $total = $images->count();
        $slides = $images->values()->map(fn ($item, $index) => ['src' => $item->file_url, 'alt' => 'Slide ' . $index]);
    @endphp
    <section class="m3-section site-gallery">
        <div class="m3-page m3-stack m3-gap-8">
            {{-- The first eight photos show; "Load more" reveals the rest. Each opens the photo viewer. --}}
            <ul class="m3-grid site-gallery__grid" id="imageContainer">
                @foreach ($images as $index => $item)
                    <li class="m3-col-12 m3-col-medium-6 m3-col-large-3" @if ($index >= 8) hidden data-site-more @endif>
                        <button type="button" class="site-photo" data-photo-index="{{ $index }}" aria-haspopup="dialog"
                            aria-label="{{ __('view') }} {{ $index + 1 }} {{ __('of') }} {{ $total }}">
                            <img class="site-photo__image" src="{{ $item->file_url }}" loading="lazy" alt="">
                        </button>
                    </li>
                @endforeach
            </ul>
            @if ($total > 8)
                <div class="site-load-more">
                    <button type="button" id="loadMoreBtn" class="m3-button m3-button--filled" data-site-load-more="imageContainer">{{ __('load_more') }}</button>
                </div>
            @endif
        </div>

        {{-- Photo viewer: an M3 dialog with previous / next (arrow keys too). --}}
        <dialog class="m3-dialog m3-dialog--wide site-photo-viewer" id="imageModal" aria-labelledby="imageModalLabel">
            <div class="m3-dialog__header">
                <h2 class="m3-dialog__headline" id="imageModalLabel">
                    {{ __('photos_details') }} <span class="site-photo-viewer__count" id="photoViewerCount"></span>
                </h2>
                <button type="button" class="m3-icon-button" data-m3-dialog-close aria-label="{{ __('close') }}">
                    <x-icon name="close" class="m3-icon" />
                </button>
            </div>
            <div class="site-photo-viewer__stage">
                <img class="site-photo-viewer__image" id="photoViewerImage" src="" alt="">
                <button type="button" class="m3-icon-button site-photo-viewer__arrow site-photo-viewer__arrow--prev" data-photo-step="-1" aria-label="{{ __('Previous') }}">
                    <x-icon name="chevron-left" class="m3-icon site-flip-rtl" />
                </button>
                <button type="button" class="m3-icon-button site-photo-viewer__arrow site-photo-viewer__arrow--next" data-photo-step="1" aria-label="{{ __('Next') }}">
                    <x-icon name="chevron-right" class="m3-icon site-flip-rtl" />
                </button>
            </div>
            <script type="application/json" id="photoViewerData">
                @json($slides)
            </script>
        </dialog>
    </section>
@endsection
