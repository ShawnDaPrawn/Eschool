@extends('layouts.master')

@section('title')
    {{ __('class') . ' ' . __('subject') }}
@endsection

@section('content')
    @php
        $includeTermsForSession = $class->getIncludeTermsForSession($sessionYearId);
        $classLabel = trim($class->name . ' - ' . $class->medium->name . ' ' . ($class->streams->name ?? ''));
        if ($includeTermsForSession === null) {
            $termLabel = 'Not Configured';
            $termVariant = 'secondary';
        } elseif ($includeTermsForSession) {
            $termLabel = __('yes');
            $termVariant = 'success';
        } else {
            $termLabel = __('no');
            $termVariant = 'light';
        }
    @endphp
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('edit') . ' ' . __('class') . ' ' . __('subject') }}
            </h3>
        </div>
        <div class="note-banner note-banner-danger mb-3" role="alert">
            <x-icon name="alert" :size="16" />
            <span>
                <strong>{{ __('note') }}:</strong>
                {{ __('editing_class_subjects_may_affect_students_data_as_students_may_associated_with_the_subjects') }}
            </span>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form class="assign-class-subject-form" id="create-form-with-redirect"
                            data-pre-submit-function="classValidation" action="{{ route('class.subject.update') }}"
                            novalidate="novalidate" data-redirect-url="{{ route('class.subject') }}">

                            <input type="hidden" name="class_id" id="class_id" value="{{ $class->id }}" />
                            {{-- Save to the session year shown on this page, not whichever one the header has selected. --}}
                            <input type="hidden" name="session_year_id" value="{{ $sessionYearId }}" />

                            {{-- Read-only class context --}}
                            <div class="cs-context">
                                <div class="cs-context__item">
                                    <span class="cs-context__icon">
                                        <x-icon name="academics" :size="20" />
                                    </span>
                                    <div>
                                        <span class="cs-context__label">{{ __('class') }}</span>
                                        <span class="cs-context__value">{{ $classLabel }}</span>
                                    </div>
                                </div>
                                <div class="cs-context__item">
                                    <span class="cs-context__icon">
                                        <x-icon name="session-year" :size="20" />
                                    </span>
                                    <div>
                                        <span class="cs-context__label">{{ __('session_year') }}</span>
                                        <span class="cs-context__value">{{ $sessionYearName }}</span>
                                    </div>
                                </div>
                                <div class="cs-context__item">
                                    <span class="cs-context__icon">
                                        <x-icon name="session-year" :size="20" />
                                    </span>
                                    <div>
                                        <span class="cs-context__label">{{ __('include_terms') }}</span>
                                        <span class="cs-context__value">
                                            <span class="badge badge-{{ $termVariant }}">{{ $termLabel }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- ===================== Core Subject ===================== --}}
                            <div class="cs-section">
                                <div class="cs-section__head">
                                    <h5 class="cs-section__title">{{ __('core_subject') }}</h5>
                                    <p class="cs-section__desc">{{ __('core_subject_compulsory') }}</p>
                                </div>

                                {{-- Template for old core subject --}}
                                <div class="cs-subject-row edit-core-subject-div" style="display: none;">
                                    @if ($includeTermsForSession)
                                        <div class="cs-field cs-field--term term-div">
                                            <label class="cs-field__label">{{ __('term') }}</label>
                                            <select name="edit_core_subject[0][term_id]"
                                                class="form-control edit-core-subject-term-id" required
                                                disabled="true">
                                                <option value="" hidden="">-- {{ __('Select Term') }} --</option>
                                                @foreach ($terms as $term)
                                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="cs-field cs-field--subject">
                                        <label class="cs-field__label">{{ __('subject') }}</label>
                                        <input type="hidden" name="edit_core_subject[0][class_subject_id]"
                                            class="edit-class-subject-id form-control" disabled="true">
                                        <select name="edit_core_subject[0][subject_id]"
                                            class="edit-core-subject-id form-control subject" required="required"
                                            disabled="true">
                                            <option value="">{{ __('select_subject') }}</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->id }}"
                                                    data-medium-id="{{ $subject->medium_id }}">
                                                    {{ $subject->name }} - {{ $subject->type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cs-field--action">
                                        <button type="button" data-id="123"
                                            class="btn btn-icon btn-danger remove-core-subject"
                                            data-related='["online exams","online exam questions","elective subject group"]'
                                            title="{{ __('remove_core_subject') }}">
                                            <x-icon name="close" :size="16" />
                                        </button>
                                    </div>
                                </div>

                                {{-- Template for New Core Subject --}}
                                <div class="cs-subject-row core-subject-div" style="display: none;">
                                    @if ($includeTermsForSession)
                                        <div class="cs-field cs-field--term">
                                            <label class="cs-field__label">{{ __('term') }}</label>
                                            <select name="core_subjects[0][term_id]" class="form-control select2"
                                                disabled="true" required>
                                                <option value="" hidden="">-- {{ __('Select Term') }} --</option>
                                                @foreach ($terms as $term)
                                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="cs-field cs-field--subject">
                                        <label class="cs-field__label">{{ __('subject') }}</label>
                                        <select name="core_subjects[0][subject_id]"
                                            class="core-subject-id form-control subject" required="required"
                                            disabled="true">
                                            <option value="">{{ __('select_subject') }}</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->id }}"
                                                    data-medium-id="{{ $subject->medium_id }}">
                                                    {{ $subject->name }} - {{ $subject->type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="cs-field--action">
                                        <button type="button" class="btn btn-success btn-icon add-core-subject"
                                            title="{{ __('add_new_core_subject') }}">
                                            <x-icon name="plus" :size="16" class="icon-core-add" />
                                            <x-icon name="close" :size="16" class="icon-core-remove"
                                                style="display: none;" />
                                        </button>
                                    </div>
                                </div>

                                {{-- Dynamic New Core Subject will be added in this DIV --}}
                                <div class="edit-extra-core-subjects"></div>

                                <button type="button" class="btn btn-outline-primary btn-block cs-add-section add-new-core-subject">
                                    <x-icon name="plus" :size="16" /> {{ __('add_new_core_subject') }}
                                </button>
                            </div>

                            <hr class="cs-divider">

                            {{-- ===================== Elective Subject ===================== --}}
                            <div class="cs-section">
                                <div class="cs-section__head">
                                    <h5 class="cs-section__title">{{ __('elective_subject') }}</h5>
                                    <p class="cs-section__desc">{{ __('elective_subject_choiceable') }}</p>
                                </div>

                                {{-- Template for Old Elective Subjects --}}
                                <div id="edit-elective-subject-group-div" class="cs-egroup edit-elective-subject-group-div"
                                    style="display: none;">
                                    <input type="hidden" name="edit_elective_subjects[0][subject_group_id]"
                                        class="edit-elective-subject-group-id form-control" disabled="true" />
                                    <div class="cs-group__head">
                                        <h6 class="cs-group__title group-no">{{ __('group') }}</h6>
                                        <x-icon name="trash" :size="18"
                                            class="cs-group__remove remove-elective-subject-group" />
                                    </div>

                                    @if ($includeTermsForSession)
                                        <div class="cs-field cs-field--term mb-3">
                                            <label class="cs-field__label">{{ __('term') }}</label>
                                            <select name="edit_elective_subjects[0][term_id]"
                                                class="form-control select2 edit-elective-subject-term-id"
                                                disabled="true" required>
                                                <option value="" hidden="">-- {{ __('Select Term') }} --</option>
                                                @foreach ($terms as $term)
                                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <label class="cs-field__label cs-electives__label">{{ __('subject') }}</label>
                                    <div class="cs-electives">
                                        <div class="elective-subject-div">
                                            <input type="hidden" name="edit_elective_subjects[0][class_subject_id][0]"
                                                class="edit-elective-subject-class-id form-control" disabled="true" />
                                            <select name="edit_elective_subjects[0][subject_id][0]"
                                                class="form-control edit-elective-subject-name subject" disabled="true"
                                                required="required">
                                                <option value="">{{ __('select_subject') }}</option>
                                                @foreach ($subjects as $subject)
                                                    <option value="{{ $subject->id }}"
                                                        data-medium-id="{{ $subject->medium_id }}">
                                                        {{ $subject->name }} - {{ $subject->type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-icon name="close" :size="14" class="remove-elective-subject"
                                                style="visibility: hidden;" />
                                        </div>
                                        <span class="or">{{ __('or') }}</span>
                                        <div class="elective-subject-div">
                                            <input type="hidden" name="edit_elective_subjects[0][class_subject_id][1]"
                                                class="edit-elective-subject-class-id form-control" disabled="true" />
                                            <select name="edit_elective_subjects[0][subject_id][1]"
                                                class="form-control edit-elective-subject-name subject" disabled="true"
                                                required="required">
                                                <option value="">{{ __('select_subject') }}</option>
                                                @foreach ($subjects as $subject)
                                                    <option value="{{ $subject->id }}"
                                                        data-medium-id="{{ $subject->medium_id }}">
                                                        {{ $subject->name }} - {{ $subject->type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-icon name="close" :size="14" class="remove-elective-subject"
                                                style="visibility: hidden;" />
                                        </div>
                                        <button type="button"
                                            class="btn btn-success btn-icon add-new-elective-subject"
                                            title="{{ __('add_new_elective_subject') }}" value="1">
                                            <x-icon name="plus" :size="16" />
                                        </button>
                                    </div>

                                    <div class="cs-total-row">
                                        <div class="cs-field cs-total">
                                            <label class="cs-field__label">{{ __('total_selectable_subjects') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input name="edit_elective_subjects[0][total_selectable_subjects]"
                                                type="number" placeholder="{{ __('total_selectable_subjects') }}"
                                                class="form-control edit-total-selectable-subject" min="1"
                                                disabled="disabled" required />
                                        </div>
                                    </div>
                                </div>

                                {{-- Template for New Elective Subjects --}}
                                <div id="elective-subject-group-div" class="cs-egroup elective-subject-group-div"
                                    style="display: none;">
                                    <div class="cs-group__head">
                                        <h6 class="cs-group__title group-no">{{ __('group') }}</h6>
                                        <x-icon name="trash" :size="18"
                                            class="cs-group__remove remove-elective-subject-group"
                                            data-related='[
                                                "class subjects",
                                                "student subject selections",
                                                "online exams",
                                                "online exam questions"
                                            ]' />
                                    </div>

                                    @if ($includeTermsForSession)
                                        <div class="cs-field cs-field--term mb-3">
                                            <label class="cs-field__label">{{ __('term') }}</label>
                                            <select name="elective_subjects[0][term_id]"
                                                class="form-control select2 terms" disabled="true" required>
                                                <option value="" hidden="">-- {{ __('Select Term') }} --</option>
                                                @foreach ($terms as $term)
                                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <label class="cs-field__label cs-electives__label">{{ __('subject') }}</label>
                                    <div class="cs-electives">
                                        <div class="elective-subject-div">
                                            <select name="elective_subjects[0][subject_id][0]"
                                                class="form-control elective-subject-name subject" disabled="true"
                                                required="required">
                                                <option value="">{{ __('select_subject') }}</option>
                                                @foreach ($subjects as $subject)
                                                    <option value="{{ $subject->id }}"
                                                        data-medium-id="{{ $subject->medium_id }}">
                                                        {{ $subject->name }} - {{ $subject->type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-icon name="close" :size="14" class="remove-elective-subject"
                                                style="visibility: hidden;" />
                                        </div>
                                        <span class="or">{{ __('or') }}</span>
                                        <div class="elective-subject-div">
                                            <select name="elective_subjects[0][subject_id][1]"
                                                class="form-control elective-subject-name subject" disabled="true"
                                                required="required">
                                                <option value="">{{ __('select_subject') }}</option>
                                                @foreach ($subjects as $subject)
                                                    <option value="{{ $subject->id }}"
                                                        data-medium-id="{{ $subject->medium_id }}">
                                                        {{ $subject->name }} - {{ $subject->type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-icon name="close" :size="14" class="remove-elective-subject"
                                                style="visibility: hidden;" />
                                        </div>
                                        <button type="button"
                                            class="btn btn-success btn-icon add-new-elective-subject"
                                            title="{{ __('add_new_elective_subject') }}" value="1">
                                            <x-icon name="plus" :size="16" />
                                        </button>
                                    </div>

                                    <div class="cs-total-row">
                                        <div class="cs-field cs-total">
                                            <label class="cs-field__label">{{ __('total_selectable_subjects') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input name="elective_subjects[0][total_selectable_subjects]" type="number"
                                                placeholder="{{ __('total_selectable_subjects') }}"
                                                class="form-control total-selectable-subject" min="1" disabled="disabled"
                                                required />
                                        </div>
                                    </div>
                                </div>

                                {{-- Dynamic New Elective Subject Group will be added in this DIV --}}
                                <div id="edit-extra-elective-subject-group"></div>

                                <button type="button" class="btn btn-outline-primary btn-block cs-add-section add-elective-subject-group">
                                    <x-icon name="plus" :size="16" /> {{ __('add_new_elective_subject') }}
                                </button>
                            </div>

                            <div class="cs-footer">
                                <input class="btn btn-theme" type="submit" value="{{ __('submit') }}" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        // $(document).on('change', '.subject', function() {
        //     // $("#create-form").validate().element($(this));
        // });

        $(document).ready(function() {

            @if ($class->coreSubject)
                var coreSubjects = @json($class->coreSubject);
                var tempIndex = 0;
                coreSubjects.forEach(function(coreSubject, index) {

                    let core_subject = cloneOldCoreSubjectTemplate();
                    //Fill the Values
                    core_subject.find('.remove-core-subject').attr('data-id', coreSubject.id);
                    core_subject.find('.edit-class-subject-id').val(coreSubject.id).prop('disabled', false);
                    core_subject.find('.edit-core-subject-id').val(coreSubject.subject_id).prop('disabled',
                        false);

                    if (coreSubject.term_id) {
                        core_subject.find('.edit-core-subject-term-id').val(coreSubject.term_id)
                            .prop('disabled', false);
                    }
                    $('.edit-extra-core-subjects').append(core_subject);
                });
            @endif


            @if ($class->electiveSubjectGroup)
                var electiveSubjectGroups = @json($class->electiveSubjectGroup);

                electiveSubjectGroups.forEach(function(group, index) {
                    let subjectGroup = cloneOldElectiveSubjectGroup();
                    $('#edit-extra-elective-subject-group').append(subjectGroup);

                    // Fill the values in cloned element
                    subjectGroup.find('.edit-total-selectable-subject').val(group.total_selectable_subjects)
                        .attr('max', group.total_subjects - 1);
                    subjectGroup.find('.remove-elective-subject-group').attr('data-id', group.id);

                    // Set Group ID and Term ID
                    subjectGroup.find('.edit-elective-subject-group-id').val(group.id);
                    subjectGroup.find('.edit-elective-subject-term-id').val(group.term_id);

                    var electiveSubjects = group.elective_subjects;

                    electiveSubjects.forEach(function(elective_subject, key) {
                        if (key === 0) {
                            subjectGroup.find('.edit-elective-subject-name:first').val(
                                elective_subject.subject_id);
                            subjectGroup.find('.edit-elective-subject-name:first').siblings(
                                '.edit-elective-subject-class-id').val(elective_subject.id);
                        } else if (key === 1) {
                            subjectGroup.find('.edit-elective-subject-name:eq(1)').val(
                                elective_subject.subject_id);
                            subjectGroup.find('.edit-elective-subject-name:eq(1)').siblings(
                                '.edit-elective-subject-class-id').val(elective_subject.id);
                        } else {
                            let electiveSubjectButton = subjectGroup.find(
                                '.add-new-elective-subject');
                            let electiveSubject = cloneNewElectiveSubject(electiveSubjectButton);
                            electiveSubject.insertBefore(subjectGroup.find(
                                '.add-new-elective-subject'));
                            electiveSubject.find('.edit-elective-subject-name').val(elective_subject
                                .subject_id);
                            electiveSubject.find('.edit-elective-subject-name').siblings(
                                '.edit-elective-subject-class-id').val(elective_subject.id);
                            electiveSubject.find('.edit-elective-subject-name').siblings(
                                '.remove-elective-subject').attr('data-id', elective_subject.id);
                        }
                    });

                    // Remove any extra cloned elements if there are less than 3 subjects
                    let totalSubjects = electiveSubjects.length;
                    let extraInputs = subjectGroup.find('.edit-elective-subject-name').length;

                    // Remove excess fields if not needed
                    if (totalSubjects < extraInputs) {
                        subjectGroup.find('.edit-elective-subject-name:gt(' + (totalSubjects - 1) + ')')
                            .closest('.elective-subject-div').remove();
                    }
                    // Keep an "or" only where it sits between two subjects (N subjects → N-1 separators)
                    subjectGroup.find('.or').each(function () {
                        if (!$(this).prev().hasClass('elective-subject-div') ||
                            !$(this).next().hasClass('elective-subject-div')) {
                            $(this).remove();
                        }
                    });
                });
            @endif

            $('.terms').trigger('change');

        });
    </script>
@endsection
