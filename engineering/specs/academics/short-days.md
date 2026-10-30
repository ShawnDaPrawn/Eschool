# Short days (day patterns)

Action Plan step 1.7. Schools often run a shorter day: a staff development
afternoon, a sports day, an exam week, a public-holiday eve. Today the timetable
only knows the normal bells, so the app shows lessons at the wrong time and the
office re-explains the day by WhatsApp.

## Model

`bell_schedule_overrides` (school-owned): `session_year_id`, `on_date`,
`bell_schedule_id` (the schedule being replaced), `replacement_bell_schedule_id`,
`reason`, `revision`. One row per replaced schedule and date. The short-day
bells are an ordinary bell schedule in the same school year (for example
"Short day (Senior)"), built in the Bell schedules section.

## Rules

- **Order, not clock time.** A lesson keeps its placement. On the short day it
  follows the replacement's teaching period in the same order: the third lesson
  of the day takes the third short-day period. Breaks never count.
- **Trailing lessons drop off.** When the replacement has fewer teaching periods,
  the later lessons do not take place that day. `TimetableEngine::dates()` and
  `occursOn()` leave the date out, so registers, relief cover, room capacity and
  clash checks treat the lesson as not happening; `scheduledOn()` still reports
  the placement.
- **Clash-safe.** Saving or removing a short day re-checks that date with each
  lesson's actual time and any assigned relief teacher: teacher, room, teaching
  group, homeroom and shared learners. A short day that would put two lessons
  on top of each other is refused and nothing is saved. Placement previews use
  short-day times on short-day dates, and a preview made before a short day
  changed can no longer be committed (the fingerprint includes the overrides).
- **Forward only.** Short days are planned or removed for today or later. A
  short day that would remove a lesson whose register was already taken is
  refused.
- **Managers only.** The same timetable-management right as rooms and bells,
  checked fresh on every write. Every ID resolves inside the current school.
- **Audited.** `academics.short_day_saved` and `academics.short_day_removed`.

## Who sees what

- **v2 week and next.** `GET /api/v2/academics/timetable/week` and `/next` return
  moved lessons with that day's `starts_at`, `ends_at` and `period_name`, and the
  new optional `day_pattern` (the short-day bells' name). Lessons that do not
  take place are left out, so installed apps that ignore `day_pattern` still
  show the right times and never a cancelled lesson. Showing "cancelled for the
  short day" rows needs an app update and is deferred to that work.
- **Panel.** The academic timetable workbench has a Short days section (plan a
  date, choose short-day bells per schedule, remove a planned day) and the week
  view labels each short day and moved lesson.
- **v1.** The legacy weekly projection keeps the normal bells; v1 has no dated
  week to put a short day on.

## Notifications

`academics.timetable_changed` (academics module, every portal, standard channel
preferences). When a short day is planned or removed, the learners in the
affected lessons, their current guardians and the lessons' teachers get "Short
day on 17 Feb 2026" or "Normal bells on 17 Feb 2026". The notice names only the
date, never the reason or other people. The outbox event
`academics.timetable_changed` carries the affected lesson IDs only.

## Acceptance tests (`Academics/ShortDaysTest`)

| Given | When | Then |
| --- | --- | --- |
| Three Tuesday lessons; short bells with two periods | a short day is planned | the parent's week shows the first two at the short times with `day_pattern`, the third is gone; other Tuesdays unchanged; the next-lesson card uses the short time |
| The same | a short day is planned | the learner, parent and teacher are notified; another learner is not; the reason is not in the notice |
| A planned short day | it is removed | normal times return; "Normal bells" notice; a second removal finds nothing |
| A Junior lesson that the new bells would overlap | the short day is saved | refused with a teacher clash; nothing saved; no notice |
| A placement that clashes on normal bells but not on the short day | previewed before and after | refused before, allowed after with a new fingerprint; later bells that would clash with it are refused |
| A teacher, parent or learner; a past date; the same or no replacement; another school's bells | a short day is saved | 403; 422; 422; 404 |
| The workbench | a manager plans, views and removes; others try to remove; a stale revision | saved and shown; refused; error kept on the page |
| The family v2 week | read on the short day | matches `openapi.yaml` with `day_pattern` |

Browser: `e2e/tests/academic-timetable.spec.ts` checks the short-day form's labels,
accessibility and fit at 1024 and 1280 pixels.

## Not yet

- App rows for lessons cancelled by a short day (needs a client release).
- Whole-school presets ("apply to every Senior schedule") beyond choosing each
  schedule on one form.
- A v2 write API for short days; the panel is the only writer for now.
