@props(['index', 'title' => '', 'active' => false])

<div {{ $attributes->merge(['class' => 'wizard-step' . ($active ? ' active' : '')]) }} data-step="{{ $index }}">
    <div class="wizard-step-content">
        @if ($title !== '')
            <h4 class="wizard-step-title">{{ $title }}</h4>
        @endif
        {{ $slot }}
    </div>
</div>
