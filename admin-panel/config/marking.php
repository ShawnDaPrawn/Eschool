<?php

/*
 * Marking engine (Action Plan 3.16): engineering/specs/learning/marking-engine.md.
 *
 * The accuracy gate (Action Plan 3.18): engineering/specs/learning/marking-accuracy-gate.md.
 * Learners receive engine marking in a subject only when its latest evaluation
 * run on the current engine and profile versions passed, and an HQ approver
 * switched the subject on from /saas/marking-accuracy. Teachers' "mark this
 * answer against the memo" preview is not affected.
 *
 * Every figure under gate is a PLACEHOLDER until the owner decides it: the
 * rule is "agreement within the tolerance moderation accepts for human
 * markers, on at least 200 teacher-marked scripts". Runs are measured against
 * these figures (each run keeps a copy), but no subject can be switched on
 * while owner_confirmed is false. Change them, and owner_confirmed, only in a
 * reviewed commit that records the owner's decision: there are deliberately
 * no environment variables.
 */
return [
    'gate' => [
        // Subjects (marking profile keys) that can be evaluated and switched on.
        // The generic profile covers every other subject, so it never is.
        'subjects' => ['mathematics', 'physical_sciences'],

        // The owner has confirmed the figures below. Until then no subject can be switched on.
        'owner_confirmed' => false,

        // Fewest teacher-marked scripts in a subject before a run can pass (placeholder: the plan says 200).
        'min_scripts' => 200,

        'tolerance' => [
            // How far the engine may differ from the teacher on one mark point, in marks (placeholder).
            'point_marks' => 0,
            // How far the engine may differ from the teacher on a script's total, as a percentage of the script's maximum (placeholder).
            'script_percent' => 3,
        ],

        'thresholds' => [
            // Share of mark points on which the engine agrees with the teacher within the point tolerance (placeholder).
            'point_agreement_percent' => 95,
            // Share of scripts whose total agrees with the teacher's within the script tolerance (placeholder).
            'script_agreement_percent' => 90,
            // Largest systematic bias allowed: the mean signed difference of script totals, as a percentage of the mean script maximum (placeholder).
            'max_bias_percent' => 1,
        ],

        // Bounds on the work one run or one import does.
        'max_scripts_per_run' => 5000,
        'max_scripts_per_import' => 1000,
        'max_import_bytes' => 2097152,
    ],
];
