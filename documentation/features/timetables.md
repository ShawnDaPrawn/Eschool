# Academic timetables

Configure physical rooms and a bell schedule for each school section and year.
A lesson uses a dated teaching group or homeroom, an allocated teacher, a room,
a teaching period, and a day/date and week pattern. Breaks stay separate.

Preview placements before saving. The engine checks actual overlapping times
for teachers, rooms, groups, homerooms and shared learners, including overlaps
between different bell schedules. A conflict does not save part of the week.
Changed previews and stale edits require another preview.

Spreadsheet exports use explicit column and school-value mappings. Import is
bounded and atomic, so an unknown teacher/room/group or malformed row does not
leave a partially imported timetable. Keep the source privately for rehearsal.

Teachers read their authorized timetable; learners and parents read their own
or authorized child's dated lessons. Room and teacher names accompany the next
period. Existing timetable responses retain their fields and add a room label.
The panel workbench and shared adaptive editor are being added in the client
slice; this backend alone does not certify pilot or classroom acceptance.
