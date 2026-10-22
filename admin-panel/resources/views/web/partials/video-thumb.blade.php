{{--
    A video's thumbnail: a button that plays it in the video dialog (web.partials.video-dialog).
    @include('web.partials.video-thumb', ['item' => $media, 'number' => 1, 'hidden' => false])
--}}
<button type="button" class="site-video-thumb" data-video-src="{{ $item->embeded_url['embedUrl'] }}"
    data-video-title="{{ $item->name }}" aria-haspopup="dialog"
    aria-label="{{ filled($item->name) ? $item->name : __('videos') . ' ' . $number }}" @if ($hidden ?? false) hidden @endif>
    <img class="site-video-thumb__image" src="{{ $item->embeded_url['thumbnailUrl'] }}" alt="Thumbnail" loading="lazy">
    <span class="site-video-thumb__play" aria-hidden="true"><x-icon name="play" class="m3-icon" /></span>
</button>
