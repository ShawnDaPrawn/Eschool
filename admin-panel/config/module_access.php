<?php

/*
|--------------------------------------------------------------------------
| Module access levels (Action Plan step 1.12)
|--------------------------------------------------------------------------
|
| How a school role's access is described to a school administrator: for each
| module in config/modules.php, a level (none, view, contribute, manage) and
| named special abilities. Each level lists the existing Spatie permission
| names it grants; levels are cumulative (manage includes contribute includes
| view). Abilities are granted one by one and are never implied by a level.
|
| This is a translation layer over the existing permissions, not a rename:
| policies and middleware keep checking the same permission names.
|
| Every permission the Super Admin role holds (SchoolRoles, the set a custom
| role may be given) appears exactly once here, or in 'retired'.
| ModuleAccessTest enforces it. Specification:
| engineering/specs/platform/module-access.md.
|
| 'sensitive' abilities open records about children's health, wellbeing or
| identity, or approve money; the role editor shows them apart and every grant
| is audited.
|
*/

return [

    'levels' => ['none', 'view', 'contribute', 'manage'],

    'modules' => [

        'school' => [
            'view' => [],
            'contribute' => ['update-admin-profile'],
            'manage' => [
                'setting-create', 'fcm-setting-create', 'email-setting-create', 'chat-settings',
                'privacy-policy', 'terms-condition', 'contact-us', 'about-us',
                'language-list', 'language-create', 'language-edit', 'language-delete',
                'slider-list', 'slider-create', 'slider-edit', 'slider-delete',
                'faq-list', 'faq-create', 'faq-edit', 'faq-delete',
                'content-list', 'content-create', 'content-edit',
                'media-list', 'media-create', 'media-edit', 'media-delete',
                'program-list', 'program-create', 'program-edit', 'program-delete',
            ],
            'abilities' => [
                'ai_settings' => ['label' => 'Manage AI settings and limits', 'permissions' => ['ai-settings-manage'], 'sensitive' => true],
            ],
        ],

        'people' => [
            'view' => ['student-list', 'parents-list', 'teacher-list', 'staff-list', 'profile-person-view', 'leave-list'],
            'contribute' => ['student-create', 'student-edit', 'parents-edit', 'profile-person-edit', 'student-reset-password', 'assign-class-to-new-student'],
            'manage' => [
                'student-delete', 'teacher-create', 'teacher-edit', 'teacher-delete',
                'staff-create', 'staff-edit', 'staff-delete',
                'form-field-list', 'form-field-create', 'form-field-edit', 'form-field-delete',
                'category-list', 'category-create', 'category-edit', 'category-delete',
                'leave-setting-create', 'leave-approve', 'leave-delete',
            ],
            'abilities' => [
                'sensitive_profiles' => ['label' => 'See and export sensitive profile details', 'permissions' => ['profile-sensitive-view', 'profile-sensitive-export'], 'sensitive' => true],
                'roles' => ['label' => 'Manage roles and access', 'permissions' => ['role-list', 'role-create', 'role-edit', 'role-delete'], 'sensitive' => true],
            ],
        ],

        'academics' => [
            'view' => [
                'medium-list', 'section-list', 'class-list', 'subject-list', 'stream-list', 'shift-list',
                'session-year-list', 'term-list', 'timetable-list', 'class-timetable', 'teacher-timetable',
                'holiday-list', 'exam-list', 'view-exam-result', 'class-teacher-list', 'subject-teacher-list',
                'attendance-report', 'student-report', 'curriculum-read',
            ],
            'contribute' => ['class-attendance', 'attendance-staff', 'assignment-submission', 'student-leave-approve'],
            'manage' => [
                'medium-create', 'medium-edit', 'medium-delete',
                'section-create', 'section-edit', 'section-delete',
                'class-create', 'class-edit', 'class-delete',
                'subject-create', 'subject-edit', 'subject-delete',
                'stream-create', 'stream-edit', 'stream-delete',
                'shift-create', 'shift-edit', 'shift-delete',
                'session-year-create', 'session-year-edit', 'session-year-delete',
                'term-create', 'term-edit', 'term-delete',
                'timetable-create', 'timetable-edit', 'timetable-delete',
                'holiday-create', 'holiday-edit', 'holiday-delete',
                'exam-create', 'exam-edit', 'exam-delete', 'exam-timetable-create', 'grade-create',
                'class-teacher-create', 'class-teacher-edit', 'class-teacher-delete',
                'subject-teacher-create', 'subject-teacher-edit', 'subject-teacher-delete',
                'assign-class-subject', 'assign-elective-subjects',
                'promote-student-list', 'promote-student-edit',
                'academic-structure-manage', 'curriculum-manage', 'generate-result', 'generate-document',
            ],
            'abilities' => [
                'marks_view_all' => ['label' => 'See every learner\'s marks', 'permissions' => ['marks-view-all'], 'sensitive' => false],
                'marks_release' => ['label' => 'Release marks and reports to families', 'permissions' => ['marks-release'], 'sensitive' => true],
                'curriculum_publish' => ['label' => 'Publish curriculum plans', 'permissions' => ['curriculum-publish'], 'sensitive' => false],
            ],
        ],

        'communication' => [
            'view' => ['announcement-list', 'notification-list', 'event-list'],
            'contribute' => ['announcement-create', 'announcement-edit', 'announcement-delete', 'notification-create', 'event-create', 'event-edit', 'event-delete'],
            'manage' => ['notification-delete', 'communication-manage'],
            'abilities' => [],
        ],

        'intake' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['online-registration-list'],
            'abilities' => [],
        ],

        'fees' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['fee-plans-manage', 'fee-items-manage'],
            'abilities' => [
                'fee_accounts' => ['label' => 'Manage family fee accounts and payers', 'permissions' => ['fee-accounts-manage'], 'sensitive' => true],
                'fee_billing' => ['label' => 'Approve and post bills', 'permissions' => ['fee-billing-manage'], 'sensitive' => true],
                'fee_payments' => ['label' => 'Record and allocate payments', 'permissions' => ['fee-payments-manage'], 'sensitive' => true],
                'fee_credits' => ['label' => 'Issue credit notes', 'permissions' => ['fee-credits-manage'], 'sensitive' => true],
            ],
        ],

        'wallet' => [
            'view' => [],
            'contribute' => ['wallet-desk-process'],
            'manage' => ['wallet-transfer-policy-manage', 'wallet-desk-policy-manage'],
            'abilities' => [
                'wallet_finance' => ['label' => 'Review wallet finance', 'permissions' => ['wallet-finance-manage'], 'sensitive' => true],
            ],
        ],

        'vendors' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['vendors-manage'],
            'abilities' => [
                'cash_up' => ['label' => 'Manage cash-ups', 'permissions' => ['money-cashup-manage'], 'sensitive' => true],
            ],
        ],

        'cards' => [
            'view' => [],
            'contribute' => ['generate-id-card'],
            'manage' => ['cards-manage', 'cards-readers-manage', 'id-card-setting'],
            'abilities' => [
                'cards_encode' => ['label' => 'Encode physical cards', 'permissions' => ['cards-encode'], 'sensitive' => true],
            ],
        ],

        'pathway' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['pathways-manage'],
            'abilities' => [],
        ],

        'family_visits' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['family-visits-manage'],
            'abilities' => [],
        ],

        'family_forms' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['family-forms-manage'],
            'abilities' => [
                'health_read' => ['label' => 'See learners\' health information', 'permissions' => ['family-health-read'], 'sensitive' => true],
                'health_manage' => ['label' => 'Edit learners\' health information', 'permissions' => ['family-health-manage'], 'sensitive' => true],
            ],
        ],

        'consultations' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['consultations-manage'],
            'abilities' => [],
        ],

        'groups' => [
            'view' => [],
            'contribute' => [],
            'manage' => ['activity-groups-manage'],
            'abilities' => [],
        ],

        'wellbeing' => [
            'view' => [],
            'contribute' => [],
            'manage' => [],
            'abilities' => [
                'wellbeing_configure' => ['label' => 'Configure incident categories and teams (no access to reports)', 'permissions' => ['wellbeing-configure'], 'sensitive' => true],
            ],
        ],
    ],

    // Permissions kept for history only; never offered in the role editor.
    'retired' => ['fees-type', 'fees-classes', 'fees-paid', 'fees-config'],

];
