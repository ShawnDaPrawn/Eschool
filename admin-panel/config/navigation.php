<?php

/*
|--------------------------------------------------------------------------
| Admin panel navigation
|--------------------------------------------------------------------------
|
| The panel's navigation, by job rather than by module: eight sections, each
| with groups of pages. App\Services\Panel\Navigation builds the sidebar, the
| global search's page results and the breadcrumbs from this registry.
|
| Each item:
|   key          unique, stable id (tests and search use it)
|   label        translation key, in plain words ("Learners", not "Students")
|   route        the page's route name, or
|   url          its path when the route has no name
|   permissions  the person needs any one of these to see the item (empty: every
|                panel user); the page still checks its own permission
|   roles        optional: the person needs one of these roles as well
|   module       the module switch Phase 1 checks (config/modules.php); null for
|                pages that are always on. Ignored until then.
|   active       optional route-name or path patterns that mark the item as the
|                current page (default: its route name and the paths below it)
|   keywords     extra words the global search matches (English)
|
| Items whose route does not exist are left out, so a page can be removed
| without breaking the navigation. A section with no visible item is hidden.
|
*/

return [

    'sections' => [

        'home' => [
            'label' => 'nav_home',
            'icon' => 'home',
            'groups' => [
                'home' => [
                    'label' => 'nav_home',
                    'items' => [
                        ['key' => 'home', 'label' => 'nav_home', 'route' => 'home', 'permissions' => [], 'module' => null,
                            'keywords' => ['dashboard', 'today', 'setup checklist', 'overview']],
                    ],
                ],
            ],
        ],

        'people' => [
            'label' => 'nav_people',
            'icon' => 'people',
            'groups' => [
                'learners' => [
                    'label' => 'nav_group_learners',
                    'items' => [
                        ['key' => 'learners', 'label' => 'nav_learners', 'route' => 'students.index', 'permissions' => ['student-list', 'class-teacher', 'generate-document'], 'module' => null,
                            'active' => ['students.index', 'students.list'], 'keywords' => ['students', 'pupils', 'learner details', 'find a learner']],
                        ['key' => 'add-learner', 'label' => 'nav_add_learner', 'route' => 'students.create', 'permissions' => ['student-create'], 'module' => null,
                            'keywords' => ['admission', 'admit', 'enrol', 'enroll', 'new learner', 'add student', 'register a learner']],
                        ['key' => 'import-learners', 'label' => 'nav_import_learners', 'route' => 'students.create-bulk-data', 'permissions' => [], 'roles' => ['Super Admin'], 'module' => null,
                            'keywords' => ['bulk upload', 'excel', 'csv', 'import students']],
                        ['key' => 'applications', 'label' => 'nav_applications', 'route' => 'online-registration.index', 'permissions' => ['online-registration-list'], 'module' => 'intake',
                            'keywords' => ['online registration', 'admissions', 'apply', 'pending learners']],
                        ['key' => 'place-new-learners', 'label' => 'nav_place_new_learners', 'route' => 'students.assign-class', 'permissions' => ['assign-class-to-new-student'], 'module' => null,
                            'keywords' => ['assign class', 'new students', 'homeroom']],
                        ['key' => 'roll-numbers', 'label' => 'nav_roll_numbers', 'route' => 'students.index-students-roll-number', 'permissions' => ['student-create'], 'module' => null,
                            'keywords' => ['roll no', 'class list order']],
                        ['key' => 'learner-categories', 'label' => 'nav_learner_categories', 'route' => 'category.index', 'permissions' => ['category-create'], 'module' => null,
                            'keywords' => ['student category']],
                        ['key' => 'learner-id-cards', 'label' => 'nav_learner_id_cards', 'route' => 'generate_id.index', 'permissions' => ['generate-id-card'], 'module' => 'cards',
                            'keywords' => ['id card', 'student card', 'print cards']],
                        ['key' => 'learner-passwords', 'label' => 'nav_learner_passwords', 'route' => 'students.reset_password', 'permissions' => ['student-reset-password'], 'module' => null,
                            'keywords' => ['reset password', 'sign in', 'login']],
                    ],
                ],
                'parents' => [
                    'label' => 'nav_group_parents',
                    'items' => [
                        ['key' => 'parents', 'label' => 'nav_parents', 'route' => 'parents.index', 'permissions' => ['parents-list'], 'module' => null,
                            'keywords' => ['guardians', 'mothers', 'fathers', 'families']],
                    ],
                ],
                'staff' => [
                    'label' => 'nav_group_staff',
                    'items' => [
                        ['key' => 'teachers', 'label' => 'nav_teachers', 'route' => 'teacher.details', 'permissions' => ['teacher-list'], 'module' => null,
                            'keywords' => ['educators', 'teacher details', 'find a teacher']],
                        ['key' => 'add-teacher', 'label' => 'nav_add_teacher', 'route' => 'teachers.index', 'permissions' => ['teacher-create'], 'module' => null,
                            'keywords' => ['new teacher', 'create teacher', 'hire']],
                        ['key' => 'teacher-id-cards', 'label' => 'nav_teacher_id_cards', 'route' => 'generate_teacher_id.index', 'permissions' => ['generate-id-card'], 'module' => 'cards',
                            'keywords' => ['id card', 'teacher card']],
                        ['key' => 'staff', 'label' => 'nav_staff', 'route' => 'staff.index', 'permissions' => ['staff-list'], 'module' => null,
                            'keywords' => ['employees', 'admin staff', 'add staff', 'finance']],
                        ['key' => 'staff-id-cards', 'label' => 'nav_staff_id_cards', 'route' => 'generate_staff_id.index', 'permissions' => ['staff-list', 'generate-id-card'], 'module' => 'cards',
                            'keywords' => ['id card', 'staff card']],
                    ],
                ],
                'leave' => [
                    'label' => 'nav_group_staff_leave',
                    'items' => [
                        ['key' => 'apply-leave', 'label' => 'nav_apply_leave', 'route' => 'leave.index', 'permissions' => ['leave-create'], 'module' => null,
                            'keywords' => ['request leave', 'time off', 'my leave']],
                        ['key' => 'staff-leave-requests', 'label' => 'nav_staff_leave_requests', 'route' => 'leave-request.index', 'permissions' => ['leave-approve'], 'module' => null,
                            'keywords' => ['approve leave', 'leave approvals']],
                        ['key' => 'leave-report', 'label' => 'nav_leave_report', 'route' => 'leave-report.index', 'permissions' => ['leave-list', 'leave-approve'], 'module' => null,
                            'keywords' => ['leave balance', 'leave history']],
                        ['key' => 'leave-settings', 'label' => 'nav_leave_settings', 'route' => 'leave-master.index', 'permissions' => ['leave-setting-create'], 'module' => null,
                            'keywords' => ['leave days', 'allowed leave']],
                    ],
                ],
            ],
        ],

        'academics' => [
            'label' => 'nav_academics',
            'icon' => 'academics',
            'groups' => [
                'year' => [
                    'label' => 'nav_group_school_year',
                    'items' => [
                        ['key' => 'school-years', 'label' => 'nav_school_years', 'route' => 'session-years.index', 'permissions' => ['session-year-create'], 'module' => null,
                            'keywords' => ['session year', 'academic year', 'year dates']],
                        ['key' => 'terms', 'label' => 'nav_terms', 'route' => 'semester.index', 'permissions' => ['semester-create'], 'module' => null,
                            'keywords' => ['set up terms', 'term dates', 'semesters', 'quarters']],
                        ['key' => 'new-year', 'label' => 'nav_new_year', 'route' => 'new-year.index', 'permissions' => ['session-year-create'], 'module' => null,
                            'keywords' => ['year end', 'rollover', 'next year', 'new school year', 'wizard', 'carry forward']],
                        ['key' => 'promote-learners', 'label' => 'nav_promote_learners', 'route' => 'promote-student.index', 'permissions' => ['promote-student-list'], 'module' => null,
                            'keywords' => ['promotion', 'next grade', 'year end', 'promote students']],
                        ['key' => 'holidays', 'label' => 'nav_holidays', 'route' => 'holiday.index', 'permissions' => ['holiday-list'], 'module' => null,
                            'keywords' => ['public holidays', 'school calendar', 'closed days']],
                    ],
                ],
                'structure' => [
                    'label' => 'nav_group_grades_subjects',
                    'items' => [
                        ['key' => 'grades', 'label' => 'nav_grades_homerooms', 'route' => 'class.index', 'permissions' => ['class-create'], 'module' => null,
                            'keywords' => ['classes', 'class sections', 'homerooms', 'add a grade']],
                        ['key' => 'homeroom-names', 'label' => 'nav_homeroom_names', 'route' => 'section.index', 'permissions' => ['section-create'], 'module' => null,
                            'keywords' => ['sections', 'divisions', 'a b c']],
                        ['key' => 'subjects', 'label' => 'nav_subjects', 'route' => 'subject.index', 'permissions' => ['subject-create'], 'module' => null,
                            'keywords' => ['add a subject', 'learning areas']],
                        ['key' => 'grade-subjects', 'label' => 'nav_grade_subjects', 'route' => 'class.subject', 'permissions' => ['assign-class-subject'], 'module' => null,
                            'keywords' => ['class subjects', 'assign subjects', 'curriculum']],
                        ['key' => 'electives', 'label' => 'nav_electives', 'route' => 'class.select-elective-subjects', 'permissions' => ['assign-elective-subjects'], 'module' => null,
                            'keywords' => ['elective subjects', 'subject choices']],
                        ['key' => 'teaching-languages', 'label' => 'nav_teaching_languages', 'route' => 'medium.index', 'permissions' => ['medium-list', 'medium-create', 'medium-edit', 'medium-delete'], 'module' => null,
                            'keywords' => ['medium', 'medium of instruction', 'language of learning']],
                        ['key' => 'streams', 'label' => 'nav_streams', 'route' => 'stream.index', 'permissions' => ['stream-create'], 'module' => null,
                            'keywords' => ['stream']],
                        ['key' => 'shifts', 'label' => 'nav_shifts', 'route' => 'shifts.index', 'permissions' => ['shift-create'], 'module' => null,
                            'keywords' => ['morning', 'afternoon', 'shift']],
                    ],
                ],
                'allocation' => [
                    'label' => 'nav_group_teacher_allocation',
                    'items' => [
                        ['key' => 'homeroom-teachers', 'label' => 'nav_homeroom_teachers', 'route' => 'class.teacher', 'permissions' => ['class-teacher-create'], 'module' => null,
                            'keywords' => ['class teacher', 'register teacher', 'assign class teacher']],
                        ['key' => 'subject-teachers', 'label' => 'nav_subject_teachers', 'route' => 'subject-teachers.index', 'permissions' => ['subject-teacher-list', 'subject-teacher-create', 'subject-teacher-edit', 'subject-teacher-delete'], 'module' => null,
                            'keywords' => ['assign subject teacher', 'who teaches what']],
                    ],
                ],
                'timetable' => [
                    'label' => 'nav_group_timetable',
                    'items' => [
                        ['key' => 'build-timetable', 'label' => 'nav_build_timetable', 'route' => 'timetable.index', 'permissions' => ['timetable-create'], 'module' => null,
                            'keywords' => ['create timetable', 'periods', 'schedule']],
                        ['key' => 'homeroom-timetables', 'label' => 'nav_homeroom_timetables', 'url' => 'class-timetable', 'permissions' => ['class-timetable', 'class-teacher'], 'module' => null,
                            'active' => ['class-timetable*'], 'keywords' => ['class timetable']],
                        ['key' => 'teacher-timetables', 'label' => 'nav_teacher_timetables', 'url' => 'teacher-timetable', 'permissions' => ['teacher-timetable'], 'module' => null,
                            'active' => ['teacher-timetable*'], 'keywords' => ['teacher timetable', 'my timetable']],
                    ],
                ],
                'attendance' => [
                    'label' => 'nav_group_attendance',
                    'items' => [
                        ['key' => 'take-attendance', 'label' => 'nav_take_attendance', 'route' => 'attendance.index', 'permissions' => ['attendance-create'], 'module' => null,
                            'keywords' => ['register', 'mark the register', 'roll call', 'absent']],
                        ['key' => 'view-attendance', 'label' => 'nav_view_attendance', 'route' => 'attendance.view', 'permissions' => ['attendance-list'], 'module' => null,
                            'keywords' => ['attendance history']],
                        ['key' => 'import-attendance', 'label' => 'nav_import_attendance', 'route' => 'attendance.add-bulk-data', 'permissions' => ['attendance-create'], 'module' => null,
                            'keywords' => ['bulk attendance', 'excel']],
                        ['key' => 'attendance-report', 'label' => 'nav_attendance_report', 'route' => 'attendace_report.index', 'permissions' => ['attendance-report'], 'module' => null,
                            'keywords' => ['absence report']],
                        ['key' => 'staff-attendance', 'label' => 'nav_staff_attendance', 'route' => 'attendance.staff_attendance', 'permissions' => ['attendance-staff'], 'module' => null,
                            'keywords' => ['staff present', 'staff register']],
                        ['key' => 'staff-attendance-month', 'label' => 'nav_staff_attendance_month', 'route' => 'attendance.monthly', 'permissions' => ['attendance-staff'], 'module' => null,
                            'keywords' => ['monthly attendance']],
                    ],
                ],
                'teaching' => [
                    'label' => 'nav_group_lessons_assignments',
                    'items' => [
                        ['key' => 'lessons', 'label' => 'nav_lessons', 'route' => 'lesson.index', 'permissions' => ['lesson-list', 'lesson-create', 'lesson-edit', 'lesson-delete'], 'module' => null,
                            'active' => ['lesson.*', 'lesson'], 'keywords' => ['create lesson', 'lesson plans']],
                        ['key' => 'topics', 'label' => 'nav_topics', 'route' => 'lesson-topic.index', 'permissions' => ['topic-list', 'topic-create', 'topic-edit', 'topic-delete'], 'module' => null,
                            'keywords' => ['lesson topics']],
                        ['key' => 'assignments', 'label' => 'nav_assignments', 'route' => 'assignment.index', 'permissions' => ['assignment-create'], 'module' => null,
                            'active' => ['assignment.index'], 'keywords' => ['homework', 'create assignment']],
                        ['key' => 'submissions', 'label' => 'nav_submissions', 'route' => 'assignment.submission', 'permissions' => ['assignment-submission'], 'module' => null,
                            'keywords' => ['assignment submissions', 'handed in']],
                    ],
                ],
                'exams' => [
                    'label' => 'nav_group_exams_results',
                    'items' => [
                        ['key' => 'exams', 'label' => 'nav_exams', 'route' => 'exams.index', 'permissions' => ['exam-create'], 'module' => null,
                            'active' => ['exams.index'], 'keywords' => ['create exam', 'tests']],
                        ['key' => 'exam-timetable', 'label' => 'nav_exam_timetable', 'route' => 'exam-timetable.index', 'permissions' => ['exam-timetable-create'], 'module' => null,
                            'keywords' => ['exam dates', 'exam schedule']],
                        ['key' => 'capture-marks', 'label' => 'nav_capture_marks', 'route' => 'exams.upload-marks', 'permissions' => ['exam-upload-marks'], 'module' => null,
                            'keywords' => ['upload marks', 'enter marks', 'exam marks']],
                        ['key' => 'exam-results', 'label' => 'nav_exam_results', 'route' => 'exams.view-result', 'permissions' => ['view-exam-result'], 'module' => null,
                            'keywords' => ['results', 'publish results']],
                        ['key' => 'learner-results', 'label' => 'nav_learner_results', 'route' => 'exams.get-result', 'permissions' => ['exam-result'], 'module' => null,
                            'keywords' => ['student exam result']],
                        ['key' => 'result-sheets', 'label' => 'nav_result_sheets', 'route' => 'student.result.index', 'permissions' => ['generate-result'], 'module' => null,
                            'keywords' => ['generate result', 'report card', 'certificates']],
                        ['key' => 'grading-scale', 'label' => 'nav_grading_scale', 'route' => 'grades', 'permissions' => ['grade-create'], 'module' => null,
                            'keywords' => ['exam grade', 'symbols', 'mark bands']],
                        ['key' => 'learner-report', 'label' => 'nav_learner_report', 'route' => 'students.report', 'permissions' => ['student-report'], 'module' => null,
                            'keywords' => ['student report', 'progress report']],
                    ],
                ],
                'online-exams' => [
                    'label' => 'nav_group_online_exams',
                    'items' => [
                        ['key' => 'online-exams', 'label' => 'nav_online_exams', 'route' => 'online-exam.index', 'permissions' => ['manage-online-exam'], 'module' => 'online_exams',
                            'active' => ['online-exam.index'], 'keywords' => ['quiz', 'manage online exam']],
                        ['key' => 'question-bank', 'label' => 'nav_question_bank', 'route' => 'online-exam-question.index', 'permissions' => ['manage-online-exam'], 'module' => 'online_exams',
                            'active' => ['online-exam-question.index'], 'keywords' => ['questions', 'manage questions']],
                        ['key' => 'import-questions', 'label' => 'nav_import_questions', 'route' => 'online-exam-question.create-bulk', 'permissions' => ['manage-online-exam'], 'module' => 'online_exams',
                            'keywords' => ['bulk questions', 'excel']],
                        ['key' => 'online-exam-rules', 'label' => 'nav_online_exam_rules', 'route' => 'online-exam.terms-conditions', 'permissions' => ['manage-online-exam'], 'module' => 'online_exams',
                            'keywords' => ['online exam terms', 'exam instructions']],
                    ],
                ],
            ],
        ],

        'communication' => [
            'label' => 'nav_communication',
            'icon' => 'communication',
            'groups' => [
                'messages' => [
                    'label' => 'nav_group_messages',
                    'items' => [
                        ['key' => 'notices', 'label' => 'nav_notices', 'route' => 'announcement.index', 'permissions' => ['announcement-create'], 'module' => null,
                            'keywords' => ['send a notice', 'announcement', 'notice board', 'message parents', 'newsletter']],
                        ['key' => 'push-notifications', 'label' => 'nav_push_notifications', 'route' => 'notifications.index', 'permissions' => ['notification-create'], 'module' => null,
                            'keywords' => ['custom notification', 'send notification', 'alert', 'push message']],
                        ['key' => 'app-banners', 'label' => 'nav_app_banners', 'route' => 'sliders.index', 'permissions' => ['slider-create'], 'module' => null,
                            'keywords' => ['sliders', 'carousel', 'app home picture']],
                    ],
                ],
                'website' => [
                    'label' => 'nav_group_website',
                    'items' => [
                        ['key' => 'website-content', 'label' => 'nav_website_content', 'route' => 'content.index', 'permissions' => ['content-create'], 'module' => null,
                            'keywords' => ['web settings', 'content settings', 'public website']],
                        ['key' => 'programmes', 'label' => 'nav_programmes', 'route' => 'educational.index', 'permissions' => ['program-create'], 'module' => null,
                            'keywords' => ['educational program', 'programs']],
                        ['key' => 'website-faqs', 'label' => 'nav_website_faqs', 'route' => 'faq.index', 'permissions' => ['faq-create'], 'module' => null,
                            'keywords' => ['faq', 'questions']],
                        ['key' => 'website-messages', 'label' => 'nav_website_messages', 'route' => 'contact_us.index', 'permissions' => ['contact-us'], 'module' => null,
                            'keywords' => ['contact us', 'enquiries', 'inbox']],
                    ],
                ],
            ],
        ],

        'money' => [
            'label' => 'nav_money',
            'icon' => 'wallet',
            'groups' => [
                'fees' => [
                    'label' => 'nav_group_fees',
                    'items' => [
                        ['key' => 'fee-types', 'label' => 'nav_fee_types', 'route' => 'fees-type.index', 'permissions' => ['fees-type'], 'module' => 'fees',
                            'keywords' => ['tuition', 'fees type']],
                        ['key' => 'grade-fees', 'label' => 'nav_grade_fees', 'route' => 'fees.class.index', 'permissions' => ['fees-classes'], 'module' => 'fees',
                            'keywords' => ['assign fees', 'fee structure', 'class fees']],
                        ['key' => 'fees-paid', 'label' => 'nav_fees_paid', 'route' => 'fees.paid.index', 'permissions' => ['fees-paid'], 'module' => 'fees',
                            'keywords' => ['payments', 'receipts', 'collect fees', 'outstanding', 'record a payment']],
                        ['key' => 'payment-log', 'label' => 'nav_payment_log', 'route' => 'fees.transactions.log.index', 'permissions' => ['fees-paid'], 'module' => 'fees',
                            'keywords' => ['transactions', 'online payments']],
                        ['key' => 'fees-setup', 'label' => 'nav_fees_setup', 'route' => 'fees.config.index', 'permissions' => ['fees-config'], 'module' => 'fees',
                            'keywords' => ['currency', 'payment gateway', 'stripe', 'fees configuration']],
                    ],
                ],
            ],
        ],

        'activities' => [
            'label' => 'nav_activities',
            'icon' => 'activities',
            'groups' => [
                'events' => [
                    'label' => 'nav_group_events_gallery',
                    'items' => [
                        ['key' => 'events', 'label' => 'nav_events', 'route' => 'events.index', 'permissions' => ['event-list', 'event-create', 'event-edit', 'event-delete'], 'module' => null,
                            'keywords' => ['sports day', 'calendar', 'functions']],
                        ['key' => 'photos', 'label' => 'nav_photos', 'route' => 'photo.index', 'permissions' => ['media-create'], 'module' => null,
                            'keywords' => ['gallery', 'albums', 'pictures']],
                        ['key' => 'videos', 'label' => 'nav_videos', 'route' => 'video.index', 'permissions' => ['media-create'], 'module' => null,
                            'keywords' => ['gallery', 'youtube']],
                    ],
                ],
            ],
        ],

        'wellbeing' => [
            'label' => 'nav_wellbeing',
            'icon' => 'wellbeing',
            'groups' => [
                'absence' => [
                    'label' => 'nav_group_absence',
                    'items' => [
                        ['key' => 'learner-leave', 'label' => 'nav_learner_leave', 'route' => 'student-leave-request.index', 'permissions' => ['student-leave-approve'], 'module' => null,
                            'keywords' => ['absence notes', 'sick note', 'student leave', 'leave requests']],
                    ],
                ],
            ],
        ],

        'settings' => [
            'label' => 'nav_settings',
            'icon' => 'settings',
            'groups' => [
                'school' => [
                    'label' => 'nav_group_school',
                    'items' => [
                        ['key' => 'school-modules', 'label' => 'nav_school_modules', 'route' => 'school-modules.index', 'permissions' => ['setting-create'], 'module' => null,
                            'keywords' => ['features', 'switch', 'package', 'enabled modules']],
                        ['key' => 'notification-rules', 'label' => 'nav_notification_rules', 'route' => 'notification-rules.index', 'permissions' => ['setting-create'], 'module' => null,
                            'keywords' => ['mandatory notices', 'email', 'push', 'notification settings']],
                        ['key' => 'school-profile', 'label' => 'nav_school_profile', 'url' => 'settings', 'permissions' => ['setting-create'], 'module' => null,
                            'active' => ['settings'], 'keywords' => ['general settings', 'school name', 'logo', 'address', 'time zone', 'date format', 'school details']],
                        ['key' => 'app-settings', 'label' => 'nav_app_settings', 'url' => 'app-settings', 'permissions' => ['setting-create'], 'module' => null,
                            'active' => ['app-settings*'], 'keywords' => ['app version', 'force update', 'mobile app']],
                        ['key' => 'languages', 'label' => 'nav_languages', 'route' => 'language.index', 'permissions' => ['language-create'], 'module' => null,
                            'active' => ['language*'], 'keywords' => ['translation', 'language settings']],
                        ['key' => 'extra-fields', 'label' => 'nav_extra_fields', 'route' => 'form-fields.index', 'permissions' => ['form-field-list', 'form-field-create', 'form-field-edit', 'form-field-delete'], 'module' => null,
                            'keywords' => ['custom fields', 'additional fields', 'admission form']],
                        ['key' => 'id-card-design', 'label' => 'nav_id_card_design', 'route' => 'id_card_setting.index', 'permissions' => ['id-card-setting'], 'module' => 'cards',
                            'keywords' => ['id card settings', 'card colours']],
                        ['key' => 'roles', 'label' => 'nav_roles', 'route' => 'roles.index', 'permissions' => ['role-create'], 'module' => null,
                            'active' => ['roles.*'], 'keywords' => ['role permission', 'staff roles', 'access', 'who can do what']],
                    ],
                ],
                'messaging' => [
                    'label' => 'nav_group_email_notifications',
                    'items' => [
                        ['key' => 'email-setup', 'label' => 'nav_email_setup', 'route' => 'setting.email-config-index', 'permissions' => ['email-setting-create'], 'module' => null,
                            'keywords' => ['email configuration', 'smtp', 'mail server']],
                        ['key' => 'email-templates', 'label' => 'nav_email_templates', 'route' => 'email.Template', 'permissions' => ['email-setting-create'], 'module' => null,
                            'keywords' => ['email template', 'welcome email']],
                        ['key' => 'push-setup', 'label' => 'nav_push_setup', 'url' => 'fcm-settings', 'permissions' => ['fcm-setting-create'], 'module' => null,
                            'active' => ['fcm-settings*'], 'keywords' => ['firebase', 'fcm', 'notification setting']],
                        ['key' => 'chat-settings', 'label' => 'nav_chat_settings', 'route' => 'chat_setting.index', 'permissions' => ['chat-settings'], 'module' => 'chat',
                            'keywords' => ['chat', 'messages cleanup']],
                    ],
                ],
                'policies' => [
                    'label' => 'nav_group_policies',
                    'items' => [
                        ['key' => 'privacy-policy', 'label' => 'nav_privacy_policy', 'route' => 'privacy.index', 'permissions' => ['privacy-policy'], 'module' => null,
                            'keywords' => ['popia', 'privacy notice']],
                        ['key' => 'terms-and-conditions', 'label' => 'nav_terms_and_conditions', 'url' => 'terms-condition', 'permissions' => ['terms-condition'], 'module' => null,
                            'active' => ['terms-condition*'], 'keywords' => ['terms condition', 'school terms']],
                        ['key' => 'about-page', 'label' => 'nav_about_page', 'url' => 'about-us', 'permissions' => ['about-us'], 'module' => null,
                            'active' => ['about-us*'], 'keywords' => ['about us']],
                        ['key' => 'contact-page', 'label' => 'nav_contact_page', 'url' => 'contact-us', 'permissions' => ['contact-us'], 'module' => null,
                            'active' => ['contact-us'], 'keywords' => ['contact us page', 'school contact details']],
                    ],
                ],
            ],
        ],

    ],

];
