{{--
    The setup checklist (App\Services\Panel\SetupChecklist): each step is worked
    out from the school's data and links to the page that completes it. Once
    every step is done it folds into one line that can be opened again.
--}}
<section class="card page-section setup-checklist {{ $checklist['complete'] ? 'is-complete' : '' }}"
    aria-labelledby="setup-checklist-title" data-setup-checklist>
    <div class="card-body">
        @if ($checklist['complete'])
            <details>
                <summary class="setup-checklist-summary">
                    <x-icon name="circle-check" :size="22" class="setup-checklist-ready-icon" />
                    <span id="setup-checklist-title" class="setup-checklist-title">{{ __('checklist_ready_title') }}</span>
                    <span class="setup-checklist-count">{{ __('checklist_progress', ['done' => $checklist['done'], 'total' => $checklist['total']]) }}</span>
                </summary>
                @include('home.checklist-steps')
            </details>
        @else
            <div class="setup-checklist-head">
                <div>
                    <h2 id="setup-checklist-title" class="setup-checklist-title">{{ __('checklist_title') }}</h2>
                    <p class="setup-checklist-intro">{{ __('checklist_intro') }}</p>
                </div>
                <div class="setup-checklist-progress">
                    <label for="setup-checklist-progress" class="setup-checklist-count">
                        {{ __('checklist_progress', ['done' => $checklist['done'], 'total' => $checklist['total']]) }}
                    </label>
                    <progress id="setup-checklist-progress" max="{{ $checklist['total'] }}" value="{{ $checklist['done'] }}">
                        {{ $checklist['done'] }} / {{ $checklist['total'] }}
                    </progress>
                </div>
            </div>
            @include('home.checklist-steps')
        @endif
    </div>
</section>
