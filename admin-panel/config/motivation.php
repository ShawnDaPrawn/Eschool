<?php

// Action Plan 3.13: practice streaks, goals, gentle nudges and the "gap
// closed" celebration. engineering/specs/learning/practice-motivation.md.
return [
    'streak' => [
        // How far back a run is followed, and the window "best run" covers.
        'lookback_days' => 366,
        // Missed school days a week that keep a run going (a "rest day").
        'rest_days_per_week' => 1,
        // Streak milestone badges (off by default per school), in days.
        'milestones' => [5, 10, 20, 40],
    ],
    'goal' => ['min_days' => 1, 'max_days' => 7],
    'nudges' => [
        // Nudges go out only on school days, in this school-local window.
        'local_hour_from' => 15,
        'local_hour_until' => 19,
        'min_days' => 2,
        'max_days' => 10,
        'default_days' => 3,
    ],
    'gap_closed' => [
        // A gap is a family-visible topic level below this; secure is this or above
        // (the same boundary the mastery map's "topics to work on" uses).
        'secure_level' => 3,
        // A secure level needs at least this much evidence before it is celebrated.
        'min_evidence' => 3,
    ],
];
