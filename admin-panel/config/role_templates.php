<?php

/*
|--------------------------------------------------------------------------
| Role templates (Action Plan step 1.14)
|--------------------------------------------------------------------------
|
| Starting points for common school jobs in the role editor
| (/roles-by-module/new?template=…). A school copies one into its own custom
| role and adjusts it; nothing here changes an existing role, and the system
| roles (Super Admin, Teacher, Parent, Student, Counsellor, Safeguarding) stay
| as they are. Levels and abilities use config/module_access.php.
|
| Sensitive abilities (marks release, finance approvals, sensitive profiles,
| health records, roles) are never part of a template: they are granted on
| purpose, one by one. RoleTemplatesTest enforces it.
| Specification: engineering/specs/platform/role-editor.md.
|
*/

return [
    'principal' => [
        'label' => 'Principal',
        'description' => 'Sees most of the school, runs communication, consultations and activities. Sign-off abilities are granted separately.',
        'levels' => ['people' => 'view', 'academics' => 'view', 'communication' => 'manage', 'intake' => 'manage', 'consultations' => 'manage',
            'groups' => 'manage', 'family_visits' => 'manage', 'pathway' => 'manage'],
        'abilities' => ['academics' => ['marks_view_all']],
    ],
    'deputy_principal' => [
        'label' => 'Deputy principal',
        'description' => 'Like the principal, with less configuration.',
        'levels' => ['people' => 'view', 'academics' => 'view', 'communication' => 'contribute', 'consultations' => 'manage', 'groups' => 'manage', 'family_visits' => 'manage'],
        'abilities' => ['academics' => ['marks_view_all']],
    ],
    'school_office' => [
        'label' => 'School office',
        'description' => 'Manages people, admissions, communication, visits and forms. No marks release or finance approval.',
        'levels' => ['people' => 'manage', 'intake' => 'manage', 'communication' => 'manage', 'family_visits' => 'manage', 'family_forms' => 'manage', 'cards' => 'contribute'],
        'abilities' => [],
    ],
    'bursar' => [
        'label' => 'Bursar or finance officer',
        'description' => 'Manages fees, wallets and vendors. Billing, payment and credit approvals are granted separately. No academic records.',
        'levels' => ['people' => 'view', 'fees' => 'manage', 'wallet' => 'manage', 'vendors' => 'manage'],
        'abilities' => [],
    ],
    'head_of_department' => [
        'label' => 'Head of department',
        'description' => 'Plans and publishes curriculum and captures academic work.',
        'levels' => ['people' => 'view', 'academics' => 'contribute', 'communication' => 'contribute'],
        'abilities' => ['academics' => ['curriculum_publish']],
    ],
    'grade_head' => [
        'label' => 'Grade head',
        'description' => 'Follows a grade: marks, communication and family meetings.',
        'levels' => ['people' => 'view', 'academics' => 'contribute', 'communication' => 'contribute', 'family_visits' => 'manage'],
        'abilities' => ['academics' => ['marks_view_all']],
    ],
    'activities_coordinator' => [
        'label' => 'Sports and activities coordinator',
        'description' => 'Runs activity groups and school consultations.',
        'levels' => ['people' => 'view', 'groups' => 'manage', 'communication' => 'contribute', 'consultations' => 'manage'],
        'abilities' => [],
    ],
    'reception' => [
        'label' => 'Reception and gate',
        'description' => 'Visits, early collection and ID cards.',
        'levels' => ['people' => 'view', 'family_visits' => 'manage', 'cards' => 'contribute'],
        'abilities' => [],
    ],
    'it_administrator' => [
        'label' => 'IT administrator',
        'description' => 'School settings and ID card set-up. No learner records.',
        'levels' => ['school' => 'manage', 'cards' => 'manage'],
        'abilities' => [],
    ],
];
