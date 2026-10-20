@props([
    'steps' => [],
    'activeIndex' => 0,
])

<div {{ $attributes->merge(['class' => 'wizard-container']) }}>
    <div class="wizard-sidebar">
        <div class="steps-list d-flex flex-column">
            @foreach ($steps as $i => $label)
                <a href="#" class="step-item {{ $i === $activeIndex ? 'active' : '' }}" data-step="{{ $i }}">
                    <span class="step-number">{{ $i + 1 }}</span>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>
    <div class="wizard-content">
        {{ $slot }}
    </div>
</div>
