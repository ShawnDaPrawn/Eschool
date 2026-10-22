{{--
    M3 radio button with its label. For a labelled set, use <x-m3.radio-group>.

        <x-m3.radio name="type" value="parent" :label="__('parent')" checked />

    class goes on the <label>; input-class and every other attribute go on the input.
--}}
@props([
    'name',
    'value',
    'label' => null,
    'checked' => false,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'inputClass' => null,
])
<label {{ $attributes->only('class')->class(['m3-radio']) }}>
    <input type="radio" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif value="{{ $value }}"
        @checked($checked) @required($required) @disabled($disabled)
        {{ $attributes->except('class')->class(['m3-radio__input', $inputClass]) }}>
    <span class="m3-radio__control"></span>{{ $label ?? $slot }}
</label>
