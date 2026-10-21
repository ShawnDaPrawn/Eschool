{{--
    One labelled form field with its hint and its inline error, wired for screen
    readers (label for=, aria-describedby, aria-invalid set by page.js).

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
<div class="form-group page-field">
    <label for="{{ $fieldId }}">
        {{ $label }}
        @if ($required)
            <span class="page-field-required" aria-hidden="true">*</span>
        @endif
    </label>
    @if ($type === 'select')
        <select id="{{ $fieldId }}" name="{{ $name }}" aria-describedby="{{ $describedBy }}"
            {{ $attributes->merge(['class' => 'form-control']) }} @required($required)>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" aria-describedby="{{ $describedBy }}"
            {{ $attributes->merge(['class' => 'form-control', 'rows' => 3]) }} @required($required)
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif>{{ $current }}</textarea>
    @else
        <input id="{{ $fieldId }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}"
            aria-describedby="{{ $describedBy }}" {{ $attributes->merge(['class' => 'form-control']) }}
            @required($required) @if ($placeholder) placeholder="{{ $placeholder }}" @endif>
    @endif
    @if ($help)
        <small id="{{ $fieldId }}-help" class="form-text page-field-help">{{ $help }}</small>
    @endif
    <div id="{{ $fieldId }}-error" class="invalid-feedback" data-field-error="{{ $name }}"></div>
</div>
