<?php

declare(strict_types=1);

/*
 * Anonymous benchmarking between comparable schools (Action Plan 5.5).
 * engineering/specs/leadership/benchmarking.md explains every rule.
 *
 * Values are basis points (10000 = 100%). A school is compared only with
 * schools of the same sector, the same stage mix and the same learner band,
 * and only when enough of them took part.
 */
return [
    // k: the fewest schools a comparison may describe. A smaller group shows
    // nothing at all. The code never goes below 3, whatever is set here.
    'min_group_size' => (int) env('BENCHMARK_MIN_GROUP_SIZE', 5),

    // Differencing guard: when a group's schools change by fewer than this
    // many (joined plus left) since its last published figures, the last
    // figures are kept unchanged instead of publishing new ones. The code
    // never goes below 2.
    'min_membership_change' => (int) env('BENCHMARK_MIN_MEMBERSHIP_CHANGE', 3),

    // Published medians and quartiles are rounded to this step (100 = whole
    // percentage points).
    'round_bp' => 100,

    // After a school leaves, it may join again only after this many days, so
    // that leaving and rejoining cannot be used to force a comparison.
    'rejoin_after_days' => 90,

    // Learner-count bands: each number starts a band, up to the next one.
    'learner_bands' => [1, 300, 600, 1000, 1500],

    // A stage (early years, junior, high school) is part of a school's stage
    // mix when at least this share of its learners are in it.
    'stage_share_bp' => 1000,

    // Attendance: the days before the period end that are counted, and the
    // fewest recorded learner-days that make a figure.
    'attendance_days' => 28,
    'min_attendance_learner_days' => 100,

    // Reach: the school's latest nightly health figures at most this many
    // days before the period end, with at least this many learners.
    'reach_max_age_days' => 7,
    'min_reach_learners' => 10,

    // Results movement: the fewest learner-topics measured at both the start
    // of the term and now.
    'min_results_measured' => 20,
];
