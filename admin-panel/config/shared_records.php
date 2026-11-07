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
| 'competition_kinds' is the seam for other kinds of competition: a fixture
| and a competition carry a kind, and only 'sport' exists in this step.
|
*/

return [
    'types' => [
        SharedFixture::RECORD => SharedFixture::class,
        SharedCompetition::RECORD => SharedCompetition::class,
    ],

    'published' => [
        FixtureSide::class => ['id', 'shared_fixture_id', 'team_label', 'sheet_shared', 'reported_host_score', 'reported_guest_score'],
        FixtureSelection::class => ['shared_fixture_side_id', 'sheet_label', 'position'],
    ],

    'competition_kinds' => ['sport'],
];
