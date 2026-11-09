<?php

declare(strict_types=1);

/*
 * The shared content library, teacher to teacher (Action Plan 5.4).
 * engineering/specs/learning/shared-content-library.md.
 *
 * Owner decisions recorded here as placeholders until the owner confirms them:
 * - the licence a teacher grants when they share (CC BY 4.0 proposed);
 * - how many ratings an item needs before its average is shown (3).
 * The school-side defaults (sharing off, a leader approves each contribution)
 * are the columns' defaults in library_school_settings.
 */
return [
    'licence' => [
        'code' => 'CC BY 4.0',
        'name' => 'Creative Commons Attribution 4.0 International',
        'url' => 'https://creativecommons.org/licenses/by/4.0/',
    ],

    // An item's average and count (and its comments) are shown to teachers only from this many counted ratings.
    'rating_threshold' => 3,

    // The longest rating comment, in characters.
    'comment_max' => 500,
];
