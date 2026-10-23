<?php

// null package_key identifies an upgraded seed school. Explicit packages are
// assigned by platform staff; school admins cannot grant their own entitlements.
return [
    'legacy' => ['school', 'people', 'academics', 'communication', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
    'core' => ['school', 'people', 'academics', 'communication', 'chat', 'online_exams', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
    'standard' => ['school', 'people', 'academics', 'communication', 'chat', 'online_exams', 'fees', 'cards', 'intake', 'web_learner', 'web_parent', 'web_teacher'],
];
