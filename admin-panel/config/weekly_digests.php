<?php

return [
    'retention_weeks' => 26,
    'local_hour' => 7,
    'languages' => ['en', 'af', 'zu', 'xh', 'nso', 'st', 'tn', 'ts', 'ss', 've', 'nr', 'ar'],
    // Source-authored starting text, not a claim of fluent or school approval.
    // A school manager explicitly approves each immutable language version.
    'starting_templates' => [
        'en' => [
            'notice_prompt' => 'Open your private weekly learning update.',
            'actions' => ['practice' => 'Open school learning materials for a short practice session together.', 'marks' => 'Look at a released result together.', 'attendance' => 'Review the recorded attendance together.', 'calendar' => 'Review the school calendar for the coming week.'],
            'title' => 'This week for {learner}',
            'body' => "{week_start} to {week_end}. Released results: {marks_count}. Attendance recorded: {attendance_present} present, {attendance_absent} absent. Practice sets: {practice_completed} completed of {practice_total}. Upcoming school events: {event_count}. At home: {home_action}\nThis week {learner} grew in: {grew_in}.\nComing up at school next week, to talk about at home: {help_at_home}.",
        ],
    ],
    'tokens' => ['learner', 'week_start', 'week_end', 'marks_count', 'attendance_present', 'attendance_absent', 'practice_completed', 'practice_total', 'event_count', 'home_action', 'grew_in', 'help_at_home'],
    // Action Plan 4.6. A template line that uses one of these is left out when
    // it has nothing to show, so a week without growth never prints an empty
    // or negative line. There is deliberately no "fell in" placeholder.
    'optional_tokens' => ['grew_in', 'help_at_home'],
    'growth' => [
        // A topic "grew" when its mastery level rose, or its score rose by at
        // least this much (basis points), comparing like with like.
        'min_score_rise_bp' => 500,
        'max_topics' => 5,
    ],
];
