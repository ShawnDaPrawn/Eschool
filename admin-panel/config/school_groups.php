<?php

declare(strict_types=1);

/*
 * School groups: one owner, many schools (Action Plan 5.7, SA.18).
 * engineering/specs/platform/school-groups.md.
 *
 * min_count          small-number suppression: a count of people from 1 to
 *                    min_count - 1 is shown as "fewer than min_count", and a
 *                    share is hidden when its base is under min_count or
 *                    when the people it counts, or the people it leaves out,
 *                    number 1 to min_count - 1. Zero is shown. A group total
 *                    is hidden when any school's figure in it is hidden, so
 *                    a hidden figure cannot be worked out by subtraction.
 * attendance_days    the window of recorded school days the attendance rate
 *                    covers, ending today in each school's timezone.
 * agreement_version  the version of the wording a school's Super Admin
 *                    agrees to (lang key school_group_agreement). Change it
 *                    when the wording changes: memberships keep the version
 *                    and the hash of the text they agreed to.
 * roles              the group-level roles. A role grants abilities in the
 *                    group only, never inside a school: any school-level
 *                    access comes from that school's own role grants.
 *                    group.dashboard  the group's counts and shares across its
 *                                     current schools (no learner record)
 */
return [
    'min_count' => max(1, (int) env('SCHOOL_GROUPS_MIN_COUNT', 10)),
    'attendance_days' => 28,
    'agreement_version' => '2026-11',
    'roles' => [
        'admin' => ['label' => 'Group administrator', 'abilities' => ['group.dashboard']],
    ],
];
