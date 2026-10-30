{{--
    Navigate Ed's announcements to this school (SA.13), as M3 banners for the
    school's Super Admins: shown between the announcement's dates until each
    person dismisses it (a read). Only announcements that reached this school
    appear; the text is escaped. engineering/specs/platform/hq-announcements.md.
--}}
@php
    $platformBanners = app(\App\Services\Platform\Announcements\SchoolAnnouncements::class)->banners(auth()->user());
@endphp
@foreach ($platformBanners as $banner)
    <div class="m3 m3-banner m3-banner--{{ $banner['severity'] === 'warning' ? 'warning' : 'info' }} panel-notice" role="status" data-platform-announcement="{{ $banner['id'] }}">
        <x-icon name="announcement" :size="20" class="panel-notice__icon" />
        <p class="panel-notice__text">
            <strong>{{ __('From Navigate Ed') }} · {{ $banner['title'] }}</strong>
            @if ($banner['window_starts_at'])
                <span>{{ __('Maintenance from :from to :to.', ['from' => $banner['window_starts_at'], 'to' => $banner['window_ends_at']]) }}</span>
            @endif
            <span>{{ $banner['excerpt'] }}</span>
            <a href="{{ route('platform-announcements.index') }}#announcement-{{ $banner['id'] }}">{{ __('Read in full') }}</a>
        </p>
        <form method="POST" action="{{ route('platform-announcements.read', $banner['id']) }}" data-year-safe>
            @csrf
            <button type="submit" class="m3-button m3-button--text" aria-label="{{ __('Dismiss :title', ['title' => $banner['title']]) }}">{{ __('Dismiss') }}</button>
        </form>
    </div>
@endforeach
