<div class="modal fade" id="createSessionYearModal" tabindex="-1" role="dialog"
    aria-labelledby="createSessionYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content create-session-year-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="createSessionYearModalLabel">{{ __('create_new_session_year') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="createSessionYearForm">
                    @csrf
                    <!-- Basic Details Section -->
                    <div class="section-header">
                        <i class="fa fa-calendar"></i>
                        <span>{{ __('basic_details') }}</span>
                    </div>

                    <div class="form-group">
                        <label for="sessionName">{{ __('session_name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sessionName" name="name"
                            placeholder="{{ __('eg_session_year_format') }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startDate">{{ __('start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker-popup" id="startDate"
                                    name="start_date" placeholder="{{ __('select_start_date') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endDate">{{ __('end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker-popup" id="endDate"
                                    name="end_date" placeholder="{{ __('select_end_date') }}" required>
                            </div>
                        </div>
                    </div>





                    <div class="installment-toggle-wrapper">
                        <div class="installment-toggle-label">
                            <span>{{ __('make_this_the_current_session_year') }}</span>
                            <small>{{ __('make_this_the_current_session_year_desc') }}</small>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="setAsCurrentSessionYear" name="set_as_current" value="1"
                                {{ !empty($currentYearEnded) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Data Transfer Options Section -->
                    <div class="section-header">
                        <i class="fa fa-database"></i>
                        <span>{{ __('data_transfer_options') }}</span>
                    </div>

                    <div class="data-transfer-options">

                        {{-- Source Session Year Picker (always visible) --}}
                        <div class="form-group" id="sourceSessionYearWrapper">
                            <label for="sourceSessionYear">
                                {{ __('import_data_from_session_year') }} <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="sourceSessionYear" name="source_session_year_id">
                                <option value="">{{ __('select_session_year') }}</option>
                                @if (!empty($sessionYears))
                                    @foreach ($sessionYears as $sy)
                                        <option value="{{ $sy->id }}">{{ $sy->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="form-text text-muted">
                                {{ __('data_transfer_help') }}
                            </small>
                        </div>

                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_term" value="1" class="transfer-checkbox"
                                id="transferTermCheckbox">
                            <span>{{ __('term') }}</span>
                        </label>

                        <!-- Term list (auto-populated from selected session year) -->
                        <div id="termTransferContent" style="display: none;">
                            <div id="termTransferList">
                                {{-- Populated dynamically via AJAX --}}
                            </div>
                            <p id="termEmptyMsg" class="text-muted small m-0" style="display: none;">
                                {{-- No terms found for the selected session year. --}}
                            </p>
                            <p id="termLoadingMsg" class="text-muted small" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> {{ __('loading_terms') }}
                            </p>
                        </div>

                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_subject" value="1"
                                class="transfer-checkbox">
                            <span>{{ __('class_subject') }}</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_teacher_subject" value="1"
                                class="transfer-checkbox">
                            <span>{{ __('class_teacher_and_subject_teacher') }}</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_timetable" value="1"
                                class="transfer-checkbox">
                            <span>{{ __('class_timetable') }}</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_exam_grades" value="1"
                                class="transfer-checkbox">
                            <span>{{ __('exam_grades') }}</span>
                        </label>

                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_leave_settings" value="1"
                                class="transfer-checkbox">
                            <span>{{ __('leave_settings') }}</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                <button type="button" class="btn btn-theme" id="submitSessionYear">{{ __('create_session_year') }}</button>
            </div>
        </div>
    </div>
</div>
