{{--
    The setup checklist (App\Services\Panel\SetupChecklist), a Material 3 card:
    each step is worked out from the school's data and links to the page that
    completes it. Once every step is done it folds into one line (an M3
    expansion) that can be opened again.
--}}
<section class="m3 m3-card m3-card--outlined page-section setup-checklist {{ $checklist['complete'] ? 'is-complete' : '' }}"
    aria-labelledby="setup-checklist-title" data-setup-checklist>
    @if ($checklist['complete'])
        <details class="m3-expansion setup-checklist__expansion">
            <summary class="m3-expansion__header setup-checklist-summary">
                <x-icon name="circle-check" :size="24" class="setup-checklist-ready-icon" />
                <span id="setup-checklist-title" class="setup-checklist-title">{{ __('checklist_ready_title') }}</span>
                <span class="setup-checklist-count">{{ __('checklist_progress', ['done' => $checklist['done'], 'total' => $checklist['total']]) }}</span>
            </summary>
            <div class="m3-expansion__content">
                @include('home.checklist-steps')
            </div>
        </details>
    @else
        <div class="m3-card__content">
            <div class="setup-checklist-head">
                <div>
                    <h2 id="setup-checklist-title" class="setup-checklist-title">{{ __('checklist_title') }}</h2>
                    <p class="setup-checklist-intro">{{ __('checklist_intro') }}</p>
                </div>
                <div class="setup-checklist-progress">
                    <span class="setup-checklist-count" id="setup-checklist-progress-label">
                        {{ __('checklist_progress', ['done' => $checklist['done'], 'total' => $checklist['total']]) }}
                    </span>
                    <div class="m3-progress-linear setup-checklist-bar" role="progressbar" aria-labelledby="setup-checklist-progress-label"
                        aria-valuemin="0" aria-valuemax="{{ $checklist['total'] }}" aria-valuenow="{{ $checklist['done'] }}"
                        style="--m3-progress: {{ $checklist['total'] ? round($checklist['done'] / $checklist['total'] * 100) : 0 }}%">
                        <span class="m3-progress-linear__bar"></span>
                    </div>
                </div>
            </div>
            @include('home.checklist-steps')
        </div>
    @endif
</section>
