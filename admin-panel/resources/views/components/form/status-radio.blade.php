@props([
    'name',
    'value' => null,
    'col' => 4,
    'label' => null,
])

<div class="form-group col-sm-6 col-md-{{ $col }}">
    <label>{{ $label ?? __('status') }} <span class="text-danger">*</span></label><br>
    <div class="d-flex">
        <div class="form-check form-check-inline">
            <label class="form-check-label">
                {!! Form::radio($name, '1', (string) $value === '1', []) !!}{{ __('enable') }}
            <i class="input-helper"></i>
            </label>
        </div>
        <div class="form-check form-check-inline">
            <label class="form-check-label">
                {!! Form::radio($name, '0', (string) $value === '0', []) !!}{{ __('disable') }}
            <i class="input-helper"></i>
            </label>
        </div>
    </div>
</div>
