{{--
    What a page shows before it has anything in it: an icon, a short title, one
    sentence on how to start, and the action that fills the page.

    <x-page.empty-state icon="announcement" :title="__('no_notices_yet')"
        :message="__('no_notices_yet_hint')" :action-label="__('send_a_notice')" action-href="#notice-form" />
--}}
@props([
    'icon' => 'inbox',
    'title',
    'message' => null,
    'actionLabel' => null,
    'actionHref' => null,
    'actionWrite' => true,
])
<div {{ $attributes->merge(['class' => 'm3 page-empty']) }}>
    <span class="page-empty-icon" aria-hidden="true"><x-icon :name="$icon" :size="28" /></span>
    <p class="page-empty-title">{{ $title }}</p>
    @if ($message)
        <p class="page-empty-message">{{ $message }}</p>
    @endif
    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="m3-button m3-button--filled" @if ($actionWrite) data-write @endif>{{ $actionLabel }}</a>
    @endif
    {{ $slot }}
</div>
