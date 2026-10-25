<?php

// null package_key identifies an upgraded seed school. Explicit packages are
// assigned by platform staff; school admins cannot grant their own entitlements.
return [
    'legacy' => ['support_plans', 'relief_cover', 'mastery', 'school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
    'core' => ['support_plans', 'relief_cover', 'mastery', 'school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
    'standard' => ['support_plans', 'relief_cover', 'mastery', 'school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
];
