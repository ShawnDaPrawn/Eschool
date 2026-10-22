@extends('web.master')

@section('title')
    {{ __('about-us') }}
@endsection
@section('content')
    @include('web.partials.banner', [
        'title' => __('about-us'),
        'crumbs' => [[__('home'), url('/')], [__('about-us'), null]],
    ])

    @if ($about)
        <section class="m3-section site-about">
            <div class="m3-page m3-grid m3-gap-8">
                <div class="m3-col-12 m3-col-expanded-6">
                    <img class="site-about__image" src="{{ isset($about->image) ? $about->image : url('assets/images/dummyImg.png') }}" alt="" />
                </div>
                <div class="m3-col-12 m3-col-expanded-6 site-about__content">
                    @include('web.partials.section-head', [
                        'tag' => isset($about->tag) ? $about->tag : 'About Us',
                        'heading' => isset($about->heading) ? $about->heading : 'Cutting-Edge Education That Empowers',
                        'content' => isset($about->content) ? $about->content : 'Lorem ipsum dolor sit amet consectetur. Faucibus non mauris risus enim sed. Lectus fusce elit duis dignissim aliquet nisl vitae. Eget sit nisi vulputate enim sem. Facilisi tincidunt donec interdum in in eros quisque consectetur sit. Sagittis purus velit amet risus egestas sed arcu nam. Pellentesque pharetra blandit fringilla volutpat tristique sit. Sit euismod praesent volutpat eu et. Id egestas dictum cursus purus morbi semper praesent quam.',
                        'align' => 'start',
                    ])
                </div>
            </div>
        </section>
    @endif

    @if ($whoweare)
        <section class="m3-section m3-surface-container-low site-who">
            <div class="m3-page m3-grid m3-gap-8">
                <div class="m3-col-12 m3-col-expanded-6 m3-stack m3-gap-8">
                    @include('web.partials.section-head', [
                        'tag' => isset($whoweare->tag) ? $whoweare->tag : 'Who We Are',
                        'heading' => isset($whoweare->heading) ? $whoweare->heading : 'Empowering Minds, Shaping Futures',
                        'content' => isset($whoweare->content) ? $whoweare->content : 'Lorem ipsum dolor sit amet consectetur. Faucibus non mauris risus enim sed. Lectus fusce elit duis dignissim aliquet nisl vitae. Eget sit nisi vulputate enim sem. Facilisi tincidunt donec interdum in in eros quisque consectetur sit. Sagittis purus velit amet risus egestas sed arcu nam. Pellentesque pharetra blandit fringilla volutpat tristique sit. Sit euismod praesent volutpat eu et. Id egestas dictum cursus purus morbi semper praesent quam.',
                        'align' => 'start',
                    ])
                    <ul class="m3-grid m3-gap-4 site-who__stats">
                        <li class="m3-col-12 m3-col-medium-6">
                            <div class="m3-card m3-card--outlined site-stat">
                                <span class="site-stat__icon"><img src="{{ url('assets/images/student.png') }}" alt="" width="52" height="52"></span>
                                <p class="site-stat__text">
                                    <span class="site-stat__value">{{ $studentcount }}+</span>
                                    <span class="site-stat__label">{{ __('student') }} {{ __('enrolled') }}</span>
                                </p>
                            </div>
                        </li>
                        <li class="m3-col-12 m3-col-medium-6">
                            <div class="m3-card m3-card--outlined site-stat">
                                <span class="site-stat__icon"><img src="{{ url('assets/images/teacher.png') }}" alt="" width="52" height="52"></span>
                                <p class="site-stat__text">
                                    <span class="site-stat__value">{{ $teachercount }}+</span>
                                    <span class="site-stat__label">{{ __('expert') }} {{ __('teachers') }}</span>
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="m3-col-12 m3-col-expanded-6">
                    <img class="site-who__image" src="{{ isset($whoweare->image) ? $whoweare->image : url('assets/images/who-we-are-img.png') }}" alt="">
                </div>
            </div>
        </section>
    @endif

    @if ($teacher)
        <section class="m3-section site-teachers">
            <div class="m3-page">
                @include('web.partials.section-head', [
                    'tag' => isset($teacher->tag) ? $teacher->tag : 'Our Expert Teacher',
                    'heading' => isset($teacher->heading) ? $teacher->heading : "More Than Just Teachers, They're Mentors",
                    'content' => isset($teacher->content) ? $teacher->content : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ])
                <div class="site-slider">
                    <div class="owl-carousel site-slider__track" data-site-carousel="strip">
                        @if (isset($teachers))
                            @foreach ($teachers as $teacher)
                                <article class="m3-card m3-card--outlined site-teacher">
                                    <img class="site-teacher__photo" src="{{ $teacher->user->image ?? '' }}" alt="" onerror="onErrorImage(event)">
                                    <div class="m3-card__content site-teacher__details">
                                        <h3 class="m3-card__headline">{{ $teacher->user->first_name . '  ' . $teacher->user->last_name }}</h3>
                                        <p class="m3-card__supporting">{{ $teacher->qualification }}</p>
                                    </div>
                                </article>
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
@endsection
