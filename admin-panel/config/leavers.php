<?php

declare(strict_types=1);

/*
 * The leaver state (Action Plan 1.21): a learner who left at the year switch,
 * and a guardian whose children at the school all left, read the school's
 * records about them until leaver_until, and write nothing there.
 * engineering/specs/academics/new-year-structures.md, "Leaver state".
 */
return [
    'read_only' => [
        /*
         * While a person is read-only at a school (App\Services\People\Leavers\
         * LeaverAccess, called from the school.context middleware), every POST,
         * PUT, PATCH and DELETE there is refused except these ($request->is()
         * patterns): requests that only read but are sent as POST. Signing out
         * and changing one's own password run without a school (school.context:
         * optional) and are never refused. Deny by default.
         */
        'allow' => [
            'api/parent/get-leave-list',
            'api/parent/get-user-message',
            'api/v2/wellbeing/own/follow-up',
            'api/v2/wellbeing/own/photos/download',
        ],
    ],
];
