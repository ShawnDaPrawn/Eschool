<?php

// null package_key identifies an upgraded seed school. Explicit packages are
// assigned by platform staff; school admins cannot grant their own entitlements.
return [
    'legacy' => ['mastery', 'school', 'people', 'academics', 'communication', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
    'core' => ['mastery', 'school', 'people', 'academics', 'communication', 'chat', 'online_exams', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
    'standard' => ['mastery', 'school', 'people', 'academics', 'communication', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
];
