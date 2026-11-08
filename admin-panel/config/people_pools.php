<?php

/*
|--------------------------------------------------------------------------
| Alumni and volunteer pool (Action Plan 5.6)
|--------------------------------------------------------------------------
|
| The school's own pool of former learners and outside volunteers who give
| career talks or host job shadows. engineering/specs/community/people-pools.md.
|
*/

return [
    // A pool session is a group session: never fewer learners than this on
    // the session date, so nobody is ever booked one-to-one with a learner.
    'minimum_learners' => (int) env('PEOPLE_POOL_MINIMUM_LEARNERS', 2),

    // Pool members are adults.
    'minimum_age' => 18,

    // The checks a school may record, and the ones it must record before it
    // clears someone. South African schools check the National Child
    // Protection Register (Children's Act, s126) and the National Register
    // for Sex Offenders (Criminal Law Amendment Act, s45). Only the kind of
    // check is stored, never a certificate or reference number.
    'checks' => ['identity', 'child_protection_register', 'sexual_offences_register', 'police_clearance', 'references'],
    'required_checks' => ['identity', 'child_protection_register', 'sexual_offences_register'],

    // The longest a clearance may last before the school checks again.
    'maximum_vetting_months' => 36,
];
