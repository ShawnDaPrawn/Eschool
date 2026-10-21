@extends('layouts.master')

@section('title')
    {{ __('nav_new_year') }}
@endsection

{{--
    New-year wizard (NewYearController, NewYearService), on the page pattern.
    Step 1 creates next year (the current year is not touched). Steps 2 to 5
    are one form: what to carry forward, the year-end checks, the preview and
    the switch, which runs the chosen transfers and makes the year current.
    new-year.js shows one step at a time; without it every step shows.
--}}
@section('content')
    @php
        $steps = [
            1 => __('new_year_step_create'),
            2 => __('new_year_step_carry'),
            3 => __('new_year_step_checklist'),
            4 => __('new_year_step_preview'),
            5 => __('new_year_step_switch'),
        ];
    @endphp
    <div class="content-wrapper new-year" data-new-year data-start-step="{{ $step }}"
        @if ($next) data-preview-url="{{ route('new-year.preview') }}" data-year-id="{{ $next->id }}" @endif>

        <x-page.header :title="__('nav_new_year')"
            :subtitle="__('new_year_subtitle', ['year' => $current->name])" />

        <nav aria-label="{{ __('new_year_steps') }}">
            <ol class="wizard-steps">
                @foreach ($steps as $number => $label)
                    <li class="wizard-step-item">
                        <button type="button" class="wizard-step-button" data-wizard-goto="{{ $number }}"
                            @if (! $next && $number > 1) disabled @endif>
                            <span class="wizard-step-number" aria-hidden="true">{{ $number }}</span>
                            <span>{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        {{-- Step 1: next year --}}
        <section class="wizard-panel" data-wizard-step="1" aria-labelledby="new-year-step-1">
            <h2 class="page-section-title" id="new-year-step-1" tabindex="-1">1. {{ $steps[1] }}</h2>
            @if ($next)
                <div class="card page-section">
                    <div class="card-body">
                        <p class="page-card-title" data-next-year="{{ $next->id }}">
                            <x-icon name="circle-check" :size="20" class="text-success" />
                            {{ __('new_year_ready', ['year' => $next->name]) }}
                        </p>
                        <p class="page-card-subtitle">
                            {{ __('new_year_dates', ['start' => \Carbon\Carbon::parse($next->start_date)->translatedFormat('j F Y'), 'end' => \Carbon\Carbon::parse($next->end_date)->translatedFormat('j F Y')]) }}
                        </p>
                        @if ($nextTerms->isNotEmpty())
                            <ul class="new-year-terms">
                                @foreach ($nextTerms as $term)
                                    <li>{{ $term->name }}: {{ \Carbon\Carbon::parse($term->start_date)->translatedFormat('j M') }} – {{ \Carbon\Carbon::parse($term->end_date)->translatedFormat('j M Y') }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="new-year-note">{{ __('new_year_ready_note', ['current' => $current->name]) }}
                            <a href="{{ route('session-years.index') }}">{{ __('nav_school_years') }}</a>
                        </p>
                        <div class="page-form-actions">
                            <button type="button" class="btn btn-theme" data-wizard-next>{{ __('new_year_next') }}</button>
                        </div>
                    </div>
                </div>
            @else
                <x-page.form-card id="new-year-form" :action="route('new-year.store')"
                    :title="__('new_year_create_title', ['current' => $current->name])"
                    :subtitle="__('new_year_create_hint', ['current' => $current->name])"
                    :submit-label="__('new_year_create')">
                    <div class="row">
                        <div class="col-md-4">
                            <x-page.field name="name" :label="__('new_year_name')" :value="$suggestion['name']" required maxlength="255" />
                        </div>
                        <div class="col-md-4">
                            <x-page.field name="start_date" type="date" :label="__('new_year_starts_on')" :value="$suggestion['start_date']" required />
                        </div>
                        <div class="col-md-4">
                            <x-page.field name="end_date" type="date" :label="__('new_year_ends_on')" :value="$suggestion['end_date']" required />
                        </div>
                    </div>
                    <fieldset class="new-year-fieldset">
                        <legend class="new-year-legend">{{ __('new_year_terms') }}</legend>
                        <p class="page-field-help">{{ __('new_year_terms_hint') }}</p>
                        @foreach ($suggestion['terms'] as $index => $term)
                            <div class="row new-year-term" data-term-row>
                                <div class="col-md-4">
                                    <x-page.field name="terms[{{ $index }}][name]" :label="__('new_year_term')" :value="$term['name']" maxlength="255" />
                                </div>
                                <div class="col-md-4">
                                    <x-page.field name="terms[{{ $index }}][start_date]" type="date" :label="__('new_year_term_starts_on')" :value="$term['start_date']" />
                                </div>
                                <div class="col-md-4">
                                    <x-page.field name="terms[{{ $index }}][end_date]" type="date" :label="__('new_year_term_ends_on')" :value="$term['end_date']" />
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                    <div class="row">
                        <div class="col-md-6">
                            <x-page.field name="fee_due_date" type="date" :label="__('new_year_fee_due_date')" :value="$suggestion['fee_due_date']"
                                :help="__('new_year_fee_due_date_hint')" required />
                        </div>
                        <div class="col-md-6">
                            <x-page.field name="fee_due_charges" type="number" :label="__('new_year_fee_due_charges')" :value="$suggestion['fee_due_charges']"
                                :help="__('new_year_fee_due_charges_hint')" min="0" max="100" step="0.01" required />
                        </div>
                    </div>
                </x-page.form-card>
            @endif
        </section>

        @if ($next)
            <form id="new-year-switch" class="page-form" action="{{ route('new-year.make-current') }}" method="POST" novalidate
                data-page-form data-draft="new-year-switch-{{ $next->id }}">
                @csrf
                <input type="hidden" name="year_id" value="{{ $next->id }}">
                <div class="page-form-summary" role="alert" data-form-summary hidden></div>

                {{-- Step 2: carry forward --}}
                <section class="wizard-panel" data-wizard-step="2" aria-labelledby="new-year-step-2">
                    <h2 class="page-section-title" id="new-year-step-2" tabindex="-1">2. {{ $steps[2] }}</h2>
                    <div class="card page-section">
                        <div class="card-body">
                            <fieldset>
                                <legend class="page-card-title">{{ __('new_year_carry_title', ['current' => $current->name, 'next' => $next->name]) }}</legend>
                                <p class="page-card-subtitle">{{ __('new_year_carry_hint') }}</p>
                                <ul class="new-year-carry">
                                    @foreach ($carry as $option)
                                        @php($usable = $option['source'] > 0 && $option['existing'] === 0)
                                        <li class="form-check new-year-carry-option">
                                            <input class="form-check-input" type="checkbox" name="carry[]" value="{{ $option['key'] }}"
                                                id="carry-{{ $option['key'] }}" @checked($usable) @disabled(! $usable)
                                                aria-describedby="carry-{{ $option['key'] }}-note" data-carry-option>
                                            <label class="form-check-label" for="carry-{{ $option['key'] }}">{{ $option['label'] }}</label>
                                            <span class="new-year-carry-note" id="carry-{{ $option['key'] }}-note">
                                                @if ($option['existing'] > 0)
                                                    {{ __('new_year_carry_has_own', ['year' => $next->name, 'count' => $option['existing']]) }}
                                                @elseif ($option['source'] === 0)
                                                    {{ __('new_year_carry_nothing', ['year' => $current->name]) }}
                                                @else
                                                    {{ __('new_year_carry_count', ['count' => $option['source'], 'year' => $current->name]) }}
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="new-year-note">{{ __('new_year_carry_promotions') }}</p>
                            </fieldset>
                            <div class="page-form-actions">
                                <button type="button" class="btn btn-secondary" data-wizard-back>{{ __('new_year_back') }}</button>
                                <button type="button" class="btn btn-theme" data-wizard-next>{{ __('new_year_next') }}</button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Step 3: year-end checklist --}}
                <section class="wizard-panel" data-wizard-step="3" aria-labelledby="new-year-step-3">
                    <h2 class="page-section-title" id="new-year-step-3" tabindex="-1">3. {{ $steps[3] }}</h2>
                    <div class="card page-section">
                        <div class="card-body">
                            <p class="page-card-subtitle">{{ __('new_year_checklist_hint', ['current' => $current->name]) }}</p>
                            <ul class="setup-checklist-steps">
                                @foreach ($checklist as $check)
                                    <li class="setup-step {{ $check['ok'] ? 'is-done' : '' }}" data-year-end-check="{{ $check['key'] }}">
                                        <span class="setup-step-icon">
                                            <x-icon :name="$check['ok'] ? 'circle-check' : 'alert'" :size="22" />
                                            <span class="sr-only">{{ $check['ok'] ? __('checklist_step_done') : __('new_year_check_attention') }}</span>
                                        </span>
                                        <div class="setup-step-text">
                                            <span class="setup-step-title">{{ $check['title'] }}</span>
                                            <span class="setup-step-detail">{{ $check['detail'] }}</span>
                                        </div>
                                        @if ($check['url'])
                                            <a class="btn btn-sm btn-secondary setup-step-action" href="{{ $check['url'] }}">
                                                {{ __('new_year_check_open') }}<span class="sr-only">: {{ $check['title'] }}</span>
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            <div class="page-form-actions">
                                <button type="button" class="btn btn-secondary" data-wizard-back>{{ __('new_year_back') }}</button>
                                <button type="button" class="btn btn-theme" data-wizard-next>{{ __('new_year_next') }}</button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Step 4: preview --}}
                <section class="wizard-panel" data-wizard-step="4" aria-labelledby="new-year-step-4">
                    <h2 class="page-section-title" id="new-year-step-4" tabindex="-1">4. {{ $steps[4] }}</h2>
                    <div class="card page-section">
                        <div class="card-body">
                            <p class="page-card-title">{{ __('new_year_preview_title', ['next' => $next->name]) }}</p>
                            <ul class="new-year-preview" data-new-year-preview aria-live="polite">
                                <li>{{ __('new_year_preview_current', ['next' => $next->name, 'current' => $current->name]) }}</li>
                                @foreach ($preview['carry'] as $option)
                                    @if ($option['copies'])
                                        <li data-preview-copies="{{ $option['key'] }}">{{ __('new_year_preview_copies', ['what' => $option['label'], 'count' => $option['source']]) }}</li>
                                    @endif
                                @endforeach
                                <li>{{ __('new_year_preview_promotions', ['promoted' => $preview['promoted'], 'learners' => $preview['learners'], 'leaving' => $preview['leaving']]) }}</li>
                            </ul>
                            <div class="page-form-actions">
                                <button type="button" class="btn btn-secondary" data-wizard-back>{{ __('new_year_back') }}</button>
                                <button type="button" class="btn btn-theme" data-wizard-next>{{ __('new_year_next') }}</button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Step 5: make it current --}}
                <section class="wizard-panel" data-wizard-step="5" aria-labelledby="new-year-step-5">
                    <h2 class="page-section-title" id="new-year-step-5" tabindex="-1">5. {{ $steps[5] }}</h2>
                    <div class="card page-section">
                        <div class="card-body">
                            <p class="page-card-title">{{ __('new_year_switch_title', ['next' => $next->name]) }}</p>
                            <p class="page-card-subtitle">{{ __('new_year_switch_hint', ['next' => $next->name, 'current' => $current->name]) }}</p>
                            <div class="page-form-actions">
                                <button type="button" class="btn btn-secondary" data-wizard-back>{{ __('new_year_back') }}</button>
                                @if ($canSwitch)
                                    <button type="submit" class="btn btn-theme" data-submit
                                        data-confirm="{{ __('new_year_switch_confirm', ['next' => $next->name, 'current' => $current->name]) }}"
                                        data-confirm-title="{{ __('new_year_switch_button', ['next' => $next->name]) }}"
                                        data-confirm-label="{{ __('new_year_switch_button', ['next' => $next->name]) }}">
                                        {{ __('new_year_switch_button', ['next' => $next->name]) }}
                                    </button>
                                @else
                                    <p class="new-year-note">{{ __('new_year_switch_needs_permission') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            </form>
        @endif
    </div>
@endsection

@section('script')
    <script src="{{ asset('/assets/js/custom/new-year.js') }}?v={{ filemtime(public_path('assets/js/custom/new-year.js')) }}"></script>
@endsection
