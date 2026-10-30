<?php

/*
|--------------------------------------------------------------------------
| Product modules (Action Plan step 1.16)
|--------------------------------------------------------------------------
|
| How NavigateEd is sold: ten product modules, each a bundle of the technical
| modules in config/modules.php that ModuleGate switches. Packages
| (config/school_packages.php) are built from them. Every technical module
| belongs to exactly one product module (ProductModulesTest).
|
| readiness, from FUTURE-IDEAS "Readiness scale": 1 designed, 2 partly built,
| 3 built but unproven, 4 verified on staging with real services, 5 used by a
| real school. Only level 4 or 5 is offered to paying schools.
|
| price_cents: monthly price per learner before VAT, in cents; null until the
| price is decided. Core is always included.
| Specification: engineering/specs/platform/product-catalogue.md.
|
*/

return [
    'core' => [
        'label' => 'Core', 'always_included' => true, 'readiness' => 3, 'price_cents' => null,
        'description' => 'School setup, people and guardians, academics, communication and the web portals.',
        'modules' => ['school', 'people', 'academics', 'communication', 'web_learner', 'web_parent', 'web_teacher', 'web_facilitator'],
    ],
    'learning' => [
        'label' => 'Learning', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'Online exams, the mastery map, daily practice, exam coach, teaching assistant, test insights and the learner diary.',
        'modules' => ['online_exams', 'mastery', 'daily_practice', 'exam_coach', 'teaching_assistant', 'test_insights', 'diary'],
    ],
    'ai_tutor' => [
        'label' => 'AI tutor', 'always_included' => false, 'readiness' => 2, 'price_cents' => null,
        'description' => 'The subject tutor. Families subscribe; the school switches it on.',
        'modules' => ['tutor'],
    ],
    'pastoral' => [
        'label' => 'Pastoral care', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'Wellbeing and counselling, support plans, early warning, discipline and My grade.',
        'modules' => ['wellbeing', 'support_plans', 'early_warning', 'discipline', 'my_grade'],
    ],
    'family' => [
        'label' => 'Family engagement', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'The weekly digest, forms and consents, meetings and early collection, the photo gallery, polls, the school assistant, live updates and direct messages.',
        'modules' => ['digest', 'family_forms', 'family_visits', 'gallery', 'consultations', 'ask_school', 'live_updates', 'chat'],
    ],
    'activities' => [
        'label' => 'Activities and culture', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'Activity groups, house points, awards and peer tutoring.',
        'modules' => ['groups', 'house_points', 'awards', 'peer_tutoring'],
    ],
    'futures' => [
        'label' => 'Futures', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'Subject choice and pathways, and school transfers.',
        'modules' => ['pathway', 'school_transfers'],
    ],
    'leadership' => [
        'label' => 'Leadership and staff', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'The leadership dashboard and relief cover.',
        'modules' => ['leadership_dashboard', 'relief_cover'],
    ],
    'finance' => [
        'label' => 'Finance', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'Tuition fees, wallets, vendors, counter payments and ticketing.',
        'modules' => ['fees', 'wallet', 'vendors', 'wallet_payments', 'ticketing'],
    ],
    'access' => [
        'label' => 'Access and admissions', 'always_included' => false, 'readiness' => 3, 'price_cents' => null,
        'description' => 'ID cards and the gate, and online registration and admissions.',
        'modules' => ['cards', 'intake'],
    ],
];
