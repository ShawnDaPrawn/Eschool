@extends('layouts.master')

@section('title')
    {{ __('section') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/wizard.css') }}">
@endsection

@section('content')
    @php
        $stepLabels = [
            __('about_us'),
            __('who_we_are'),
            __('Our Expert Teacher'),
            __('Educational Programs'),
            __('Our Events and News'),
            __('Our Photo'),
            __('Our Videos'),
            __('faqs'),
            __('Student Registration Form'),
            __('Download Our School Apps!'),
            __('Got a Question?'),
        ];
        $totalSteps = count($stepLabels);
    @endphp

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __('manage') . ' ' . __('section') }}</h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form id="edit-content-bulk" class="edit-content" method="POST" enctype="multipart/form-data"
                            action="{{ route('content.bulk_update') }}" novalidate="novalidate">
                            @csrf
                            <x-wizard :steps="$stepLabels">

                                <x-wizard.step :index="0" active title="{{ __('about_us') }}">
                                    <x-web-settings.section key="about" :model="$about" :rows="10" has-image />
                                    <x-wizard.nav :current="0" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="1" title="{{ __('who_we_are') }}">
                                    <x-web-settings.section key="whoweare" :model="$whoweare" :rows="10" has-image />
                                    <x-wizard.nav :current="1" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="2" title="{{ __('Our Expert Teacher') }}">
                                    <x-web-settings.section key="teacher" :model="$teacher" />
                                    <x-wizard.nav :current="2" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="3" title="{{ __('Educational Programs') }}">
                                    <x-web-settings.section key="program" :model="$program" />
                                    <x-wizard.nav :current="3" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="4" title="{{ __('Our Events and News') }}">
                                    <x-web-settings.section key="event" :model="$event" />
                                    <x-wizard.nav :current="4" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="5" title="{{ __('Our Photo') }}">
                                    <x-web-settings.section key="photo" :model="$photo" />
                                    <x-wizard.nav :current="5" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="6" title="{{ __('Our Videos') }}">
                                    <x-web-settings.section key="video" :model="$video" />
                                    <x-wizard.nav :current="6" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="7" title="{{ __('faqs') }}">
                                    <x-web-settings.section key="faq" :model="$faq" />
                                    <x-wizard.nav :current="7" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="8" title="{{ __('Student Registration Form') }}">
                                    <x-web-settings.section key="registration" :model="$registration" :has-content="false" />
                                    <x-wizard.nav :current="8" :total="$totalSteps" />
                                </x-wizard.step>

                                <x-wizard.step :index="9" title="{{ __('Download Our School Apps!') }}">
                                    <x-web-settings.section key="app" :model="$app" />
                                    <x-wizard.nav :current="9" :total="$totalSteps" />
                                </x-wizard.step>

                                {{-- Step 11 keeps its bespoke 3-column inline layout (tag / heading / status on one row) --}}
                                <x-wizard.step :index="10" title="{{ __('Got a Question?') }}">
                                    <input type="hidden" name="sections[question][id]"
                                        value="{{ $question->id ?? '' }}" />
                                    <div class="row">
                                        <div class="form-group col-sm-6 col-md-5">
                                            <label>{{ __('section_title') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('sections[question][tag]', $question?->tag ?? '', [
                                                'required',
                                                'placeholder' => __('tag'),
                                                'class' => 'form-control',
                                            ]) !!}
                                        </div>
                                        <div class="form-group col-sm-6 col-md-5">
                                            <label>{{ __('heading') }} <span class="text-danger">*</span></label>
                                            {!! Form::text('sections[question][heading]', $question?->heading ?? '', [
                                                'required',
                                                'placeholder' => __('heading'),
                                                'class' => 'form-control',
                                            ]) !!}
                                        </div>
                                        <x-form.status-radio name="sections[question][status]" :value="$question?->status"
                                            :col="2" />
                                    </div>
                                    <x-wizard.nav :current="10" :total="$totalSteps" last />
                                </x-wizard.step>

                            </x-wizard>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/js/wizard.js') }}"></script>
    <script>
        // Validate current step's required fields before advancing to next step.
        // Uses capture phase so this fires before wizard.js's bubble-phase listener.
        var wizardContainer = document.querySelector('#edit-content-bulk .wizard-container');
        if (wizardContainer) {
            wizardContainer.addEventListener('click', function (e) {
                var btn = e.target.closest('.wizard-next');
                if (!btn) return;

                var validator = $('#edit-content-bulk').data('validator');
                if (!validator) return;

                var currentStep = btn.closest('.wizard-step');
                var isStepValid = true;

                currentStep.querySelectorAll('input[required], textarea[required], select[required]').forEach(function (field) {
                    if (!validator.element(field)) {
                        isStepValid = false;
                    }
                });

                if (!isStepValid) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
            }, true);
        }

        // Single AJAX submit for all 11 sections
        $('#edit-content-bulk').on('submit', function(e) {
            e.preventDefault();

            // Validate current (last) step's visible required fields
            if (!$(this).valid()) return;

            var formElement = $(this);
            var submitButtonElement = $(this).find('.wizard-submit-all');
            var url = $(this).attr('action');
            var data = new FormData(this);

            function successCallback() {
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            }

            formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
        });
    </script>
@endsection
