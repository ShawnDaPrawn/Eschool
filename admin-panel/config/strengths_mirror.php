<?php

// Interests and strengths mirror (Action Plan 4.8).
// engineering/specs/learning/strengths-mirror.md.
return [
    // "Where you are growing": topics whose mastery rose over this many days.
    'growth_window_days' => 42,
    // The smallest rise that counts as growth when the level did not change
    // (basis points of the recent20-v1 score; the growth digest uses the same).
    'min_score_rise_bp' => 500,
    // "What you remember well": a practice memory at least this stable (FSRS
    // stability in days) that the learner would still recall today.
    'remember_min_stability_days' => 7,
    'remember_min_recall' => 0.85,
    // At most this many topics, activities or ideas per section.
    'max_items' => 5,
    // A share lasts at most this many days; the learner can end it sooner.
    'share_max_days' => 30,
    // Open shares a learner may hold at once.
    'max_open_shares' => 20,
];
