# Academic timetable operations

Run migrations before using the timetable API. Keep the ordinary scheduler
running: `academics:refresh-allocations` refreshes both dated allocations and
compatible current-week timetable projections every minute. Canonical dates,
week patterns, cancelled lessons, group state and allocations remain authoritative
on reads while that refresh is delayed. Use a tested backup for rollback once
canonical lessons exist; the migration refuses to discard their provenance.

Managers configure physical rooms and ordered bell schedules for a school year
and section. Capacity is optional when unknown. Break periods cannot host lessons.
A used bell schedule retains its period IDs: copy it and move lessons to the new
periods before retiring it. Move or cancel future lessons before deactivating a
room. Changes use revisions and the school transaction lock.

A placement chooses exactly one teaching group or homeroom, its allocated
teacher, room, period, date range, weekday and all/odd/even ISO week pattern.
An optional specific date must match that range and weekday. Preview uses actual
time overlaps, including different schedules and overlapping school-year ranges.
Teachers, rooms, groups, homerooms and shared dated learners participate in
clashes; existing unmatched timetable rows also participate. Different period
IDs do not make two overlapping times safe.

Commit the normalized preview rows and its unchanged fingerprint. A changed
roster, allocation, configuration, legacy timetable or competing placement
requires a new preview. A conflict writes no lessons. Edits include original
lesson revisions; cancelling increments the revision and preserves history.
Old timetable writers cannot change protected projections or overlap canonical
lessons. Existing v1 fields retain their types; room_name is additive. Projection
source IDs remain private. Mixed groups and subjectless homeroom lessons use
precise v2 reads where the old whole-class subject schema cannot represent them.

## Spreadsheet and timetabling exports

Export a sheet as comma-separated or tab-separated UTF-8 text, at most512KiB and
300 lessons per atomic import. A header is required. Canonical columns use the
placement field names. Exported names, including FET/aSc or other timetable
export labels, use explicit `columns` and `values` mappings; ambiguous names are
never guessed. The pilot's actual vendor layouts still need import rehearsal.

For example, map the exported `Students Sets` column to `teaching_group_id`,
`Teachers` to `teacher_id`, `Room` to `room_id`, `Day` to `weekday` and `Hour` to
`bell_period_id`. Map each exported value to the selected school's concrete ID,
and provide the year, date range, term and week pattern as defaults. The same
adapter handles a spreadsheet whose headers differ. Each mapped column and
value must exist. Unmapped or malformed rows reject the whole preview, without
creating lessons. Commit uses the same placement fingerprint and atomic service.

This backend slice provides configuration, preview/commit/import and private
week/next-period reads. The panel workbench, shared adaptive editor and home card
are the following client slice; this is not complete Phase2 acceptance.

## Access and operations

Only fresh academic-structure managers configure or place lessons. Teachers read
their authorized groups and assignments. Learners read their own dated roster;
parents select only a currently authorized child. An ended guardian link revokes
access immediately. Family results contain no learner lists. There is no fee
condition. Queue outbox events with the standard worker and dispatch scheduler.

Rehearse real vendor exports, week editing, actual room walks and school timezone
boundaries on staging. Synthetic HTTP/contract tests do not prove classroom UX,
1,200-learner staging performance, a full-term pilot or independent review.

The new-year wizard carries only original legacy entries. A current-week canonical
projection must never be copied into another year: create that year’s dated groups,
allocations and bells and preview its calendar. Source-year equality also protects
reads from historical bulk copies. This does not block other year-end transfers.
