<?php

// null package_key identifies an upgraded seed school. Explicit packages are
// assigned by platform staff; school admins cannot grant their own entitlements.
return [
    'legacy' => ['school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher', 'relief_cover', 'mastery'],
    'core' => ['school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher', 'relief_cover', 'mastery'],
    'standard' => ['school', 'people', 'academics', 'communication', 'groups', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher', 'relief_cover', 'mastery'],
];
