<!-- Delete/Clear Session Data Modal -->
<div class="modal fade" id="deleteSessionModal" tabindex="-1" role="dialog" aria-labelledby="deleteSessionModalLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content delete-session-modal-content">
            <!-- Step 1: Delete or Clear Choice -->
            <div class="modal-step" id="step-1">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteSessionModalLabel">{{ __('delete_or_clear_session_data') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="session-year-label mb-3">{{ __('session_year') }}: <span class="session-year-value"></span></p>

                    <p class="mb-3 font-weight-medium">{{ __('choose_how_to_handle_session') }}</p>

                    <div class="choice-option mb-3">
                        <label class="custom-radio-container">
                            <input type="radio" name="delete_option" value="delete_entire" checked>
                            <span class="radio-checkmark"></span>
                            <div class="radio-content">
                                <div class="radio-title">{{ __('delete_entire_session') }}</div>
                                <div class="radio-description">{{ __('delete_entire_session_desc') }}</div>
                            </div>
                        </label>
                    </div>

                    <div class="choice-option">
                        <label class="custom-radio-container">
                            <input type="radio" name="delete_option" value="clear_specific">
                            <span class="radio-checkmark"></span>
                            <div class="radio-content">
                                <div class="radio-title">{{ __('clear_specific_data') }}</div>
                                <div class="radio-description">{{ __('clear_specific_data_desc') }}</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                    <button type="button" class="btn btn-theme continue-btn"
                        id="continueBtn">{{ __('continue') }}</button>
                </div>
            </div>

            <!-- Step 2: Delete Entire Session Confirmation -->
            <div class="modal-step" id="step-2" style="display: none;">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-start w-100">
                        <div class="delete-icon-container mr-3">
                            <i class="fa fa-trash"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title mb-1">{{ __('delete_entire_session') }}?</h5>
                            <p class="session-year-label mb-0">{{ __('session_year') }}: <span class="session-year-value"></span>
                            </p>
                        </div>
                        <button type="button" class="close ml-3" data-dismiss="modal" aria-label="{{ __('close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="warning-box mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-triangle warning-icon mr-2"></i>
                            <div>
                                <div class="warning-title">{{ __('warning') }}:</div>
                                <div class="warning-text">{{ __('delete_session_warning_text') }}</div>
                            </div>
                        </div>
                    </div>

                    <ul class="delete-items-list" id="relatedDataList">
                        <!-- Will be populated dynamically -->
                    </ul>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                    <button type="button" class="btn btn-danger delete-session-btn" id="deleteSessionBtn">
                        <i class="fa fa-trash"></i> {{ __('delete_session') }}
                    </button>
                </div>
            </div>

            <!-- Step 3: Clear Specific Data -->
            <div class="modal-step" id="step-3" style="display: none;">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-start w-100">
                        <div class="delete-icon-container mr-3">
                            <i class="fa fa-trash"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title mb-1">{{ __('clear_session_data') }}</h5>
                            <p class="session-year-label mb-0">{{ __('session_year') }}: <span class="session-year-value"></span>
                            </p>
                        </div>
                        <button type="button" class="close ml-3" data-dismiss="modal" aria-label="{{ __('close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="warning-box mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-triangle warning-icon mr-2"></i>
                            <div>
                                <div class="warning-title">{{ __('warning') }}:</div>
                                <div class="warning-text">{{ __('clear_session_warning_text') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="section-title">{{ __('select_data_to_clear') }}</div>
                            <div class="section-subtitle">{{ __('select_data_to_clear_desc') }}</div>
                        </div>
                        <button type="button" class="btn btn-link select-all-btn" id="selectAllBtn">{{ __('select_all') }}</button>
                    </div>

                    <div class="clear-data-options">

                        {{-- Class Subject --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="class_subject"
                                {{ in_array('class_subject', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('class_subject') }}</div>
                                <div class="checkbox-description">{{ __('remove_all_class_subject_assignments') }}</div>
                            </div>
                        </label>

                        {{-- Class Teachers & Subject Teachers --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="class_teachers"
                                {{ in_array('class_teachers', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('class_teachers_and_subject_teachers') }}</div>
                                <div class="checkbox-description">{{ __('clear_all_teacher_assignments') }}</div>
                            </div>
                        </label>



                        {{-- Notifications --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="notifications"
                                {{ in_array('notifications', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('notifications') }}</div>
                                <div class="checkbox-description">{{ __('remove_all_notification_records') }}</div>
                            </div>
                        </label>

                        {{-- Timetable --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="timetable"
                                {{ in_array('timetable', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('timetable') }}</div>
                                <div class="checkbox-description">{{ __('clear_all_timetable_entries') }}</div>
                            </div>
                        </label>

                        {{-- Student Attendance --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="attendance"
                                {{ in_array('attendance', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('student_attendance') }}</div>
                                <div class="checkbox-description">{{ __('clear_all_attendance_records') }}</div>
                            </div>
                        </label>

                        {{-- Assignments --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="assignments"
                                {{ in_array('assignments', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('assignment') }}</div>
                                <div class="checkbox-description">{{ __('remove_all_assignment_data') }}</div>
                            </div>
                        </label>

                        {{-- Online / Offline Exam --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="exams"
                                {{ in_array('exams', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('online_offline_exam') }}</div>
                                <div class="checkbox-description">{{ __('clear_all_exam_records') }}</div>
                            </div>
                        </label>

                        {{-- Grade --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="grades"
                                {{ in_array('grades', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('grade') }}</div>
                                <div class="checkbox-description">{{ __('delete_all_grade_data') }}</div>
                            </div>
                        </label>

                        {{-- Announcements --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="announcements"
                                {{ in_array('announcements', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('announcements') }}</div>
                                <div class="checkbox-description">{{ __('remove_all_announcements') }}</div>
                            </div>
                        </label>

                        {{-- Holidays --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="holidays"
                                {{ in_array('holidays', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('holidays') }}</div>
                                <div class="checkbox-description">{{ __('clear_all_holiday_records') }}</div>
                            </div>
                        </label>

                        {{-- Events --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="events"
                                {{ in_array('events', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('events') }}</div>
                                <div class="checkbox-description">{{ __('delete_all_event_records') }}</div>
                            </div>
                        </label>

                        {{-- Student and Staff Leave --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="leaves"
                                {{ in_array('leaves', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('student_and_staff_leave') }}</div>
                                <div class="checkbox-description">{{ __('remove_all_leave_records') }}</div>
                            </div>
                        </label>

                        {{-- Allowed Leave Days --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="allowed_leave_days"
                                {{ in_array('allowed_leave_days', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">{{ __('allowed_leave_days') }}</div>
                                <div class="checkbox-description">{{ __('clear_leave_day_configurations') }}</div>
                            </div>
                        </label>

                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                    <button type="button" class="btn btn-danger clear-data-btn" id="clearDataBtn" disabled>
                        <i class="fa fa-trash"></i> {{ __('clear_data') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
