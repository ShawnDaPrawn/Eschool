{{--
    One item in an <x-m3.menu>: a link with href, otherwise a button.
    selected marks the current choice; icon is a leading <x-icon> name.
    Every other attribute goes on the item.
--}}
@props([
    'href' => null,
    'icon' => null,
    'selected' => false,
    'type' => 'button',
])
@php
    $classes = ['m3-menu__item', 'm3-menu__item--selected' => $selected];
@endphp
@if ($href !== null)
    <a href="{{ $href }}" role="menuitem" @if ($selected) aria-current="true" @endif {{ $attributes->class($classes) }}>@if ($icon)<x-icon :name="$icon" :size="20" class="m3-icon" />@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" role="menuitem" {{ $attributes->class($classes) }}>@if ($icon)<x-icon :name="$icon" :size="20" class="m3-icon" />@endif{{ $slot }}</button>
@endif
