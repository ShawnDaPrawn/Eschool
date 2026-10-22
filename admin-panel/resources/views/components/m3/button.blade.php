{{--
    M3 button: an <a> when it has an href, otherwise a <button>.

        <x-m3.button type="submit">{{ __('submit') }}</x-m3.button>
        <x-m3.button variant="outlined" icon="plus">{{ __('add') }}</x-m3.button>
        <x-m3.button variant="text" :href="url('students')">{{ __('back') }}</x-m3.button>

    variant: filled (default), tonal, elevated, outlined, text, error, inverse
    size:    small, large      block: full width
    icon / trailing-icon: an <x-icon> name.
    Every other attribute (id, class, name, value, disabled, form, data-*) goes on the element.
--}}
@props([
    'variant' => 'filled',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'trailingIcon' => null,
    'size' => null,
    'block' => false,
])
@php
    $classes = [
        'm3-button',
        'm3-button--' . $variant,
        'm3-button--' . $size => $size,
        'm3-button--block' => $block,
        'm3-button--icon-leading' => $icon,
        'm3-button--icon-trailing' => $trailingIcon,
    ];
@endphp
@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>@if ($icon)<x-icon :name="$icon" :size="18" class="m3-icon" />@endif{{ $slot }}@if ($trailingIcon)<x-icon :name="$trailingIcon" :size="18" class="m3-icon" />@endif</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>@if ($icon)<x-icon :name="$icon" :size="18" class="m3-icon" />@endif{{ $slot }}@if ($trailingIcon)<x-icon :name="$trailingIcon" :size="18" class="m3-icon" />@endif</button>
@endif
