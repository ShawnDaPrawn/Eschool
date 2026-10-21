@extends('web.master')

@section('title')
    {{ __('videos') }}
@endsection
@section('content')
    @include('web.partials.banner', [
        'title' => __('videos'),
        'crumbs' => [[__('home'), url('/')], [__('gallery'), null], [__('videos'), null]],
    ])

    @if ($video)
        @php
            $videos = $videos ?? collect();
        @endphp
        <section class="m3-section site-gallery">
            <div class="m3-page m3-stack m3-gap-8">
                @include('web.partials.section-head', [
                    'tag' => isset($video->tag) ? $video->tag : 'Our Videos',
                    'heading' => isset($video->heading) ? $video->heading : 'Rewind, Replay, Rejoice! Dive into Our Video Vault',
                    'content' => isset($video->content) ? $video->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                {{-- The first eight videos show; "Load more" reveals the rest. Each plays in the video dialog. --}}
                <ul class="m3-grid site-gallery__grid" id="videoContainer">
                    @foreach ($videos->values() as $index => $item)
                        <li class="m3-col-12 m3-col-medium-6 m3-col-large-3" @if ($index >= 8) hidden data-site-more @endif>
                            @include('web.partials.video-thumb', ['item' => $item, 'number' => $index + 1])
                        </li>
                    @endforeach
                </ul>
                @if ($videos->count() > 8)
                    <div class="site-load-more">
                        <button type="button" id="loadMoreVideosBtn" class="m3-button m3-button--filled" data-site-load-more="videoContainer">{{ __('load_more') }}</button>
                    </div>
                @endif
            </div>
            @include('web.partials.video-dialog')
        </section>
    @endif
@endsection
