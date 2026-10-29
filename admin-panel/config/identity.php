<?php

return [
    'consent_version' => '2026-10-23',
    'consent_text' => 'I am authorised to act for this learner and approve their access to this school on NavigateEd. I can withdraw this approval at any time.',
    'invitation_days' => 7,
    'portal_url' => env('LEARNER_PORTAL_URL', str_contains((string) env('APP_URL', ''), 'staging') ? 'https://staging-app.navigateed.co.za' : 'https://app.navigateed.co.za'),
    // Rollout is explicit: the service and tests work before a school enables it.
    // No existing account is silently treated as having guardian consent.
    'approval_enforced' => (bool) env('LEARNER_APPROVAL_ENFORCED', false),
    // LURITS number shape (digits only, after removing spaces and dashes).
    // Deliberately permissive until confirmed against the SA-SAMS / DBE
    // specification; see engineering/specs/platform/lurits.md.
    'lurits' => [
        'pattern' => env('LURITS_PATTERN', '/^[0-9]{6,16}$/D'),
    ],
];
