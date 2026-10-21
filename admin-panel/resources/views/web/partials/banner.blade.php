{{--
    The navy banner at the top of an inner page: the page's h1 and its breadcrumbs.
    @include('web.partials.banner', ['title' => __('photos'), 'crumbs' => [[__('home'), url('/')], [__('gallery'), null], [__('photos'), null]]])
    The last crumb is the current page; a crumb without a URL is plain text.
--}}
<section class="site-banner">
    <div class="m3-page">
        <h1 class="site-banner__title">{{ $title }}</h1>
        <nav aria-label="Breadcrumb">
            <ol class="site-crumbs">
                @foreach ($crumbs as [$label, $href])
                    @if ($loop->last)
                        <li aria-current="page">{{ $label }}</li>
                    @elseif ($href)
                        <li><a href="{{ $href }}">{{ $label }}</a></li>
                    @else
                        <li>{{ $label }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
</section>
