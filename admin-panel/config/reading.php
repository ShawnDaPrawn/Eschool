<?php

declare(strict_types=1);

/*
 * The reading library (Action Plan 4.4): public-domain books fetched on
 * demand from a Project Gutenberg mirror, once, by the server, and served to
 * learners from private storage. No host is built in: until READING_MIRROR_URL
 * is set, nothing is fetched and an uncached book is "not ready yet".
 * engineering/specs/learning/reading-library.md.
 */
return [
    // The mirror's origin, such as https://mirror.example.org (https only unless allow_http).
    'mirror_url' => env('READING_MIRROR_URL'),
    // The plain-text (UTF-8) file of a book under that origin; {id} is the Gutenberg number.
    'text_path' => env('READING_MIRROR_TEXT_PATH', 'cache/epub/{id}/pg{id}.txt'),
    'allow_http' => (bool) env('READING_MIRROR_ALLOW_HTTP', false),
    'connect_timeout' => 5,
    'timeout' => 30,
    // A book bigger than this is refused (most novels are under 2 MB of text).
    'max_bytes' => 8 * 1024 * 1024,
    'content_types' => ['text/plain'],
    // After a failed fetch, wait this long before trying the mirror again.
    'retry_after_seconds' => 300,
    // Pages are cut at paragraph ends near this many characters.
    'page_characters' => 2500,
    'max_pages' => 5000,
    // Reading time a learner can report: per report, and per school-local day.
    'max_seconds_per_report' => 900,
    'max_seconds_per_day' => 6 * 3600,
    // A day counts as a reading day from this many seconds.
    'reading_day_seconds' => 60,
];
