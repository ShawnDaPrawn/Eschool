{{--
    A labelled set of M3 radio buttons (a <fieldset> with a <legend>).

        <x-m3.radio-group name="status" :legend="__('status')" :value="$row->status ?? 1" required
            :options="['1' => __('enable'), '0' => __('disable')]" />

    options  value => label. Put extra <x-m3.radio>s in the slot instead for per-option attributes.
    class goes on the <fieldset>; input-class goes on every radio.
--}}
@props([
    'name',
    'legend',
    'options' => [],
    'value' => null,
    'required' => false,
    'disabled' => false,
    'inputClass' => null,
])
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $current = old($key, $value);
@endphp
<fieldset {{ $attributes->class(['m3-fieldset']) }}>
    <legend class="m3-fieldset__legend">{{ $legend }}@if ($required) <span class="m3-text-field__required" aria-hidden="true">*</span>@endif</legend>
    <div class="m3-fieldset__options">
        @foreach ($options as $optionValue => $optionLabel)
            <x-m3.radio :name="$name" :value="$optionValue" :label="$optionLabel" :input-class="$inputClass"
                :checked="$current !== null && (string) $current === (string) $optionValue"
                :required="$required" :disabled="$disabled" />
        @endforeach
        {{ $slot }}
    </div>
</fieldset>
