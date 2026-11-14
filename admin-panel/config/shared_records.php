<?php

use App\Models\Activities\FixtureSelection;
use App\Models\Activities\FixtureSide;
use App\Models\Shared\SharedCompetition;
use App\Models\Shared\SharedFixture;

/*
|--------------------------------------------------------------------------
| Records shared between schools
|--------------------------------------------------------------------------
|
| engineering/specs/platform/shared-records.md. 'types' are the shared record
| types schools can take part in (shared_participants.record_type). 'published'
| lists, per school-owned model, the only columns another school may ever read,
| and only once every school concerned has accepted. Learner ids, groups and
| private notes are never published; SharedRecordsBoundaryTest checks it.
|
| 'competition_kinds' lists the kinds a fixture and a competition may be.
| 'academic_kinds' are the academic competitions (Action Plan 5.3): each kind
| says how its entrants take part (a team or individuals), whether a draw can
| be the result, and whether its adjudicators may ask for AI feedback on the
| reasoning in a speech or written argument (advice only, never a result).
| A new kind is a new entry here and in 'competition_kinds'.
|
*/

return [
    'types' => [
        SharedFixture::RECORD => SharedFixture::class,
        SharedCompetition::RECORD => SharedCompetition::class,
    ],

    'published' => [
        FixtureSide::class => ['id', 'shared_fixture_id', 'team_label', 'sheet_shared', 'reported_host_score', 'reported_guest_score', 'reported_winner'],
        FixtureSelection::class => ['shared_fixture_side_id', 'sheet_label', 'position'],
    ],

    'competition_kinds' => ['sport', 'debate', 'quiz', 'speech'],

    'academic_kinds' => [
        'debate' => ['entry' => 'team', 'draw' => false, 'reasoning_feedback' => true],
        'quiz' => ['entry' => 'team', 'draw' => true, 'reasoning_feedback' => false],
        'speech' => ['entry' => 'individual', 'draw' => true, 'reasoning_feedback' => true],
    ],
];
