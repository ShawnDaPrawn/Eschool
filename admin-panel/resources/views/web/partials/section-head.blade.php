{{--
    A section's introduction: a tonal chip (the eyebrow tag), the section's h2 and
    a line of supporting text. 'align' => 'start' lines it up with the content
    instead of centring it.
    @include('web.partials.section-head', ['tag' => …, 'heading' => …, 'content' => …, 'align' => 'center'])
--}}
<div class="site-section-head {{ ($align ?? 'center') === 'start' ? 'site-section-head--start' : '' }}">
    @if (filled($tag ?? null))
        <span class="m3-chip m3-chip--tonal">{{ $tag }}</span>
    @endif
    @if (filled($heading ?? null))
        <h2 class="site-section-head__title">{{ $heading }}</h2>
    @endif
    @if (filled($content ?? null))
        <p class="site-section-head__text">{{ $content }}</p>
    @endif
</div>
