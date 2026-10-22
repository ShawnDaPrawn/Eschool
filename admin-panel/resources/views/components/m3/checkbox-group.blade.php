{{--
    A labelled set of M3 checkboxes (a <fieldset> with a <legend>), sent as name[].

        <x-m3.checkbox-group name="days" :legend="__('days')" :options="$days" :value="$row->days ?? []" />

    options  value => label.   value  the checked values.
    class goes on the <fieldset>; input-class goes on every checkbox.
--}}
@props([
    'name',
    'legend',
    'options' => [],
    'value' => [],
    'required' => false,
    'disabled' => false,
    'inputClass' => null,
])
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $current = old($key, $value);
    if ($current instanceof \Illuminate\Support\Collection) {
        $current = $current->all();
    }
    $checkedValues = array_map('strval', (array) $current);
    $inputName = str_ends_with($name, '[]') ? $name : $name . '[]';
@endphp
<fieldset {{ $attributes->class(['m3-fieldset']) }}>
    <legend class="m3-fieldset__legend">{{ $legend }}@if ($required) <span class="m3-text-field__required" aria-hidden="true">*</span>@endif</legend>
    <div class="m3-fieldset__options">
        @foreach ($options as $optionValue => $optionLabel)
            <x-m3.checkbox :name="$inputName" :value="$optionValue" :label="$optionLabel" :input-class="$inputClass"
                :checked="in_array((string) $optionValue, $checkedValues, true)"
                :required="$required" :disabled="$disabled" />
        @endforeach
        {{ $slot }}
    </div>
</fieldset>
