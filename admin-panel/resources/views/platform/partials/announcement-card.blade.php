<article class="m3 m3-card m3-card--outlined" id="announcement-{{ $announcement['id'] }}" aria-labelledby="announcement-{{ $announcement['id'] }}-title"><div class="m3-card__content">
    <h2 id="announcement-{{ $announcement['id'] }}-title">{{ $announcement['title'] }}</h2>
    <p>
        {{ __($announcement['kind_label']) }}
        · {{ __('From :date', ['date' => $announcement['shows_from']]) }}@if ($announcement['shows_until']) {{ __('until :date', ['date' => $announcement['shows_until']]) }}@endif
        @if ($announcement['window_starts_at']) · {{ __('Maintenance from :from to :to.', ['from' => $announcement['window_starts_at'], 'to' => $announcement['window_ends_at']]) }}@endif
        @if ($announcement['read_at']) · {{ __('You dismissed it on :date.', ['date' => $announcement['read_at']]) }}@endif
    </p>
    {{ $announcement['html'] }}
    @unless ($announcement['read_at'])
        <form method="POST" action="{{ route('platform-announcements.read', $announcement['id']) }}" data-year-safe>
            @csrf
            <button type="submit" class="m3-button m3-button--outlined">{{ __('Mark as read') }}</button>
        </form>
    @endunless
</div></article>
