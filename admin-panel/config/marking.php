<?php

/*
 * Marking engine (Action Plan 3.16): engineering/specs/learning/marking-engine.md.
 *
 * learner_marking: whether learners may receive engine marking in a subject
 * profile. Every subject stays off until it passes the accuracy gate (Action
 * Plan 3.18): agreement with teacher marks, within the tolerance moderation
 * accepts for human markers, on at least 200 teacher-marked scripts. Only a
 * literal true switches a subject on, and the change belongs in a reviewed
 * commit with that evidence: there is deliberately no environment variable.
 * Teachers' "mark this answer against the memo" preview is not affected.
 */
return [
    'learner_marking' => [
        'mathematics' => false,
        'physical_sciences' => false,
        'generic' => false,
    ],
];
