---
title: Learner diary
---

The learner diary replaces a paper homework diary. Learners use it in the
learner app for their own notes and to-dos, and the school's homework, events
and holidays appear in it automatically.

## Switching it on

Open **Settings → School modules** and enable **Learner diary**. It needs the
People and Academics modules. The diary is off by default. While it is off the
learner app hides it and the diary API answers *not found*.

Only a current, guardian-approved learner of the school can use it. There is no
panel page for it.

## In the learner app

Learners open **My diary** on the home screen. Each day has its own page, with
a week strip to move between days:

- **From school**: homework due that day (with the subject, due time and, once
  marked, the mark), school events and holidays. Learners tick items off as they
  go; handed-in homework is ticked automatically.
- **My to-dos** and **My notes**: what the learner writes for that day. To-dos
  without a date appear on today's page until they are done.
- **Remind me**: a reminder the learner's own phone shows at the chosen time.
  The school sends nothing, and the reminder hides its text on a locked screen.

## Privacy

A learner's diary is private to that learner. Parents, guardians, teachers,
school administrators and platform staff cannot read or change what a learner
writes. Nothing in the panel, the parent app, the teacher app, exports, reports,
search or AI features uses it.

- Titles, notes and to-dos are encrypted with a key that belongs to that
  learner at that school. A copy of the database does not reveal them.
- Ticks and private notes the learner adds to homework stay in the diary. The
  teacher still sees only whether the homework was submitted, as before.
- Deleting an entry removes it permanently.
- The diary is not monitored. It offers a **Talk to someone** link to the
  school's wellbeing reporting, which the learner can choose to use.

Before switching the diary on, the school's Information Officer should confirm
how the school answers a parent's request to see a young learner's notes under
POPIA. The system gives nobody a way to read them.

## What appears automatically

| Item | Who sees it | Where it appears |
|---|---|---|
| Homework | Learners in the class it was set for; for an elective, only learners who take the subject | On the due date, and on today's page while it is late and not submitted (up to 30 days) |
| Holidays | Every learner | Each day of the holiday |
| School events | Every learner | From the start to the end date |

Nothing is copied. If a teacher moves a due date or deletes homework, or a
learner changes class, the diary shows the change straight away. Submitted
homework counts as done, and the mark appears once the teacher accepts it.

## Limits

A learner can keep up to 2,000 entries. A title can be up to 200 characters,
a note up to 10,000 and a note on a school item up to 2,000. One request reads up
to 62 days.

## Encryption key

Production sets a separate `DIARY_KEY` secret (32 random bytes, base64). Until
it is set, the diary uses the application key. Setting `DIARY_KEY` later keeps
older entries readable. Never remove or change a diary key that still has
entries, because those entries can then no longer be read.
