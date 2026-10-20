@props([
    'name',
    'image' => null,
    'col' => 4,
    'label' => null,
])

<div class="form-group col-sm-6 col-md-{{ $col }}">
    <label>{{ $label ?? __('image') }} <span class="text-danger">*</span></label>
    <input type="file" name="{{ $name }}" class="file-upload-default edit_image" accept="image/*" />
    <div class="input-group col-xs-12">
        <input type="text" class="form-control edit_image" value="" placeholder="{{ __('image') }}" />
        <span class="input-group-append">
            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
        </span>
    </div>
    <div class="content-image-preview mt-3 {{ $image ? '' : 'd-none' }}">
        <img src="{{ $image ?: '' }}" class="content_image" alt="{{ $label ?? __('image') }}">
    </div>
</div>
