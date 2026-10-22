{{--
    One labelled form field, a Material 3 outlined text field (public/assets/m3):
    the label sits in the outline and floats onto it, the hint and the inline
    error sit under it, wired for screen readers (label for=, aria-describedby,
    aria-invalid set by page.js).

    <x-page.field name="name" :label="__('name')" required />
    <x-page.field name="start_date" type="date" :label="__('starts_on')" :help="__('first_school_day')" required />
    <x-page.field name="grade_id" type="select" :label="__('grade')" :options="$grades" :placeholder="__('choose')" />
    <x-page.field name="note" type="textarea" :label="__('note')" />

    Extra attributes (min, max, maxlength, pattern, data-*) go on the control.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
    'options' => [],
    'placeholder' => null,
    'id' => null,
])
@php
    $fieldId = $id ?? 'field-'.\Illuminate\Support\Str::slug(str_replace(['[', ']', '.'], '-', $name));
    $describedBy = trim(($help ? $fieldId.'-help ' : '').$fieldId.'-error');
    $current = old(str_replace(['[', ']'], ['.', ''], $name), $value);
@endphp
<div class="m3 page-field" data-page-field>
    <label class="m3-text-field {{ $type === 'select' ? 'm3-text-field--select' : '' }}" for="{{ $fieldId }}">
        @if ($type === 'select')
            <select id="{{ $fieldId }}" name="{{ $name }}" aria-describedby="{{ $describedBy }}"
                {{ $attributes->merge(['class' => 'm3-text-field__input']) }} @required($required)>
                @if ($placeholder !== null)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea id="{{ $fieldId }}" name="{{ $name }}" aria-describedby="{{ $describedBy }}"
                {{ $attributes->merge(['class' => 'm3-text-field__input', 'rows' => 3]) }} @required($required)
                placeholder="{{ $placeholder ?? ' ' }}">{{ $current }}</textarea>
        @else
            <input id="{{ $fieldId }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}"
                aria-describedby="{{ $describedBy }}" {{ $attributes->merge(['class' => 'm3-text-field__input']) }}
                @required($required) placeholder="{{ $placeholder ?? ' ' }}">
        @endif
        <span class="m3-text-field__label">{{ $label }}@if ($required)<span class="m3-text-field__required" aria-hidden="true"> *</span>@endif</span>
    </label>
    @if ($help)
        <p id="{{ $fieldId }}-help" class="m3-text-field__supporting page-field-help">{{ $help }}</p>
    @endif
    <p id="{{ $fieldId }}-error" class="m3-text-field__supporting m3-text-field__supporting--error page-field-error"
        data-field-error="{{ $name }}" hidden></p>
</div>
