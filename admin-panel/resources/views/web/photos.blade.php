@extends('web.master')

@section('title')
    {{ __('photos') }}
@endsection
@section('content')
    @include('web.partials.banner', [
        'title' => __('photos'),
        'crumbs' => [[__('home'), url('/')], [__('gallery'), null], [__('photos'), null]],
    ])

    @if ($photo)
        <section class="m3-section site-gallery">
            <div class="m3-page">
                @include('web.partials.section-head', [
                    'tag' => isset($photo->tag) ? $photo->tag : 'Our Photos',
                    'heading' => isset($photo->heading) ? $photo->heading : ' Capturing Memories, Building Dreams',
                    'content' => isset($photo->content) ? $photo->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                <ul class="m3-grid site-gallery__grid">
                    @if (isset($images))
                        @foreach ($images as $image)
                            <li class="m3-col-12 m3-col-medium-6 m3-col-large-3">
                                <a class="site-album" href="{{ route('photo.gallery', $image->id) }}">
                                    <img class="site-album__image" src="{{ $image->thumbnail }}" loading="lazy" alt="">
                                    <span class="site-album__label">
                                        <span>{{ $image->name }}</span>
                                        <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </section>
    @endif
@endsection
