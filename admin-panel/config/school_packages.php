<?php

// null package_key identifies an upgraded seed school. Explicit packages are
// assigned by platform staff; school admins cannot grant their own entitlements.
// A package lists technical modules (config/modules.php); 'pilot' is built from
// the product modules (config/product_modules.php, Action Plan 1.16).
$products = require __DIR__.'/product_modules.php';
$from = fn (array $keys): array => array_values(array_merge(...array_map(fn (string $key): array => $products[$key]['modules'], $keys)));

return [
    'legacy' => ['peer_tutoring', 'ask_school', 'pathway', 'family_visits', 'exam_coach', 'family_forms', 'teaching_assistant', 'test_insights', 'tutor', 'diary', 'my_grade', 'digest', 'live_updates', 'gallery', 'daily_practice', 'consultations', 'discipline', 'house_points', 'awards', 'school_transfers', 'early_warning', 'wellbeing', 'support_plans', 'relief_cover', 'mastery', 'school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'fees', 'wallet', 'vendors', 'ticketing', 'wallet_payments', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher', 'web_facilitator', 'leadership_dashboard'],
    'core' => ['peer_tutoring', 'ask_school', 'pathway', 'family_visits', 'exam_coach', 'family_forms', 'teaching_assistant', 'test_insights', 'tutor', 'diary', 'my_grade', 'digest', 'live_updates', 'gallery', 'daily_practice', 'consultations', 'discipline', 'house_points', 'awards', 'school_transfers', 'early_warning', 'wellbeing', 'support_plans', 'relief_cover', 'mastery', 'school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher', 'web_facilitator', 'leadership_dashboard'],
    'standard' => ['peer_tutoring', 'ask_school', 'pathway', 'family_visits', 'exam_coach', 'family_forms', 'teaching_assistant', 'test_insights', 'tutor', 'diary', 'my_grade', 'digest', 'live_updates', 'gallery', 'daily_practice', 'consultations', 'discipline', 'house_points', 'awards', 'school_transfers', 'early_warning', 'wellbeing', 'support_plans', 'relief_cover', 'mastery', 'school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'fees', 'wallet', 'vendors', 'ticketing', 'wallet_payments', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher', 'web_facilitator', 'leadership_dashboard'],
    // The design-partner pilot (Term 3 and 4 2027): every product module.
    'pilot' => $from(array_keys($products)),
];
