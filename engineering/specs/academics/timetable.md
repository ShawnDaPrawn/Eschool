# School timetable engine

Extend the existing Laravel/app timetable rather than replacing it. School-owned
rooms record building, capacity, type and active state. Bell schedules belong to
a school/year/section with ordered periods, start/end times and break markers.
Lessons use a teaching group or homeroom, an allocated teacher, room, period,
weekday or date and a week pattern. All IDs resolve under the current school.

Writes and preview use the same clash calculation. Overlapping actual time
ranges conflict for a teacher, room, teaching group, homeroom and shared learners,
including different bell schedules and dated memberships. Date, weekday,
term/year boundaries, odd/even week patterns and cancellations are respected.
School-level transaction locks serialize competing placements; optimistic
revisions prevent lost edits. Do not treat a different period ID as disjoint.
Inactive rooms, too-small capacities and stale allocations are explicit errors.
A bounded CSV import previews every row and commits all-or-nothing with the
unchanged preview fingerprint. Existing unmatched timetable rows participate in
clash detection. Canonical projections are protected from old timetable editors.

V1 keeps its existing response fields. Canonical lessons project dated active
entries to timetables/subject_teachers, adding room labels without changing field
types. The v2 week query resolves the requesting teacher's allocations, a
learner's dated memberships or a fresh guardian's child and hides other rosters.
The next-period home query gives room and teacher without requiring a timetable
tap. Manage rights are separate from teacher/family read rights.

The shared academics package receives an adaptive week view/editor and conflict
preview before saving. Existing phone timetable flows remain available. Test
happy path, wrong role/school/owner, cross-schedule time clashes, membership
clashes, patterns/date overrides, concurrent/stale writes, import atomicity,
legacy projections and next-period boundaries. Cover new app screens with cubit,
widget/browser checks and any new panel page with both Playwright widths.

Short days (one-date bell schedule replacements) are specified in
[`short-days.md`](short-days.md).

## Breaks and activities in the week view (Action Plan 1.6)

A learner's week (the learner, or their guardian with `learner_id`) now also shows:

- **Breaks.** The named break periods (`bell_periods.is_break`) of the bells their lessons follow each day, with their times. On a short day it shows the replacement bells' breaks. A day without lessons shows no breaks.
- **Activities.** Sessions of activity groups the learner is a current member of on that day, not cancelled: name, group, venue, date and, when set, start and end time. A session without times is all day. Nothing shows when the activity groups module is off.

Activity sessions gain optional `starts_at` and `ends_at` (HH:MM). Both or neither must be given, and the end must come after the start. Existing sessions have none. They are set in the activity workbench and through the activities API (`ActivitySessionWrite`).

A staff member's own week returns empty `breaks` and `activities`. The week response always has both (`AcademicTimetableWeekData`). Who may read whose week is unchanged.

| Given | When | Then (`Academics/WeekBreaksActivitiesTest`) |
| --- | --- | --- |
| Asha has a Tuesday lesson with a long break after it, and Hockey on Tuesday 14:30-16:00 and Thursday (no time); a cancelled and a next-week session; a session of a group she is not in | her guardian, then Asha, read the week | one break on Tuesday; Practice and Match day only, with times or null; a teacher's own week has neither |
| A session with an end before its start, one time only, or a non-time | saved | refused |
| Gina (not Asha's guardian), School B, the groups module off | read Asha's week | refused; refused; breaks but no activities |

Not in this step: the apps' week screen colours and layout, activity sessions in the calendar feed, and per-learner bell schedules other than those their lessons follow.

Activity-session updates from older clients that omit both optional time fields
retain the saved times. Sending both fields explicitly as null makes the session
all-day. Rolling back the added time columns refuses while any saved time exists.
