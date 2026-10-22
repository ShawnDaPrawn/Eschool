{{-- Plays a video from a .site-video-thumb (script.js sets the iframe's src, and clears it on close). --}}
<dialog class="m3-dialog m3-dialog--wide site-video-dialog" id="videoModal" aria-labelledby="videoModalTitle">
    <div class="m3-dialog__header">
        <h2 class="m3-dialog__headline" id="videoModalTitle">{{ __('videos') }}</h2>
        <button type="button" class="m3-icon-button" data-m3-dialog-close aria-label="{{ __('close') }}">
            <x-icon name="close" class="m3-icon" />
        </button>
    </div>
    <div class="site-video-dialog__frame">
        <iframe id="videoIframe" title="{{ __('videos') }}" src="" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
    </div>
</dialog>
