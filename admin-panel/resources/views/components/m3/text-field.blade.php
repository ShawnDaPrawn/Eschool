{{--
    M3 outlined text field with a floating label (or a textarea, with type="textarea").

        <x-m3.text-field name="name" :label="__('name')" required />
        <x-m3.text-field name="email" type="email" :label="__('email')" :value="$user->email" :hint="__('email_hint')" />
        <x-m3.text-field name="address" type="textarea" rows="2" :label="__('address')" />
        <x-m3.text-field name="dob" :label="__('dob')" input-class="datepicker-popup" readonly class="m3-col-12 m3-col-medium-6" />

    class        goes on the wrapper (grid placement); jQuery Validate's message stays inside it.
    input-class  extra classes on the input (script hooks such as datepicker-popup).
    icon         a leading <x-icon> name.
    error        a message (or true) to show the error state; defaults to the server's
                 validation error for this field.
    Every other attribute (maxlength, pattern, min, autocomplete, data-*, placeholder…)
    goes on the input. A placeholder keeps the label floated above it.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'id' => null,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'hint' => null,
    'error' => null,
    'icon' => null,
    'rows' => 3,
    'inputClass' => null,
])
@php
    // The old-input / error key for names like "father[email]" or "tags[]".
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $current = $type === 'password' ? null : old($key, $value);
    $message = $error ?? (isset($errors) ? $errors->first($key) : null);
    $supporting = is_string($message) && $message !== '' ? $message : $hint;
    $supportingId = $supporting ? ($id ?? 'm3-field-' . \Illuminate\Support\Str::random(8)) . '-supporting' : null;
    $inputAttributes = $attributes->except('class')->class(['m3-text-field__input', $inputClass]);
    // The floating label needs a placeholder of one space (:placeholder-shown); written
    // literally because the attribute bag trims values.
    $blankPlaceholder = ! $attributes->has('placeholder');
@endphp
<div {{ $attributes->only('class') }}>
    <label @class([
        'm3-text-field',
        'm3-text-field--leading-icon' => $icon,
        'm3-text-field--float' => $attributes->has('placeholder'),
        'm3-text-field--error' => $message,
    ])>
        @if ($icon)
            <x-icon :name="$icon" :size="24" class="m3-text-field__icon m3-text-field__icon--leading" />
        @endif
        @if ($type === 'textarea')
            <textarea name="{{ $name }}" @if ($id) id="{{ $id }}" @endif rows="{{ $rows }}"
                @required($required) @readonly($readonly) @disabled($disabled)
                @if ($message) aria-invalid="true" @endif
                @if ($supportingId) aria-describedby="{{ $supportingId }}" @endif
                @if ($blankPlaceholder) placeholder=" " @endif {{ $inputAttributes }}>{{ $current }}</textarea>
        @else
            <input type="{{ $type }}" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif value="{{ $current }}"
                @required($required) @readonly($readonly) @disabled($disabled)
                @if ($message) aria-invalid="true" @endif
                @if ($supportingId) aria-describedby="{{ $supportingId }}" @endif
                @if ($blankPlaceholder) placeholder=" " @endif {{ $inputAttributes }}>
        @endif
        <span class="m3-text-field__label">{{ $label }}@if ($required) <span class="m3-text-field__required" aria-hidden="true">*</span>@endif</span>
    </label>
    @if ($supporting)
        <p @class(['m3-text-field__supporting', 'm3-text-field__supporting--error' => is_string($message) && $message !== '']) id="{{ $supportingId }}">{{ $supporting }}</p>
    @endif
</div>
