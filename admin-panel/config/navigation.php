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
                        ['key' => 'terms', 'label' => 'nav_terms', 'route' => 'term.index', 'permissions' => ['term-list'], 'module' => null,
                            'keywords' => ['set up terms', 'term dates', 'terms', 'quarters']],
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
                        ['key' => 'academic-sasams', 'label' => 'SA-SAMS exports', 'route' => 'academic-sasams.index', 'permissions' => ['academic-structure-manage'], 'module' => 'academics', 'keywords' => ['SA-SAMS', 'learners', 'attendance', 'marks', 'export']],
                        ['key' => 'academic-transitions', 'label' => 'Mid-year learner changes', 'route' => 'academic-transitions.index', 'permissions' => ['academic-structure-manage'], 'module' => 'academics', 'keywords' => ['join', 'leave', 'subject change', 'exit package']],
                        ['key' => 'discipline', 'label' => 'School discipline', 'route' => 'discipline-panel.index', 'permissions' => [], 'discipline_actor' => true, 'module' => 'discipline', 'active' => ['discipline-panel.*'], 'keywords' => ['discipline', 'grade review', 'hearing', 'detention']],
                        ['key' => 'risk-interventions', 'label' => 'Early warning and interventions', 'route' => 'risk-interventions.index', 'permissions' => [], 'risk_actor' => true, 'module' => 'early_warning', 'active' => ['risk-interventions.*'], 'keywords' => ['academic follow-up', 'attendance follow-up', 'owned interventions']],
                        ['key' => 'learner-notes', 'label' => 'Learner progress and notes', 'route' => 'learner-notes.index', 'permissions' => [], 'progress_actor' => true, 'module' => 'academics', 'active' => ['learner-notes.*'], 'keywords' => ['progress bands', 'teaching notes', 'counselling notes']],
                        ['key' => 'mastery', 'label' => 'Mastery map', 'route' => 'mastery.index', 'permissions' => ['curriculum-read', 'academic-structure-manage'], 'module' => 'mastery', 'keywords' => ['growth', 'topics', 'prerequisites', 'learning support']],
                        ['key' => 'academic-content', 'label' => 'Learning content', 'route' => 'academic-content.index', 'permissions' => ['curriculum-read', 'academic-structure-manage'], 'module' => 'academics', 'keywords' => ['content', 'upload', 'markdown', 'transcript', 'curriculum topics']],
                        ['key' => 'admissions', 'label' => 'Admissions', 'route' => 'admissions-panel.index', 'permissions' => [], 'intake_actor' => true, 'module' => 'intake', 'active' => ['admissions-panel.*'], 'keywords' => ['intake', 'applications', 'waiting list', 'enrolment']],
                        ['key' => 'school-transfers', 'label' => 'School transfers', 'route' => 'school-transfers.index', 'permissions' => [], 'transfer_actor' => true, 'module' => 'school_transfers', 'active' => ['school-transfers.*'], 'keywords' => ['transfer', 'receiving school', 'guardian consent', 'signed archive']],
                        ['key' => 'leadership-dashboard', 'label' => 'Leadership dashboard', 'route' => 'leadership-dashboard.index', 'permissions' => [], 'principal_dashboard' => true, 'module' => 'leadership_dashboard', 'active' => ['leadership-dashboard.*'], 'keywords' => ['principal', 'attendance trends', 'academic follow-up', 'school trends']],
                        ['key' => 'academic-reports', 'label' => 'Academic report cards', 'route' => 'academic-reports.index', 'permissions' => ['curriculum-read', 'academic-structure-manage'], 'module' => 'academics', 'keywords' => ['reports', 'comments', 'principal', 'grade head', 'report release']],
                        ['key' => 'academic-registers', 'label' => 'Academic registers', 'route' => 'academic-registers.index', 'permissions' => ['attendance-list', 'academic-structure-manage'], 'module' => 'academics', 'keywords' => ['register', 'absence', 'attendance', 'cutoff', 'qr']],
                        ['key' => 'academic-timetable', 'label' => 'Academic timetable', 'route' => 'academic-timetable.index', 'permissions' => ['academic-structure-manage'], 'module' => 'academics',
                            'keywords' => ['rooms', 'bells', 'lesson clashes', 'week editor', 'timetable import']],
                        ['key' => 'teaching-groups', 'label' => 'Teaching groups', 'route' => 'teaching-groups.index', 'permissions' => ['curriculum-read', 'academic-structure-manage'], 'module' => 'academics',
                            'keywords' => ['learners', 'allocations', 'subject groups', 'dated membership']],
                        ['key' => 'academic-allocations', 'label' => 'Academic allocations', 'route' => 'academic-allocations.index', 'permissions' => ['academic-structure-manage'], 'module' => 'academics',
                            'keywords' => ['homeroom teachers', 'grade heads', 'principal']],
                        ['key' => 'curriculum-plans', 'label' => 'Curriculum plans', 'route' => 'curriculum.index', 'permissions' => ['curriculum-read'], 'module' => 'academics',
                            'keywords' => ['topics', 'assessments', 'task weights', 'question maxima']],
                        ['key' => 'subject-standards', 'label' => 'Subject standards', 'route' => 'subject-standards.index', 'permissions' => ['subject-list'], 'module' => 'academics',
                            'keywords' => ['CAPS', 'IEB', 'subject codes', 'curriculum references']],
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
                        ['key' => 'exam-periods', 'label' => 'nav_exam_periods', 'route' => 'exam-periods.index', 'permissions' => ['exam-timetable-create'], 'module' => null,
                            'keywords' => ['exam mode', 'exam weeks', 'exam days']],
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
                        ['key' => 'school-messages', 'communication_author' => true, 'label' => 'School messages', 'route' => 'communication-centre.index', 'permissions' => [], 'module' => 'communication', 'keywords' => ['audiences', 'messages', 'approval', 'scheduled message']],
                        ['key' => 'family-reach', 'label' => 'nav_family_reach', 'route' => 'family-reach.index', 'permissions' => ['student-list'], 'module' => null,
                            'keywords' => ['reach', 'unreachable families', 'parents without app', 'verified email']],
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
                        ['key' => 'fee-accounts', 'label' => 'Family accounts', 'route' => 'fee-accounts.index', 'permissions' => ['fee-accounts-manage'], 'module' => 'fees', 'active' => ['fee-accounts.*'],
                            'keywords' => ['family', 'payer', 'split holders', 'statement', 'invoices']],
                        ['key' => 'fee-bill-runs', 'label' => 'Bill runs', 'route' => 'fee-bill-runs.index', 'permissions' => ['fee-billing-manage'], 'module' => 'fees', 'active' => ['fee-bill-runs.*', 'fee-invoices.*'],
                            'keywords' => ['annual tuition', 'billing', 'preview', 'exceptions', 'approval', 'post invoices']],
                        ['key' => 'fee-items', 'label' => 'Fee items and charges', 'route' => 'fee-items.index', 'permissions' => ['fee-items-manage'], 'module' => 'fees', 'active' => ['fee-items.*', 'fee-item-charges.*', 'fee-item-batches.*'], 'keywords' => ['additional charges', 'one-off', 'excursion', 'family charge']],
                        ['key' => 'fee-payments', 'label' => 'Fee payments', 'route' => 'fee-payments.index', 'permissions' => ['fee-payments-manage'], 'module' => 'fees', 'active' => ['fee-payments.*'],
                            'keywords' => ['receipts', 'cash', 'EFT', 'allocation', 'held credit', 'statements']],
                        ['key' => 'fee-credit-notes', 'label' => 'Fee credit notes', 'route' => 'fee-credit-notes.index', 'permissions' => ['fee-credits-manage'], 'module' => 'fees', 'active' => ['fee-credit-notes.*'], 'keywords' => ['corrections', 'credit', 'invoice', 'held funds']],
                        ['key' => 'fee-refunds', 'label' => 'Fee refunds', 'route' => 'fee-refunds.index', 'permissions' => ['fee-payments-manage'], 'module' => 'fees', 'active' => ['fee-refunds.*'], 'keywords' => ['receipt', 'refund', 'payout']],
                        ['key' => 'fee-discounts', 'label' => 'Discounts and bursaries', 'route' => 'fee-discounts.index', 'permissions' => ['fee-credits-manage'], 'module' => 'fees', 'active' => ['fee-discounts.*'], 'keywords' => ['discount', 'bursary', 'exemption', 'sibling', 'staff']],
                        ['key' => 'fee-leavers', 'label' => 'Leaver credits', 'route' => 'fee-leavers.index', 'permissions' => ['fee-credits-manage'], 'module' => 'fees', 'active' => ['fee-leavers.*'], 'keywords' => ['departure', 'refund rule', 'leaver', 'credit']],
                        ['key' => 'fee-payer-corrections', 'label' => 'Unpaid payer corrections', 'route' => 'fee-payer-corrections.index', 'permissions' => ['fee-credits-manage'], 'module' => 'fees', 'active' => ['fee-payer-corrections.*'], 'keywords' => ['unpaid debt', 'payer correction', 'replacement invoice']],
                        ['key' => 'fee-reporting', 'label' => 'Arrears and accounting', 'route' => 'fee-reporting.index', 'permissions' => ['fee-payments-manage'], 'module' => 'fees', 'active' => ['fee-reporting.*'], 'keywords' => ['ageing', 'debtors', '30 60 90', 'accounting export', 'CSV']],
                    ],
                ],
                'wallets' => [
                    'label' => 'nav_group_wallets',
                    'items' => [
                        ['key' => 'wallet-settings', 'label' => 'Wallets', 'route' => 'wallet-finance.index', 'permissions' => ['wallet-finance-manage'], 'module' => 'wallet', 'active' => ['wallet-finance.*'], 'keywords' => ['wallet balances', 'spending limits', 'guardian controls']],
                        ['key' => 'wallet-funding', 'label' => 'School wallet funding', 'route' => 'wallet-funding.index', 'permissions' => ['wallet-finance-manage'], 'module' => 'wallet', 'active' => ['wallet-funding.*'], 'keywords' => ['master wallet', 'verified school cash', 'school EFT funding']],
                        ['key' => 'wallet-deposits', 'label' => 'Parent wallet deposits', 'route' => 'wallet-deposits.index', 'permissions' => ['wallet-finance-manage'], 'module' => 'wallet', 'active' => ['wallet-deposits.*'], 'keywords' => ['parent deposit', 'verify outside receipt', 'parent wallet funding']],
                        ['key' => 'wallet-topups', 'label' => 'Desk wallet top-ups', 'route' => 'wallet-desk.index', 'permissions' => ['wallet-finance-manage', 'wallet-desk-process'], 'module' => 'wallet', 'active' => ['wallet-desk.*'], 'keywords' => ['master wallet credit', 'second approval', 'school grants']],
                        ['key' => 'wallet-withdrawals', 'label' => 'Adult wallet withdrawals', 'route' => 'wallet-withdrawals.index', 'permissions' => ['wallet-finance-manage'], 'module' => 'wallet', 'active' => ['wallet-withdrawals.*'], 'keywords' => ['withdrawal', 'cash pickup', 'bank payout', 'return adult funds']],
                    ],
                ],
                'vendors' => [
                    'label' => 'nav_group_vendors',
                    'items' => [
                        ['key' => 'vendor-catalogues', 'label' => 'Vendors and stock', 'route' => 'vendor-catalogues.index', 'permissions' => ['vendors-manage'], 'module' => 'vendors', 'active' => ['vendor-catalogues.*'], 'keywords' => ['tuckshop', 'merchant', 'products', 'inventory', 'settlement terms']],
                        ['key' => 'vendor-orders', 'label' => 'Vendor orders', 'route' => 'vendor-orders.index', 'permissions' => ['vendors-manage'], 'module' => 'vendors', 'active' => ['vendor-orders.*'], 'keywords' => ['tuckshop', 'collection', 'order QR', 'counter']],
                        ['key' => 'vendor-operators', 'label' => 'Vendor operators', 'route' => 'vendor-operators.index', 'permissions' => ['vendors-manage'], 'module' => 'vendors', 'active' => ['vendor-operators.*'], 'keywords' => ['counter access', 'vendor login', 'operator']],
                        ['key' => 'vendor-settlements', 'label' => 'Vendor settlements', 'route' => 'vendor-settlements.index', 'permissions' => ['vendors-manage'], 'module' => 'vendors', 'active' => ['vendor-settlements.*'], 'keywords' => ['merchant payout', 'collected orders', 'settlement report']],
                        ['key' => 'ticket-events', 'label' => 'Events and admission tickets', 'route' => 'ticket-events.index', 'permissions' => ['vendors-manage'], 'module' => 'ticketing', 'active' => ['ticket-events.*'], 'keywords' => ['admission capacity', 'event tickets', 'ticket transfers']],
                        ['key' => 'vendor-payment-readers', 'label' => 'Vendor payment readers', 'route' => 'vendor-payment-readers.index', 'permissions' => [], 'vendor_payment_reader_actor' => true, 'module' => null, 'active' => ['vendor-payment-readers.*'], 'keywords' => ['counter pairing', 'wallet card tap']],
                    ],
                ],
                'cash' => [
                    'label' => 'nav_group_cash',
                    'items' => [
                        ['key' => 'cashups', 'label' => 'Daily cash-up', 'route' => 'cashups.index', 'permissions' => ['money-cashup-manage'], 'module' => null, 'active' => ['cashups.*'], 'keywords' => ['actual cash count', 'cash custodian', 'cash receipts', 'cash differences']],
                        ['key' => 'bank-statements', 'label' => 'Bank statement matching', 'route' => 'bank-statements.index', 'permissions' => ['fee-payments-manage'], 'module' => 'fees', 'active' => ['bank-statements.*'], 'keywords' => ['bank import', 'CSV', 'EFT', 'unmatched', 'reconciliation']],
                    ],
                ],
                'configuration' => [
                    'label' => 'nav_group_money_configuration',
                    'items' => [
                        ['key' => 'fee-plans', 'label' => 'Fee plans', 'route' => 'fee-plans.index', 'permissions' => ['fee-plans-manage'], 'module' => 'fees', 'active' => ['fee-plans.*'],
                            'keywords' => ['annual fees', 'billing', 'enrolment preview', 'uplift']],
                        ['key' => 'fee-lifecycle', 'label' => 'Enrolment fee policy', 'route' => 'fee-lifecycle.index', 'permissions' => ['fee-plans-manage'], 'module' => 'fees', 'active' => ['fee-lifecycle.*'], 'keywords' => ['join', 'leave', 'proration', 'installments']],
                        ['key' => 'fee-late-charges', 'label' => 'One-off late charges', 'route' => 'fee-late-charges.index', 'permissions' => ['fee-billing-manage'], 'module' => 'fees', 'active' => ['fee-late-charges.*'], 'keywords' => ['overdue debt', 'late fee policy', 'one-off cap']],
                        ['key' => 'fee-reminders', 'label' => 'Fee reminder schedule', 'route' => 'fee-reminders.index', 'permissions' => ['fee-payments-manage'], 'module' => 'fees', 'active' => ['fee-reminders.*'], 'keywords' => ['arrears reminder', 'schedule', 'parent fee statement']],
                    ],
                ],
            ],
        ],

        'activities' => [
            'label' => 'nav_activities',
            'icon' => 'activities',
            'groups' => [
                'groups' => ['label' => 'nav_activity_groups', 'items' => [
                    ['key' => 'weekly-digest-templates', 'label' => 'Weekly digest templates', 'route' => 'digest-panel.index', 'permissions' => [], 'digest_manager' => true, 'module' => 'digest', 'active' => ['digest-panel.*'], 'keywords' => ['weekly', 'parent', 'digest', 'language', 'approval']],
                    ['key' => 'secure-cards', 'label' => 'School identity cards', 'route' => 'cards-panel.index', 'permissions' => [], 'card_actor' => true, 'module' => 'cards', 'active' => ['cards-panel.*'], 'keywords' => ['identity', 'card', 'portrait', 'freeze', 'print']],
                    ['key' => 'foundation-classrooms', 'label' => 'Foundation classrooms', 'route' => 'foundation-panel.index', 'permissions' => [], 'foundation_classroom_actor' => true, 'module' => 'cards', 'active' => ['foundation-panel.*'], 'keywords' => ['classroom', 'tablet', 'foundation', 'practice', 'authorize']],
                    ['key' => 'card-readers', 'label' => 'Card readers', 'route' => 'card-readers-panel.index', 'permissions' => [], 'card_actor' => true, 'card_readers' => true, 'module' => 'cards', 'active' => ['card-readers-panel.*'], 'keywords' => ['reader', 'encoder', 'pair', 'revoke']],
                    ['key' => 'gallery', 'label' => 'Private photos', 'route' => 'gallery-panel.index', 'permissions' => [], 'gallery_actor' => true, 'module' => 'gallery', 'active' => ['gallery-panel.*'], 'keywords' => ['photos', 'gallery', 'tags', 'consent']],
                    ['key' => 'peer-tutoring', 'label' => 'Peer tutoring', 'route' => 'peer-tutoring-panel.index', 'permissions' => [], 'peer_actor' => true, 'module' => 'peer_tutoring', 'active' => ['peer-tutoring-panel.*'], 'keywords' => ['supervision', 'service hours', 'mastery']],
                    ['key' => 'pathway', 'label' => 'Subject choice and pathways', 'route' => 'pathways-panel.index', 'permissions' => ['pathways-manage'], 'pathway_manager' => true, 'module' => 'pathway', 'active' => ['pathways-panel.*'], 'keywords' => ['Grade 9', 'subjects', 'admission score', 'requirements']],
                    ['key' => 'school-support-office', 'label' => 'School support office', 'route' => 'assistance-panel.index', 'permissions' => [], 'assistance_office' => true, 'module' => 'ask_school', 'active' => ['assistance-panel.*'], 'keywords' => ['parent', 'school question', 'handoff', 'translation']],
                    ['key' => 'family-visits', 'label' => 'Meetings and early collection', 'route' => 'family-visits-panel.index', 'permissions' => [], 'family_visit_actor' => true, 'module' => 'family_visits', 'active' => ['family-visits-panel.*'], 'keywords' => ['meeting', 'parent', 'evening', 'collection', 'gate']],
                    ['key' => 'family-forms', 'label' => 'Family consent forms', 'route' => 'family-forms-panel.index', 'permissions' => [], 'family_form_actor' => true, 'module' => 'family_forms', 'active' => ['family-forms-panel.*'], 'keywords' => ['consent', 'trip', 'permission', 'guardian']],
                    ['key' => 'consultations', 'label' => 'Polls, surveys and elections', 'route' => 'consultations-panel.index', 'permissions' => [], 'consultation_actor' => true, 'module' => 'consultations', 'active' => ['consultations-panel.*'], 'keywords' => ['vote', 'poll', 'survey', 'election']],
                    ['key' => 'house-points', 'label' => 'Houses and points', 'route' => 'recognition.index', 'params' => ['module' => 'house_points'], 'permissions' => [], 'recognition_module' => 'house_points', 'module' => 'house_points', 'active' => ['recognition.*'], 'keywords' => ['houses', 'points', 'recognition']],
                    ['key' => 'awards', 'label' => 'Awards and assembly', 'route' => 'recognition.index', 'params' => ['module' => 'awards'], 'permissions' => [], 'recognition_module' => 'awards', 'module' => 'awards', 'active' => ['recognition.*'], 'keywords' => ['awards', 'assembly', 'permanent profile']],
                    ['key' => 'group-conversations', 'label' => 'Group conversations', 'route' => 'group-chat-panel.index', 'permissions' => [], 'roles' => ['Teacher', 'Facilitator'], 'module' => 'groups', 'active' => ['group-chat-panel.*'], 'keywords' => ['group', 'conversation', 'private', 'reply']],
                    ['key' => 'activity-groups', 'label' => 'nav_activity_groups', 'route' => 'activity-groups.index', 'permissions' => ['activity-groups-manage'], 'module' => 'groups', 'active' => ['activity-groups.*'], 'keywords' => ['sport', 'coach', 'culture', 'season', 'facilitator']],
                ]],
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
                        ['key' => 'ai-settings', 'label' => 'AI settings and caps', 'route' => 'ai-controls.index', 'permissions' => [], 'ai_manager' => true, 'module' => null, 'active' => ['ai-controls.*'], 'keywords' => ['AI', 'caps', 'limits', 'availability']],
                        ['key' => 'ai-flags', 'label' => 'Flagged learning content', 'route' => 'ai-review.index', 'permissions' => [], 'ai_reviewer' => true, 'module' => null, 'active' => ['ai-review.*'], 'keywords' => ['safeguarding', 'flagged learning', 'review']],
                        ['key' => 'wellbeing', 'label' => 'Incidents and counselling', 'route' => 'wellbeing.index', 'permissions' => [], 'wellbeing_actor' => true, 'module' => 'wellbeing', 'active' => ['wellbeing.*'], 'keywords' => ['incident reports', 'safeguarding', 'restricted cases']],
                        ['key' => 'support-plans', 'label' => 'Support plans', 'route' => 'support-plans.index', 'permissions' => [], 'support_actor' => true, 'module' => 'support_plans', 'active' => ['support-plans.*'], 'keywords' => ['SIAS', 'accommodations', 'support reviews']],
                        ['key' => 'relief-cover', 'label' => 'Relief cover', 'route' => 'relief-cover.index', 'permissions' => [], 'cover_actor' => true, 'module' => 'relief_cover', 'active' => ['relief-cover.*'], 'keywords' => ['substitute', 'teacher absence', 'cover lessons']],
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
                        ['key' => 'profile-fields', 'label' => 'nav_profile_fields', 'route' => 'profile-configuration.index', 'permissions' => ['form-field-list'], 'module' => null,
                            'active' => ['profile-configuration.*'], 'keywords' => ['categories', 'profile', 'transport', 'fields']],
                        ['key' => 'person-profile', 'label' => 'nav_person_profile', 'route' => 'people-profiles.index', 'permissions' => [], 'module' => null,
                            'active' => ['people-profiles.*'], 'keywords' => ['my profile', 'personal details']],
                        ['key' => 'notification-rules', 'label' => 'nav_notification_rules', 'route' => 'notification-rules.index', 'permissions' => ['setting-create'], 'module' => null,
                            'keywords' => ['mandatory notices', 'email', 'push', 'notification settings']],
                        ['key' => 'school-information', 'label' => 'nav_school_information', 'route' => 'school-information.index', 'permissions' => ['setting-create'], 'module' => null,
                            'active' => ['school-information.*'], 'keywords' => ['my school', 'contacts', 'branding', 'published documents', 'term dates']],
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
                        ['key' => 'access-review', 'label' => 'nav_access_review', 'route' => 'access-review.index', 'permissions' => ['role-list'], 'module' => null,
                            'keywords' => ['who can see what', 'view as role', 'access check', 'permissions review']],
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
