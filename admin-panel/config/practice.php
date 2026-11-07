<?php

// Daily practice (Action Plan 3.11): engineering/specs/learning/question-types.md.

return [

    // Clients that do not send `X-Practice-Contract: 2` (the installed apps)
    // still get sets with answers and explanations included, choice and
    // number questions only. This keeps the old offline answer loophole for
    // those clients alone. Switch it off once the apps read the answer-free
    // contract; legacy practice requests are then refused with 426.
    'legacy_set_contract' => (bool) env('PRACTICE_LEGACY_SET_CONTRACT', true),

];
