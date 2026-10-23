<?php

// Sources are a fixed platform allowlist, never a caller-supplied column name.
$identity = [
    'first_name' => ['label' => 'First name', 'type' => 'text', 'source' => 'user.first_name', 'required' => true, 'locked' => true],
    'last_name' => ['label' => 'Last name', 'type' => 'text', 'source' => 'user.last_name', 'required' => true, 'locked' => true],
];
$contact = [
    'email' => ['label' => 'Email', 'type' => 'text', 'source' => 'user.email', 'required' => true, 'locked' => true, 'validation' => ['email' => true]],
    'mobile' => ['label' => 'Mobile number', 'type' => 'text', 'source' => 'user.mobile', 'locked' => true],
];
$staff = [
    'identity' => ['label' => 'Identity', 'fields' => $identity + [
        'identity_number' => ['label' => 'Identity or passport number', 'type' => 'text', 'locked' => true, 'sensitive' => true],
    ]],
    'contact' => ['label' => 'Contact', 'fields' => $contact],
];

return [
    'types' => ['learner', 'parent', 'teacher', 'facilitator', 'counsellor', 'staff'],
    'roles' => ['owner', 'guardian', 'school_admin', 'teacher', 'facilitator', 'counsellor'],
    'field_types' => ['text', 'number', 'date', 'dropdown', 'radio', 'checkbox', 'textarea', 'boolean', 'file'],
    'defaults' => [
        'learner' => [
            'identity' => ['label' => 'Identity', 'fields' => $identity + [
                'dob' => ['label' => 'Date of birth', 'type' => 'date', 'source' => 'user.dob', 'required' => true, 'locked' => true],
                'gender' => ['label' => 'Gender', 'type' => 'dropdown', 'source' => 'user.gender', 'options' => ['Male', 'Female', 'Other', 'Prefer not to say'], 'locked' => true],
                'identity_number' => ['label' => 'Identity or passport number', 'type' => 'text', 'locked' => true, 'sensitive' => true],
                'religion' => ['label' => 'Religion', 'type' => 'text', 'hidden' => true, 'sensitive' => true],
            ]],
            'health' => ['label' => 'Health', 'fields' => [
                'medical_alerts' => ['label' => 'Medical alerts', 'type' => 'textarea', 'locked' => true, 'sensitive' => true],
                'allergies' => ['label' => 'Allergies', 'type' => 'textarea', 'locked' => true, 'sensitive' => true],
            ]],
        ],
        'parent' => [
            'identity' => ['label' => 'Identity', 'fields' => $identity + [
                'relationship' => ['label' => 'Relationship', 'type' => 'text', 'source' => 'guardian.relationship', 'locked' => true, 'editable_by' => []],
            ]],
            'contact' => ['label' => 'Contact', 'fields' => $contact],
        ],
        'teacher' => $staff,
        'facilitator' => $staff,
        'counsellor' => $staff,
        'staff' => $staff,
    ],
];
