<?php

// The daily family summary (Action Plan 5.11):
// engineering/specs/notifications/family-daily-summary.md.
return [
    // Offered to a guardian who opts in without choosing a time.
    'default_time' => '17:30',
    // Summaries (and every school's part) are erased this many days after their day.
    'retention_days' => 7,
    // "Important unread notices": school messages the guardian has not opened, published in the last N days.
    'unread_message_days' => 7,
    // A day with nothing to say sends nothing: the summary exists to cut messages, not add one.
    'send_when_empty' => false,
    // At most this many items of each kind per child go into a part.
    'max_items' => 5,
    // Updates about one child that the summary never describes, only counts
    // ("you have 1 private update"): every private or notice-only type
    // (config/private_notices.php and 'notice_only' in
    // config/notification_types.php), plus these, which carry marks or fees
    // detail even though their own notices are not private.
    'private_types' => ['academics.marks_released', 'academics.report_published', 'fees.'],
];
