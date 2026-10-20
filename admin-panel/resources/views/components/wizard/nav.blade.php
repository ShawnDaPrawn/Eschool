@props([
    'current',
    'total' => null,
    'last' => false,
    'prevLabel' => null,
    'nextLabel' => null,
    'submitLabel' => null,
])

<div class="wizard-nav">
    <button type="button" class="btn btn-secondary wizard-prev" data-current="{{ $current }}" @if ($current === 0) disabled @endif>
        {{ $prevLabel ?? __('Previous') }}
    </button>
    @if ($last)
        <input type="submit" class="btn btn-theme wizard-submit-all" value="{{ $submitLabel ?? __('submit') }}">
    @else
        <button type="button" class="btn btn-theme wizard-next" data-current="{{ $current }}">
            {{ $nextLabel ?? __('Next') }}
        </button>
    @endif
</div>
