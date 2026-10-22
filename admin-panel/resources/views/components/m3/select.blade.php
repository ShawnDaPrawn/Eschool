{{--
    M3 outlined select field.

        <x-m3.select name="medium_id" :label="__('medium')" :options="$mediums->pluck('name', 'id')"
            :placeholder="__('select_medium')" required />
        <x-m3.select name="subject_ids[]" :label="__('subjects')" :options="$subjects" :value="$chosen"
            multiple input-class="select2" />

    options      value => label (an array or a collection). Put custom <option>s in the slot.
    value        the selected value, or an array of them for a multiple select.
    placeholder  adds an empty first option with this text.
    class goes on the wrapper; input-class and every other attribute go on the <select>.
--}}
@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'hint' => null,
    'error' => null,
    'inputClass' => null,
])
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $current = old($key, $value);
    if ($current instanceof \Illuminate\Support\Collection) {
        $current = $current->all();
    }
    $selected = array_map('strval', array_filter(is_array($current) ? $current : [$current], fn ($v) => $v !== null));
    $message = $error ?? (isset($errors) ? $errors->first($key) : null);
    $supporting = is_string($message) && $message !== '' ? $message : $hint;
    $supportingId = $supporting ? ($id ?? 'm3-field-' . \Illuminate\Support\Str::random(8)) . '-supporting' : null;
@endphp
<div {{ $attributes->only('class') }}>
    <label @class(['m3-text-field', 'm3-text-field--select', 'm3-text-field--error' => $message])>
        <select name="{{ $name }}" @if ($id) id="{{ $id }}" @endif
            @required($required) @disabled($disabled) @if ($multiple) multiple @endif
            @if ($message) aria-invalid="true" @endif
            @if ($supportingId) aria-describedby="{{ $supportingId }}" @endif
            {{ $attributes->except('class')->class(['m3-text-field__input', $inputClass]) }}>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selected, true))>{{ $optionLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <span class="m3-text-field__label">{{ $label }}@if ($required) <span class="m3-text-field__required" aria-hidden="true">*</span>@endif</span>
    </label>
    @if ($supporting)
        <p @class(['m3-text-field__supporting', 'm3-text-field__supporting--error' => is_string($message) && $message !== '']) id="{{ $supportingId }}">{{ $supporting }}</p>
    @endif
</div>
