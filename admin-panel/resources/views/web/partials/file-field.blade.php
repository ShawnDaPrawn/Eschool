{{--
    A file upload as an M3 text field showing the chosen file's name, with a tonal
    Upload button. The real <input type="file"> is visually hidden; script.js opens it
    from .file-upload-browse and copies the name into .file-upload-info.
    @include('web.partials.file-field', [
        'name' => 'father_image',        // the file input's name
        'label' => __('father') . ' ' . __('image'),
        'required' => true,              // show the asterisk
        'fileRequired' => true,          // put `required` on the file input
        'fileClass' => 'father_image',   // extra class on the file input
        'infoId' => 'father_image',      // id of the file-name field (script.js toggles it)
        'accept' => 'image/*',
        'hint' => __('image_size_hint'),
    ])
--}}
<div class="site-file-field">
    <input type="file" name="{{ $name }}" class="file-upload-default m3-visually-hidden {{ $fileClass ?? '' }}"
        tabindex="-1" aria-label="{{ $label }}"
        @if (! empty($accept)) accept="{{ $accept }}" @endif
        @if (! empty($fileRequired)) required @endif />
    <div class="site-file-field__row">
        <label class="m3-text-field site-file-field__name">
            <input type="text" class="m3-text-field__input file-upload-info" @if (! empty($infoId)) id="{{ $infoId }}" @endif
                disabled placeholder=" " />
            <span class="m3-text-field__label">{{ $label }}
                @if (! empty($required))
                    <span class="m3-text-field__required" aria-hidden="true">*</span>
                @endif
            </span>
        </label>
        <button class="m3-button m3-button--tonal file-upload-browse" type="button"
            aria-label="{{ __('upload') }}: {{ $label }}">{{ __('upload') }}</button>
    </div>
    @if (! empty($hint))
        <p class="m3-text-field__supporting">{{ $hint }}</p>
    @endif
</div>
