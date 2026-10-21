{{--
    Page header: where you are (section and group from config/navigation.php),
    the page title (the page's only h1), an optional line under it, and at most
    one primary action. Extra, quieter actions go in the `actions` slot.

    <x-page.header :title="__('nav_notices')" :subtitle="__('notices_subtitle')"
        :primary-label="__('send_a_notice')" primary-href="#notice-form" primary-icon="plus" />

    primary-permission hides the action from people without that permission.
    The primary action counts as a write (disabled in a read-only year) unless
    :primary-write="false".
--}}
@props([
    'title',
    'subtitle' => null,
    'primaryLabel' => null,
    'primaryHref' => null,
    'primaryIcon' => 'plus',
    'primaryPermission' => null,
    'primaryWrite' => true,
    'breadcrumbs' => true,
])
@php
    $navItem = $breadcrumbs ? app(\App\Services\Panel\Navigation::class)->current(auth()->user()) : null;
    $showPrimary = $primaryLabel && $primaryHref && (! $primaryPermission || auth()->user()?->can($primaryPermission));
@endphp
<header {{ $attributes->merge(['class' => 'page-head']) }}>
    <div class="page-head-text">
        @if ($navItem && $navItem['section'] !== 'home')
            <nav aria-label="{{ __('page_breadcrumb') }}">
                <ol class="page-breadcrumb">
                    <li>{{ $navItem['section_label'] }}</li>
                    <li>{{ $navItem['group_label'] }}</li>
                </ol>
            </nav>
        @endif
        <h1 class="page-head-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-head-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($showPrimary || isset($actions))
        <div class="page-head-actions">
            {{ $actions ?? '' }}
            @if ($showPrimary)
                <a href="{{ $primaryHref }}" class="btn btn-theme page-head-primary" @if ($primaryWrite) data-write @endif>
                    @if ($primaryIcon)
                        <x-icon :name="$primaryIcon" :size="18" />
                    @endif
                    <span>{{ $primaryLabel }}</span>
                </a>
            @endif
        </div>
    @endif
</header>
