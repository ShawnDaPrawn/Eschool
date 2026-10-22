{{--
    An M3 outlined text field with a floating label, in a wrapper that takes the grid
    classes (so jQuery Validate's error text, placed after the field, stays in its cell).
    @include('web.partials.text-field', [
        'name' => 'father_email', 'label' => __('father_email'),
        'type' => 'email',            // default text; 'textarea' for a textarea
        'id' => 'father_email',       // optional
        'value' => null,              // optional
        'required' => true,           // `required` and the asterisk
        'readonly' => false,
        'inputClass' => 'datepicker-popup-no-future',   // extra classes on the input
        'class' => 'm3-col-12 m3-col-medium-6',         // classes on the wrapper
        'attrs' => ['maxlength' => 16],                 // any other attributes
        'rows' => 2,                  // textarea only
    ])
--}}
@php
    $fieldType = $type ?? 'text';
    $extra = '';
    foreach ($attrs ?? [] as $attribute => $attributeValue) {
        $extra .= ' ' . $attribute . '="' . e($attributeValue) . '"';
    }
@endphp
<div class="{{ $class ?? '' }}">
<label class="m3-text-field">
    @if ($fieldType === 'textarea')
        <textarea class="m3-text-field__input {{ $inputClass ?? '' }}" name="{{ $name }}" @if (! empty($id)) id="{{ $id }}" @endif
            rows="{{ $rows ?? 3 }}" placeholder=" " @if (! empty($required)) required @endif @if (! empty($readonly)) readonly @endif {!! $extra !!}>{{ $value ?? '' }}</textarea>
    @else
        <input class="m3-text-field__input {{ $inputClass ?? '' }}" type="{{ $fieldType }}" name="{{ $name }}" @if (! empty($id)) id="{{ $id }}" @endif
            value="{{ $value ?? '' }}" placeholder=" " @if (! empty($required)) required @endif @if (! empty($readonly)) readonly @endif {!! $extra !!}>
    @endif
    <span class="m3-text-field__label">{{ $label }}
        @if (! empty($required) || ! empty($asterisk))
            <span class="m3-text-field__required" aria-hidden="true">*</span>
        @endif
    </span>
</label>
</div>
