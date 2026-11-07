<?php

/*
|--------------------------------------------------------------------------
| Independent school business tools (Action Plan 5.10)
|--------------------------------------------------------------------------
|
| Re-enrolment contracts, bursary applications and alumni giving.
| engineering/specs/money/school-business-tools.md.
|
| Bursary household details and documents are erased on a fixed schedule,
| even when the module is switched off or the school is suspended:
| decided or withdrawn applications after bursary_keep_days, unsent drafts
| after bursary_draft_days without a change. The award itself lives on as
| the school's fee discount rule, which is financial history.
|
*/

return [
    'bursary_keep_days' => 730,
    'bursary_draft_days' => 90,
    'maximum_document_bytes' => 10 * 1024 * 1024,
    'maximum_documents' => 10,
    // Re-enrolment reminders: at most one notice per guardian, contract and day.
    'reminder_batch' => 500,
];
