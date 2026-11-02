<?php

declare(strict_types=1);

/*
 * School health in HQ (Action Plan SA.10): the at-risk rules and how long the
 * nightly figures are kept. engineering/specs/platform/hq-school-health.md.
 *
 * Every rate is a percentage (decimals allowed, such as 0.1). A rule whose
 * figure is bad when high flags when the figure reaches its threshold ("at or
 * above"); guardian reach is the one minimum, and flags below it. Each rule
 * names the smallest sample it needs, so a school with three emails a month is
 * not flagged for one bounce. Changes apply from the next nightly figures.
 */
return [
    'flags' => [
        // Weekly active families now against the average of the nightly
        // figures of the four weeks before this one.
        'activity_drop' => ['percent' => 30, 'minimum_average' => 5, 'minimum_history' => 4],
        // Learners with at least one guardian the school can reach.
        'low_reach' => ['percent' => 60, 'minimum_learners' => 10],
        // Mail in the last 30 days: bounced or complained against sent.
        'bounce_rate' => ['percent' => 5, 'minimum_sent' => 20],
        'complaint_rate' => ['percent' => 0.1, 'minimum_sent' => 100],
        // Push attempts in the last 30 days that failed.
        'push_failure_rate' => ['percent' => 10, 'minimum_attempts' => 20],
        // AI spend this month against the school's cap for the month.
        'ai_spend' => ['percent' => 90],
        // Queued jobs of the school that failed in the last 30 days.
        'failed_jobs' => ['count' => 10],
    ],

    // Nightly figures older than this are deleted by the nightly run.
    'keep_days' => 400,
];
