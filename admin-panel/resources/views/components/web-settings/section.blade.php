@props([
    'key',
    'model' => null,
    'rows' => 5,
    'hasImage' => false,
    'hasContent' => true,
])

@php
    $contentCol = $hasImage ? 4 : 6;
    $statusCol  = $hasImage ? 4 : 6;
@endphp

<input type="hidden" name="sections[{{ $key }}][id]" value="{{ $model?->id ?? '' }}" />

<div class="row">
    <div class="form-group col-sm-6 col-md-6">
        <label>{{ __('section_title') }} <span class="text-danger">*</span></label>
        {!! Form::text("sections[$key][tag]", $model?->tag ?? '', ['required', 'placeholder' => __('tag'), 'class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-6 col-md-6">
        <label>{{ __('heading') }} <span class="text-danger">*</span></label>
        {!! Form::text("sections[$key][heading]", $model?->heading ?? '', ['required', 'placeholder' => __('heading'), 'class' => 'form-control']) !!}
    </div>
</div>

<div class="row">
    @if ($hasContent)
        <div class="form-group col-sm-6 col-md-{{ $contentCol }}">
            <label>{{ __('content') }} <span class="text-danger">*</span></label>
            {!! Form::textarea("sections[$key][content]", $model?->content ?? '', ['rows' => $rows, 'placeholder' => __('content'), 'class' => 'form-control']) !!}
        </div>
    @endif

    @if ($hasImage)
        <x-form.image-upload :name="'sections[' . $key . '][image]'" :image="$model?->image" :col="$contentCol" />
    @endif

    <x-form.status-radio :name="'sections[' . $key . '][status]'" :value="$model?->status" :col="$statusCol" />
</div>
