<?php

declare(strict_types=1);

/*
 * The leaver state (Action Plan 1.21): a learner who left at the year switch,
 * and a guardian whose children at the school all left, read the school's
 * records about them until leaver_until, and write nothing there.
 * engineering/specs/academics/new-year-structures.md, "Leaver state".
 */
return [
    /*
     * How long read-only access lasts when it does not end on 31 March: a
     * learner marked as leaving the current year (never past the next 31
     * March), or a switch made after the 31 March of the new year.
     */
    'access_days' => (int) env('LEAVER_ACCESS_DAYS', 90),

    'read_only' => [
        /*
         * Requests a leaver (or a guardian acting for one) may still send as
         * POST, PUT, PATCH or DELETE ($request->is() patterns): reads sent as
         * POST, read receipts and acknowledgements, which change nothing the
         * school relies on, and a guardian's transfer consent. Decisions that
         * only take access away (withdrawing an approval, rejecting a handle
         * proposal, switching an optional fee item off) are let through by
         * LeaverState::onlyRemovesAccess.
         * Everything else that writes is refused (App\Services\People\Leavers\
         * LeaverAccess and LeaverState::guardFamilyWrite). Signing out and
         * changing one's own password run without a school (school.context:
         * optional) and are never refused. Deny by default.
         *
         * Not listed: api/v2/live/session and api/v2/live/authorize issue and
         * use a live-updates lease (a write), which leavers do not need.
         */
        'allow' => [
            // Reads sent as POST.
            'api/parent/get-leave-list',
            'api/parent/get-user-message',
            'api/student/get-leave-list',
            'api/student/get-user-message',
            'api/v2/wellbeing/own/follow-up',
            'api/v2/wellbeing/own/photos/download',
            // Acknowledgements: accepting the school's terms, which every parent-app read needs.
            'api/parent/policy-acceptance',
            // A guardian consenting to (or withdrawing) a transfer that sends the leaver's
            // records to a new school, which staff prepare and release.
            'api/v2/transfers/packages/*/consent',
            'api/v2/transfers/packages/*/withdraw',
            // Read receipts.
            'api/parent/read-all-message',
            'api/v2/communication/messages/*/read',
            'api/v2/wellbeing/notices/*/read',
            'api/v2/academics/reports/cards/*/read',
        ],
    ],

    /*
     * The v1 learner API (student.session.active) a leaver may use: their own
     * profile and history only. Pages about the current year's class
     * (timetable, lessons, assignments, announcements, the class's exams,
     * online exam questions, chat contacts) are refused: the leaver is not in
     * that class any more.
     */
    'student_api' => [
        'api/student/get-profile-data',
        'api/student/parent-details',
        'api/student/get-notification',
        'api/student/exam-marks',
        'api/student/offline-exam-report',
        'api/student/online-exam-report',
        'api/student/leave-report',
        'api/student/assignment-report',
        'api/student/get-online-exam-result-list',
        'api/student/get-leave-list',
        'api/student/get-user-message',
    ],
];
