{{--
    M3 chip: a compact label or status.

        <x-m3.chip>{{ __('draft') }}</x-m3.chip>
        <x-m3.chip status="success" icon="check">{{ __('active') }}</x-m3.chip>

    status: success, warning, danger, info, neutral (a tinted status chip).
    tonal: a brand-tinted tag.   icon: a leading <x-icon> name.
--}}
@props([
    'status' => null,
    'tonal' => false,
    'icon' => null,
])
<span {{ $attributes->class([
    'm3-chip',
    'm3-chip--status-' . $status => $status,
    'm3-chip--tonal' => $tonal,
    'm3-chip--with-icon' => $icon,
]) }}>@if ($icon)<x-icon :name="$icon" :size="18" class="m3-icon" />@endif{{ $slot }}</span>
