<?php

// Mastery score models (Action Plan 3.8). The rationale for every parameter
// is in engineering/specs/growth/mastery.md, "Mastery model v2".

return [

    // A school without a saved choice uses this model. Keep recent20-v1 so
    // nothing changes for a school until it opts in to v2.
    'default_version' => 'recent20-v1',

    'v2' => [
        // The newest answers per topic and audience that v2 reads.
        'window' => 40,

        // Each step back in time keeps this share of the newer answer's weight.
        'recency_ratio' => 0.95,

        // Marks are teacher-set and teacher-marked; practice is self-study with
        // the answer available offline, so it counts for half.
        'source_weights' => [
            'question_mark' => 1.0,
            'released_question_mark' => 1.0,
            'practice_review' => 0.5,
            'tutor_checkpoint' => 0.5,
        ],
        'default_source_weight' => 0.5,

        // Harder items (lower success rate across the school) weigh up to
        // 1 + strength, easier items down to 1 - strength.
        'difficulty_strength' => 0.5,
        // Fewer different learners than this on an item: no difficulty weight.
        'difficulty_min_responses' => 5,

        // Memory stability in days when a learner has no practice memory on the
        // topic, and how many pseudo-memories of that default each learner's
        // own practice stabilities are blended with.
        'default_stability_days' => 60.0,
        'stability_prior_count' => 3,

        // Above the midpoint, a score keeps the share E / (E + half) of its
        // lead, where E is the learner's fresh weighted evidence.
        'midpoint_bp' => 5000,
        'score_evidence_half' => 2.0,

        // Evidence sufficiency is E / (E + half): 12 fresh marked answers give 50%.
        'confidence_evidence_half' => 12.0,
    ],

];
