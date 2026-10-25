<?php

// Register features when their implementation arrives. Later-phase modules
// cannot be enabled merely by changing a database row.
return [
    'mastery' => ['name' => 'Mastery map', 'default' => false, 'dependencies' => ['academics']],
    'school' => ['name' => 'School setup and branding', 'always_on' => true],
    'people' => ['name' => 'People and guardian approval', 'always_on' => true],
    'academics' => ['name' => 'Years, academics, attendance and reports', 'always_on' => true],
    'communication' => ['name' => 'Communication and notification settings', 'always_on' => true],
    'groups' => ['name' => 'Activity groups and sessions', 'default' => false],
    'chat' => ['name' => 'Direct messages', 'default' => true],
    'online_exams' => ['name' => 'Online exams', 'default' => true],
    'fees' => ['name' => 'Tuition fees', 'default' => false, 'legacy' => true, 'money' => true],
    'cards' => ['name' => 'ID cards', 'default' => false, 'legacy' => true],
    'intake' => ['name' => 'Online registration', 'default' => false, 'legacy' => true],
    'web_learner' => ['name' => 'Learner web portal', 'default' => true],
    'web_parent' => ['name' => 'Parent web portal', 'default' => true],
    'web_teacher' => ['name' => 'Teacher web portal', 'default' => true],
];
