<?php

declare(strict_types=1);

/*
 * Audited support access (Action Plan SA.9). A Navigate Ed Support or
 * Operations staff member asks a school for time-limited access; the school's
 * Super Admin approves, or an Owner records an emergency override.
 * engineering/specs/platform/support-access.md.
 *
 *  durations     the lengths, in minutes, a request may ask for
 *  max_minutes   the longest any session may last, whatever is asked
 *  pending_hours a request nobody decided lapses after this long
 *  scopes        what a session may do. Only "read" exists: every write is
 *                refused. A write scope must be named here and designed in
 *                its own step (see the spec's "Not in this step").
 */
return [
    'durations' => [30, 60, 120, 240],
    'max_minutes' => 240,
    'pending_hours' => 24,
    'scopes' => [
        'read' => 'Read only',
    ],
];
