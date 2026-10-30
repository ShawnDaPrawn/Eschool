# Exam mode

Action Plan step 1.8. During exams, a learner's week should show exam days
rather than a normal timetable, and families should hear at once when an exam
moves.

## Exam periods

`exam_periods` (school-owned): a name, a grade (`grade_levels`), a school year,
a first and last exam day, and `lessons_continue` (default no). Periods for one
grade never overlap and fall inside the school year. Staff who keep the exam
timetable (`exam-timetable-create`) manage them at `/exam-periods` ("Exam
periods" under Exams and results); each save or removal is audited
(`academics.exam_period_saved`, `academics.exam_period_removed`).

## The week

`TimetableQueries::week` gains `exam_days`: the dates in the week on which the
learner's grade (the grade of their homeroom's class that day, from
`enrolments`) is in an exam period. On those dates the learner's lessons are
left out unless the period keeps them. Staff views return an empty list and
are unchanged. `next` follows the week, so the next-lesson card skips exam days
too.

`GET /api/v2/academics/timetable/week` returns `exam_days` as an optional
array of dates (`AcademicTimetableWeekData.examDays` in the Dart client). The
learner app's week screen shows "Exam day: see your exam timetable" on those
days.

## Sittings

`exam_timetables` gains `venue` (up to 120 characters, optional) and
`revision` (starts at 1). The exam timetable form has a venue field on each
subject row; the list shows it, and the v1 exam endpoints return it
(`venue` and `revision` in the learner's exam details).

A saved change to a sitting's date, start or end time, or venue raises its
revision and tells the learners currently in that class, and their current
guardians, through the notification outbox: type `academics.exam_changed`
("Exam changes", academics module, learner and parent portals, standard
channel preferences). Removing a sitting sends "Exam removed". Only upcoming
sittings (today or later, in school time, before or after the change) are
announced. Times are compared as HH:MM, so a time written another way is not a
change, and neither is a change to marks. Rows written by the mark projection
(`MarkExamProjection`) are never announced. The notice names the subject, the
new date, times and venue, and no learner.

## Tests

`Academics/ExamModeTest`: exam days replace the learner's lessons unless they
continue, and other grades and staff views are unaffected; periods stay inside
the year and never overlap for a grade; a venue or time change tells the class
and its guardians once and bumps the revision, while an equal time or a marks
change does not; past sittings change quietly and a removed upcoming one is
announced; the legacy form saves a trimmed venue; the panel page manages
periods for its own school only and refuses teachers; the family week API
returns `exam_days` within the OpenAPI contract. `academic-timetable.spec.ts`
adds and removes a period in the browser with axe and an overflow check.

## Not yet

- Seat numbers per learner and sitting.
- Exam sittings shown inside the week view itself (the app links to its exam
  timetable instead).
- Elective subjects: a change is announced to the whole class.
