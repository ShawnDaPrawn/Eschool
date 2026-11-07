# Daily family summary (Action Plan 5.11)

One short message a day instead of many. A guardian who opts in gets, at the
time they choose, one summary across **all their children and all their
schools**: today's and tomorrow's timetable changes, homework due, forms
waiting for them, how many school messages they have not opened, and how many
private updates are waiting.

## The job

`family:daily-summary` runs every 15 minutes (`routes/console.php`,
`family-daily-summaries`). For each enabled row of `family_summary_preferences`:

1. Not before the chosen `send_at` on the guardian's local day (their
   `timezone`), and not while their quiet hours run. `send_at` must be a
   quarter hour (:00, :15, :30, :45): the job's own runs, so even 23:45 is
   reached before the day ends (every real UTC offset is a multiple of 15
   minutes). It must also be outside quiet hours when it is saved, compared in
   the quiet hours' own time zone (20:00 UTC is 22:00 in Johannesburg). If
   quiet hours later move over it, the summary waits for them to end, on the
   same day only.
2. Claim the day: one `family_daily_summaries` row per guardian per local date
   (unique `user_id, summary_date`). A finished day (`sent` or `empty`) is
   never composed again; an interrupted one (`composing`) is finished on the
   next run.
3. For each school where the guardian has an active Parent membership, inside
   `CurrentSchool::run(school)`: read that school's facts and write that
   school's part (`family_daily_summary_parts`, school-owned).
4. A day with nothing to say sends nothing (`send_when_empty` is off). Otherwise
   one private `family.daily_summary` notice is filed in the first school with
   a part and assigned to the guardian; it says only that the summary is ready.

`family:daily-summary --retention` (daily, 03:45) erases summaries and every
school's part after `retention_days` (7).

## Keeping schools apart

- Each school's part is **read inside that school** through the existing family
  authority: a fresh Parent role at that school, `GuardianService` children and
  `ReleasedMarks::learner` for each child, a learner who has not left, and each
  source's own guard (`TimetableQueries::week`, the diary's homework rule,
  `FamilyFormAccess::guardian`, `RecipientAccess`/`MessagePolicy::reader`,
  `NotificationVisibility`). Nothing of school A is read for a guardian who is
  not linked there.
- Each school's part is **written inside that school**: one AI call per part,
  with only that part's facts, metered and (if flagged) quarantined in that
  school. No school's facts reach another school's AI receipt or reviewers.
- `family_daily_summaries` is platform-level (one row per guardian per day) and
  holds no school content; the parts are school-owned.
- Reading a summary (`GET /api/v2/me/family-summaries/{id}`) re-reads each part
  in its own school and shows it only while the reader is still that school's
  guardian of every child it is about. A removed guardian loses that school's
  part at once. Another person's, an expired or an emptied summary is 404.
- Each part keeps the identities from the same collection that produced its
  facts, with those relationships checked again before saving. A later child
  list cannot substitute a remaining sibling for a child whose relationship
  was removed. The internal identity binding is kept out of stored facts and
  model input.
- The notice is private (`family.` in `config/private_notices.php`):
  `FamilySummaryNotices` filters the inbox and rechecks delivery against the
  owner, the school it was filed in, the state and expiry.

## Wording and the AI fallback

The AI gateway feature `family_summary` (`config/ai.php`, off by default, roles
Parent, Family audience, no learner scope) with `FamilySummaryFeaturePolicy`
and prompt `family-summary`. The model receives exactly
`FamilySummaryWriter::modelInput`: language, school name and, per child, the
first name and the timetable, homework and form facts. No surnames, ids,
counts, marks, discipline, wellbeing, health or fees detail.

`FamilySummaryModeration` runs the shared moderation, then refuses an answer
that is not short plain text or mentions a private topic (marks, results,
discipline, wellbeing, health, fees). When the AI is off, capped, failing or
refused, the plain template is used and the reason kept on the part
(`fallback_reason`). The unread-messages and private-update lines are always
the template's own: "You have 1 private update. Open your notifications to read
it." Private updates are counted from the last day's notices of every private
or notice-only type, plus marks, reports and fees (`private_types`).

## API

- `GET /api/v2/me/family-summary/preferences`, `PUT` the same path
  (`enabled`, `send_at` HH:MM on a quarter hour, optional `timezone`): the guardian's own choice,
  for every school. Only a guardian at the current school (403 otherwise).
- `GET /api/v2/me/family-summaries/{summary}`: one summary, a section per
  school.

## Tests

`tests/Feature/FamilySummary/FamilyDailySummaryTest.php`: two schools, timetable
and forms facts, a guardian at one school, a non-guardian, a removed guardian
(including removal between collecting facts and saving the part),
no private detail in the model input, four fallbacks, quiet hours, once per
day (with crash recovery and retention), wrong role and wrong owner.

## Not in this step

App screens (the notice opens `/family/daily-summary/{id}` in the Parent app);
a per-school module switch for the AI wording; a quarantine review participant
for flagged answers.
