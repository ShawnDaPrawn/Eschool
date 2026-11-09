<?php

return [
    'consent_version' => '2026-10-23',
    'consent_text' => 'I am authorised to act for this learner and approve their access to this school on NavigateEd. I can withdraw this approval at any time.',
    'invitation_days' => 7,
    'portal_url' => env('LEARNER_PORTAL_URL', str_contains((string) env('APP_URL', ''), 'staging') ? 'https://staging-app.navigateed.co.za' : 'https://app.navigateed.co.za'),
    // Rollout is explicit: the service and tests work before a school enables it.
    // No existing account is silently treated as having guardian consent.
    'approval_enforced' => (bool) env('LEARNER_APPROVAL_ENFORCED', false),
    // Child mode in the parent app (Action Plan 4.2): how long a child token
    // lasts (5 to 120 minutes), and how many wrong PINs lock the PIN, for how long.
    'child_mode_minutes' => (int) env('CHILD_MODE_MINUTES', 30),
    'child_mode_pin_attempts' => 5,
    'child_mode_pin_lock_minutes' => 15,
    // LURITS number shape (digits only, after removing spaces and dashes).
    // Deliberately permissive until confirmed against the SA-SAMS / DBE
    // specification; see engineering/specs/platform/lurits.md.
    'lurits' => [
        'pattern' => env('LURITS_PATTERN', '/^[0-9]{6,16}$/D'),
    ],
];
