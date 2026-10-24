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
