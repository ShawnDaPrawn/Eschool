{{--
    A file upload as an M3 text field showing the chosen file's name, with a tonal
    Upload button. The real <input type="file"> is visually hidden; the button opens it
    and the name is copied into the field (custom.js in the admin, script.js on the site).

        <x-m3.file-field name="image" :label="__('image')" accept="image/*" required :hint="__('image_size_hint')" />

    info-id      id of the file-name field (some scripts toggle it).
    class goes on the wrapper; input-class and every other attribute go on the file input.
--}}
@props([
    'name',
    'label',
    'required' => false,
    'accept' => null,
    'multiple' => false,
    'hint' => null,
    'id' => null,
    'infoId' => null,
    'inputClass' => null,
])
<div {{ $attributes->only('class')->class(['m3-file-field']) }}>
    <input type="file" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif tabindex="-1" aria-label="{{ $label }}"
        @if ($accept) accept="{{ $accept }}" @endif @if ($multiple) multiple @endif @required($required)
        {{ $attributes->except('class')->class(['file-upload-default', 'm3-visually-hidden', $inputClass]) }}>
    <div class="m3-file-field__row">
        <label class="m3-text-field m3-file-field__name">
            <input type="text" class="m3-text-field__input file-upload-info" @if ($infoId) id="{{ $infoId }}" @endif disabled placeholder=" ">
            <span class="m3-text-field__label">{{ $label }}@if ($required) <span class="m3-text-field__required" aria-hidden="true">*</span>@endif</span>
        </label>
        <button type="button" class="m3-button m3-button--tonal file-upload-browse" aria-label="{{ __('upload') }}: {{ $label }}">{{ __('upload') }}</button>
    </div>
    @if ($hint)
        <p class="m3-text-field__supporting">{{ $hint }}</p>
    @endif
</div>
