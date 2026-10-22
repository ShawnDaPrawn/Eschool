@extends('web.master')

@section('content')
    {{-- Hero: the school's sliders (Owl Carousel; script.js styles its arrows as M3 icon buttons). --}}
    <section class="site-hero">
        <h1 class="m3-visually-hidden">{{ config('app.name') }}</h1>
        <div class="owl-carousel site-hero__carousel" data-site-carousel="hero">
            @if ($sliders->isEmpty())
                <div class="site-hero__slide">
                    <img src="{{ url('assets/images/heroImg1.png') }}" alt="" class="site-hero__image" />
                </div>
                <div class="site-hero__slide">
                    <img src="{{ url('assets/images/heroImg2.png') }}" alt="" class="site-hero__image" />
                </div>
                <div class="site-hero__slide">
                    <img src="{{ url('assets/images/heroImg3.png') }}" alt="" class="site-hero__image" />
                </div>
                <div class="site-hero__slide">
                    <img src="{{ url('assets/images/heroImg4.png') }}" alt="" class="site-hero__image" />
                </div>
            @else
                @foreach ($sliders as $slider)
                    <div class="site-hero__slide">
                        <img src="{{ url($slider->image) }}" alt="" class="site-hero__image">
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    @if ($about)
        <section class="m3-section site-about">
            <div class="m3-page m3-grid m3-gap-8">
                <div class="m3-col-12 m3-col-expanded-6">
                    <img class="site-about__image" src="{{ isset($about->image) ? $about->image : url('assets/images/dummyImg.png') }}" alt="">
                </div>
                <div class="m3-col-12 m3-col-expanded-6 m3-stack m3-gap-6 site-about__content">
                    @include('web.partials.section-head', [
                        'tag' => isset($about->tag) ? $about->tag : '',
                        'heading' => isset($about->heading) ? $about->heading : '',
                        'content' => isset($about->content) ? $about->content : '',
                        'align' => 'start',
                    ])
                    <div>
                        <a class="m3-button m3-button--filled m3-button--icon-trailing" href="{{ route('about.us') }}">
                            {{ __('read_more') }}
                            <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($program)
        <section class="m3-section m3-surface-container-low site-programs">
            <div class="m3-page">
                <img src="{{ url('assets/images/notes.svg') }}" class="site-programs__notes" alt="notesImg">
                <img src="{{ url('assets/images/bulbImg.svg') }}" class="site-programs__bulb" alt="bulbImg">
                @include('web.partials.section-head', [
                    'tag' => isset($program->tag) ? $program->tag : 'Educational Programs',
                    'heading' => isset($program->heading) ? $program->heading : 'Educational Programs for every Stage',
                    'content' => isset($program->content) ? $program->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                <ul class="m3-grid site-programs__grid">
                    @if (isset($eprograms))
                        @foreach ($eprograms as $item)
                            <li class="m3-col-12 m3-col-medium-6 m3-col-large-3">
                                <article class="m3-card m3-card--outlined site-program">
                                    <img class="m3-card__media site-program__image" src="{{ $item->image }}" alt="...">
                                    <h3 class="m3-card__content m3-card__headline">{{ $item->title }}</h3>
                                </article>
                            </li>
                        @endforeach
                    @else
                        @foreach (['Pre-primary School', 'Primary School', 'Secondary School', 'Higher Secondary School'] as $title)
                            <li class="m3-col-12 m3-col-medium-6 m3-col-large-3">
                                <article class="m3-card m3-card--outlined site-program">
                                    <img class="m3-card__media site-program__image" src="{{ url('assets/images/programImg.png') }}" alt="...">
                                    <h3 class="m3-card__content m3-card__headline">{{ $title }}</h3>
                                </article>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </section>
    @endif

    @if ($event)
        <section class="m3-section site-events">
            <div class="m3-page">
                @include('web.partials.section-head', [
                    'tag' => isset($event->tag) ? $event->tag : 'Our Events and News',
                    'heading' => isset($event->heading) ? $event->heading : "Don't Miss the Biggest Events and News of the Year!",
                    'content' => isset($event->content) ? $event->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                <ul class="m3-grid site-events__grid">
                    @if (isset($news))
                        @foreach ($news as $item)
                            @php
                                if ($item->date) {
                                    $when = date('d M, Y', strtotime($item->date));
                                } elseif ($item->end_date) {
                                    $when = date('d M, Y', strtotime($item->start_date)) . '  To  ' . date('d M, Y', strtotime($item->end_date));
                                } else {
                                    $when = date('d M, Y', strtotime($item->start_date));
                                }
                            @endphp
                            <li class="m3-col-12 m3-col-expanded-6">
                                <article class="m3-card m3-card--outlined site-event" data-id="{{ $item->id }}">
                                    <div class="m3-card__content site-event__content">
                                        <h3 class="m3-card__headline site-event__title">{{ $item->title }}</h3>
                                        <hr class="m3-divider">
                                        <span class="m3-chip m3-chip--tonal m3-chip--with-icon site-event__date">
                                            <x-icon name="calendar" class="m3-icon" /><bdi>{{ $when }}</bdi>
                                        </span>
                                        <p class="m3-card__supporting site-event__description">{{ $item->description }}</p>
                                    </div>
                                    <div class="m3-card__actions m3-card__actions--start">
                                        <button type="button" class="m3-button m3-button--text site-event__more"
                                            data-event-title="{{ $item->title }}"
                                            data-event-date="{{ $when }}"
                                            data-event-description="{{ $item->description }}"
                                            data-event-image="{{ $item->image }}"
                                            data-event-programme="{{ $item->multipleEvent }}"
                                            aria-haspopup="dialog">{{ __('view_details') }}</button>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>

            {{-- Event details: filled in by script.js from the card's data-event-* attributes. --}}
            <dialog class="m3-dialog m3-dialog--fullscreen-compact site-event-dialog" id="eventModal" aria-labelledby="fullEventTitle">
                <div class="m3-dialog__header">
                    <h2 class="m3-dialog__headline" id="fullEventTitle"></h2>
                    <button type="button" class="m3-icon-button" data-m3-dialog-close aria-label="{{ __('close') }}">
                        <x-icon name="close" class="m3-icon" />
                    </button>
                </div>
                <div class="m3-dialog__content site-event-dialog__content">
                    <span class="m3-chip m3-chip--tonal m3-chip--with-icon">
                        <x-icon name="calendar" class="m3-icon" /><bdi id="fullEventDate"></bdi>
                    </span>
                    <img class="site-event-dialog__image" id="eventImage" src="" alt="image" hidden />
                    <p class="site-event-dialog__description" id="fullEventDescription"></p>
                    <div class="site-programme" id="multiEventDetails"></div>
                </div>
            </dialog>
            <template id="eventProgrammeItem">
                <h3 class="site-programme__day"></h3>
                <div class="site-programme__item">
                    <p class="site-programme__time"></p>
                    <div class="site-programme__body">
                        <h4 class="site-programme__title"></h4>
                        <p class="site-programme__description"></p>
                        <p class="site-programme__date"><x-icon name="calendar" class="m3-icon" /><span></span></p>
                    </div>
                </div>
            </template>
        </section>
    @endif

    @if ($photo)
        <section class="m3-section m3-surface-container-low site-gallery-strip">
            <div class="m3-page">
                <div class="site-gallery-strip__head">
                    @include('web.partials.section-head', [
                        'tag' => isset($photo->tag) ? $photo->tag : 'Our Photos',
                        'heading' => isset($photo->heading) ? $photo->heading : ' Capturing Memories, Building Dreams',
                        'content' => isset($photo->content) ? $photo->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                        'align' => 'start',
                    ])
                    <a class="m3-button m3-button--filled m3-button--icon-trailing" href="{{ route('photo') }}">
                        {{ __('view_all') }}
                        <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                    </a>
                </div>
                <div class="site-slider">
                    <div class="owl-carousel site-slider__track" data-site-carousel="strip">
                        @if (isset($images))
                            @foreach ($images as $image)
                                <a class="site-album" href="{{ route('photo.gallery', $image->id) }}">
                                    <img class="site-album__image" src="{{ $image->thumbnail }}" alt="">
                                    <span class="site-album__label">
                                        <span>{{ $image->name }}</span>
                                        <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                                    </span>
                                </a>
                            @endforeach
                        @endif
                    </div>
                    <div class="site-slider__nav">
                        <button type="button" class="m3-icon-button m3-icon-button--outlined" data-site-carousel-prev aria-label="{{ __('Previous') }}">
                            <x-icon name="arrow-left" class="m3-icon site-flip-rtl" />
                        </button>
                        <button type="button" class="m3-icon-button m3-icon-button--outlined" data-site-carousel-next aria-label="{{ __('Next') }}">
                            <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($video)
        <section class="m3-section site-gallery-strip">
            <div class="m3-page">
                <div class="site-gallery-strip__head">
                    @include('web.partials.section-head', [
                        'tag' => isset($video->tag) ? $video->tag : 'Our Videos',
                        'heading' => isset($video->heading) ? $video->heading : 'Rewind, Replay, Rejoice! Dive into Our Video Vault',
                        'content' => isset($video->content) ? $video->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                        'align' => 'start',
                    ])
                    <a class="m3-button m3-button--filled m3-button--icon-trailing" href="{{ route('video') }}">
                        {{ __('view_all') }}
                        <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                    </a>
                </div>
                <div class="site-slider">
                    <div class="owl-carousel site-slider__track" data-site-carousel="strip">
                        @if (isset($videos))
                            @foreach ($videos as $index => $item)
                                @include('web.partials.video-thumb', ['item' => $item, 'number' => $index + 1])
                            @endforeach
                        @endif
                    </div>
                    <div class="site-slider__nav">
                        <button type="button" class="m3-icon-button m3-icon-button--outlined" data-site-carousel-prev aria-label="{{ __('Previous') }}">
                            <x-icon name="arrow-left" class="m3-icon site-flip-rtl" />
                        </button>
                        <button type="button" class="m3-icon-button m3-icon-button--outlined" data-site-carousel-next aria-label="{{ __('Next') }}">
                            <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                        </button>
                    </div>
                </div>
            </div>
            @include('web.partials.video-dialog')
        </section>
    @endif

    @if ($faq)
        <section class="m3-section m3-surface-container-low site-faqs">
            <div class="m3-page">
                @include('web.partials.section-head', [
                    'tag' => isset($faq->tag) ? $faq->tag : "FAQ'S",
                    'heading' => isset($faq->heading) ? $faq->heading : "Got Questions? We've Got Answers! Dive into Our FAQs",
                    'content' => isset($faq->content) ? $faq->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                <div class="m3-card m3-card--outlined site-faqs__list" id="accordionExample">
                    @if (isset($faqs))
                        @foreach ($faqs as $item)
                            <details class="m3-expansion" name="site-faqs" id="faq-{{ $item->id }}">
                                <summary class="m3-expansion__header">{{ $item->id }}. {{ $item->question }}</summary>
                                <div class="m3-expansion__content">
                                    <p>{{ $item->answer }}</p>
                                </div>
                            </details>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($app)
        <section class="m3-section site-apps">
            <div class="m3-page">
                @include('web.partials.section-head', [
                    'tag' => isset($app->tag) ? $app->tag : 'Download Our School Apps!',
                    'heading' => isset($app->heading) ? $app->heading : 'Empower Everyone: Teachers, Students, Parents - Get the App Now!',
                    'content' => isset($app->content) ? $app->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                @php
                    $appCards = [
                        ['androidApp.svg', __('student_parent_app'), $settings['app_link'] ?? '', __('android_demo')],
                        ['iosApp.svg', __('student_parent_app'), $settings['ios_app_link'] ?? '', __('ios_demo')],
                        ['androidApp.svg', __('teacher_app'), $settings['teacher_app_link'] ?? '', __('android_demo')],
                        ['iosApp.svg', __('teacher_app'), $settings['teacher_ios_app_link'] ?? '', __('ios_demo')],
                    ];
                @endphp
                <ul class="m3-grid site-apps__grid">
                    @foreach ($appCards as [$icon, $name, $link, $label])
                        <li class="m3-col-12 m3-col-medium-6 m3-col-large-3">
                            <article class="m3-card site-app-card">
                                <div class="m3-card__content">
                                    <img src="{{ url('assets/images/' . $icon) }}" class="site-app-card__icon" alt="..." width="60" height="60">
                                    <h3 class="m3-card__headline site-app-card__name">{{ $name }}</h3>
                                    <div>
                                        <a class="m3-button m3-button--outlined m3-button--inverse m3-button--icon-trailing" href="{{ $link }}">
                                            {{ $label }}
                                            <x-icon name="arrow-right" class="m3-icon site-flip-rtl" />
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
@endsection
