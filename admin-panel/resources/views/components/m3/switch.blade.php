{{--
    M3 switch (an on/off setting that applies straight away or with the form).

        <x-m3.switch name="online_payment" :label="__('online_payment')" :checked="$settings['online_payment'] ?? false" />

    Unchecked switches send nothing, like any checkbox; add a hidden input with the
    "off" value before it if the server needs one.
    class goes on the <label>; input-class and every other attribute go on the input.
--}}
@props([
    'name' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'id' => null,
    'disabled' => false,
    'inputClass' => null,
])
<label {{ $attributes->only('class')->class(['m3-switch']) }}>
    <input type="checkbox" role="switch" @if ($name) name="{{ $name }}" @endif @if ($id) id="{{ $id }}" @endif value="{{ $value }}"
        @checked($checked) @disabled($disabled)
        {{ $attributes->except('class')->class(['m3-switch__input', $inputClass]) }}>
    <span class="m3-switch__track"><span class="m3-switch__handle"></span></span>{{ $label ?? $slot }}
</label>
