{{--
    M3 icon button: a 40px circle holding one icon. The label is required: it is the
    accessible name and the tooltip.

        <x-m3.icon-button icon="edit" :label="__('edit')" />
        <x-m3.icon-button icon="trash" :label="__('delete')" variant="tonal" class="delete-form" :href="$url" />

    variant: standard (default), filled, tonal, outlined, error      size: small
    Every other attribute goes on the element.
--}}
@props([
    'icon',
    'label',
    'variant' => 'standard',
    'type' => 'button',
    'href' => null,
    'size' => null,
    'tooltip' => true,
])
@php
    $classes = [
        'm3-icon-button',
        'm3-icon-button--' . $variant => $variant !== 'standard',
        'm3-icon-button--' . $size => $size,
    ];
    $iconSize = $size === 'small' ? 18 : 24;
@endphp
@if ($href !== null)
    <a href="{{ $href }}" aria-label="{{ $label }}" @if ($tooltip) data-m3-tooltip="{{ $label }}" @endif {{ $attributes->class($classes) }}><x-icon :name="$icon" :size="$iconSize" class="m3-icon" /></a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" @if ($tooltip) data-m3-tooltip="{{ $label }}" @endif {{ $attributes->class($classes) }}><x-icon :name="$icon" :size="$iconSize" class="m3-icon" /></button>
@endif
