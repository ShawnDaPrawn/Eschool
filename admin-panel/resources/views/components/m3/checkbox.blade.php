{{--
    M3 checkbox with its label.

        <x-m3.checkbox name="is_active" :label="__('active')" :checked="$row->is_active" />
        <x-m3.checkbox name="days[]" value="monday" :label="__('monday')" />

    class goes on the <label>; input-class and every other attribute go on the input.
    The label text can also be the slot.
--}}
@props([
    'name' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'inputClass' => null,
])
<label {{ $attributes->only('class')->class(['m3-checkbox']) }}>
    <input type="checkbox" @if ($name) name="{{ $name }}" @endif @if ($id) id="{{ $id }}" @endif value="{{ $value }}"
        @checked($checked) @required($required) @disabled($disabled)
        {{ $attributes->except('class')->class(['m3-checkbox__input', $inputClass]) }}>
    <span class="m3-checkbox__control"></span>{{ $label ?? $slot }}
</label>
