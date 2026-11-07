<?php

declare(strict_types=1);

/*
 * Open standards (Action Plan 5.9): the OneRoster 1.2 CSV export a school
 * starts from its panel, and Open Badges 3.0 credentials for recognition
 * awards. engineering/specs/platform/open-standards.md.
 */
return [

    'oneroster' => [
        // The signed download link lives this long; reload the page for a new one.
        'download_link_minutes' => (int) env('ONEROSTER_DOWNLOAD_LINK_MINUTES', 10),
        // A finished export can be downloaded for this many days, then it is gone.
        'keep_days' => (int) env('ONEROSTER_KEEP_DAYS', 2),
        // An export larger than this (rows in all files) is refused, not truncated.
        'maximum_rows' => (int) env('ONEROSTER_MAXIMUM_ROWS', 200000),
        // source.systemName in manifest.csv.
        'system_name' => 'NavigateEd',
    ],

    'open_badges' => [
        // The Ed25519 signing seed: 32 random bytes, base64. From the
        // environment or secret manager only, never committed. Without it no
        // credential is issued (verification of earlier ones needs it too).
        'signing_key' => env('OPEN_BADGES_SIGNING_KEY'),
        // Earlier seeds, comma separated: verification only, after a rotation.
        'retired_signing_keys' => array_values(array_filter(array_map('trim', explode(',', (string) env('OPEN_BADGES_RETIRED_SIGNING_KEYS', ''))))),
        // 'school': each school signs with its own key, derived from the seed;
        // 'platform': every school's credentials carry the one platform key.
        'key_scope' => env('OPEN_BADGES_KEY_SCOPE', 'school'),
        // Learners in these age stages (SchoolStages) may download their own
        // credentials; younger learners' guardians download them.
        'learner_stages' => ['high_school'],
    ],

];
