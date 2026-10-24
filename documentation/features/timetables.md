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
Open **Academic timetable** in the panel to choose the school year and week.
Expand Rooms to create a room; expand Bell schedules to create ordered periods
and breaks. Copy a used schedule before changing its periods. Choose a teaching
group or homeroom, teacher, room, period and dates under Place a lesson. Preview
before saving: clashes keep Save disabled. Edit a week entry to move or cancel
it. A competing edit needs a new preview; its saved placement is preserved.

For imports, paste CSV/TSV under Import timetable, match its columns, then select
the exact school record for each exported label. Review the clashes before
committing. Unknown names are never guessed. Keep the original vendor export for
the pilot rehearsal.

In both apps, the next-period card on Home shows the room and teacher. View week
opens a chronological phone view or a seven-day desktop view. The same week is
available in Settings. Academic managers can preview and save placements there;
teachers and families read their authorized lessons. Parents use the selected
child. Changing school or child clears the previous timetable. The school
calendar and timezone decide the initial week, independently of the phone clock.

Synthetic checks do not certify real classroom timings, physical room walks,
staging load or the full-term pilot. These remain separate acceptance checks.
