# Practice motivation: streaks, goals, gentle nudges and "now secure"

Action Plan 3.13, "Motivation and parent nudges". Owner role: Backend.

Goals, celebrations and gentle nudges for daily practice, built on the
existing practice, mastery, recognition, digest and notification features. A
child's practice is never ranked, scored against another child, or used to
shame. Everything here describes one learner, to that learner and their own
guardians.

## User stories

- As a **learner**, I see my practice run (how many days I have kept it
  going) and my best run, and I know a weekend, holiday or one missed school
  day a week does not break it.
- As a **learner**, I set my own weekly goal ("practise on 3 days a week"),
  see how many days I have practised this week, and get one short "well done"
  note the week I reach it.
- As a **learner or parent**, when a topic that needed more practice becomes
  secure, I get one short "something to celebrate" note and can see the topic
  in plain words, with no score.
- As a **parent**, when my child's practice has lapsed for a few school days, I
  may get a short, warm reminder, at most once a week and never in my quiet
  hours, if the school switched reminders on.
- As a **parent**, my Monday learning update tells me when my child reached
  their weekly goal and how long their practice run is, and never that they
  missed one.
- As a **school manager** (an Admin who manages the school, or the principal),
  I switch guardian and learner reminders on or off and set after how many
  school days they go out. As an **awards manager**, I switch practice run
  badges on or off. Everything starts off.

## Rules

### School days

A school day is a Monday to Friday inside one of the school's terms that is
not one of its holidays (the `holidays` table, which is also where a school
records a closure). A school that has not dated any term counts every weekday
that is not a holiday. Only the current school's terms and holidays are read.

### Practice run (streak)

- A **practice day** is a day on which the learner completed at least one
  daily practice set, in any subject.
- A run counts practice days. Only a **missed school day** can break it.
  Weekends, term breaks, holidays and closures never break it, and practising
  on one of them still counts.
- **One rest day a week.** The first missed school day in a week (Monday to
  Sunday) keeps the run going; a second missed school day in the same week
  ends it (`motivation.streak.rest_days_per_week`, 1). A rest day is used only
  while a run is going.
- **Today never breaks a run**: the day is not over.
- **Best run** is the longest run in the last year (`lookback_days`, 366).
- The existing `streak_days` on a daily practice set and review now uses this
  rule (it used to count calendar days in a row). The field and its type are
  unchanged.

### Weekly goal

- The learner sets their own goal: practise on 1 to 7 days a week, or clears
  it. Guardians can see the goal and progress but not change it. Staff do not
  see it (there is no list of learners, so nothing can be compared).
- Writes carry an operation ID (an exact retry replays) and the goal's
  revision (a stale one gets 409), and are audited (`practice.goal_set`,
  `practice.goal_cleared`).
- When this week's practice days first reach the goal, the learner gets one
  `practice.goal_met` note that week (`practice_goal_weeks`, unique per
  learner and week). Lowering the goal later that week never celebrates again.
  A week that falls short is never reported as a failure.

### "Now secure" (`mastery.gap_closed`)

- **Family-visible mastery only**: the `family` audience when the school opted
  in to family mastery from released marks, otherwise `practice` (the same rule
  as the 4.6 growth block), on the school's chosen mastery model. Staff-only
  mastery is never used, because it includes unreleased marks.
- **A gap** is a topic whose level in that audience and model is known and below
  secure (below level 3, the boundary `MasteryService::roots()` uses for
  "topics to work on"). Unknown is never a gap, so a first secure result is not
  a closed gap. Open gaps are kept in `mastery_topic_gaps`.
- **Closed** when the same audience and model reach level 3 or more with at
  least 3 pieces of evidence (`motivation.gap_closed.min_evidence`). A gap from
  another audience or model version is dropped, never compared.
- **Once per topic per term.** `mastery_gap_closures` is written before the
  notice and is unique per learner, topic and term (the topic grade's running
  term, or the latest one that started, so a holiday belongs to the term
  before it; the school year when no term is dated). A rebuild that changes
  nothing changes nothing; a level that dips and rises again in the same term
  never notifies twice.
- It is observed inside `MasteryRecompute`'s school/subject mutex after every
  aggregate (marks, releases, practice, tutor checkpoints, rebuilds).
- Recipients: the learner and their current guardians, only while the learner
  is current and guardian-approved. An early years learner (no sign-in) is not
  sent one; their guardians are.
- The notice says "Something to celebrate: A topic that needed more practice is
  now secure. Open the mastery map to see it." It names no topic, child, score
  or count, so a lock screen or mailbox shows nothing private. The topic name
  is shown in the app summary (below), in plain words with no score.

### Gentle nudges (`practice.nudge`)

- Both **off by default**: guardian reminders and learner reminders are
  switched separately, with N school days from 2 to 10 (3 by default).
- A learner has **lapsed** when they have practised before (in the last year),
  have not practised today, and have not practised on the last N school days
  before today. Weekends, breaks and holidays are not counted. A learner who
  never started is left alone.
- Sent by the hourly `practice:nudges` command only on a **school day**,
  between 15:00 and 19:00 school-local time.
- **At most one per person per week** (`practice_nudges`, unique per person and
  week), whichever child it is about. A guardian of two lapsed children gets
  one.
- **Never during quiet hours**, on any channel: while a person's quiet hours
  run, nothing is created for them and a later hourly run tries again.
- **Opt-outs**: a person who switched the reminder off on every channel gets
  nothing, and nothing is recorded.
- Only learners who are current, guardian-approved and in a stage with daily
  practice. Early years learners and their guardians never get one.
- The text is warm and plain, and names no child, count or score:
  "A gentle practice reminder: Daily practice is ready in NavigateEd whenever
  your family has a few minutes. Every short session helps." and, for a
  learner, "Your practice is ready: Your daily practice is ready when you are.
  A few minutes is enough to pick up where you left off."

### Recognition: practice run badges

- A third persistence badge beside the 4.6 practice and comeback badges, in the
  same awards settings ("Practice run badges", `streak_enabled`, **off by
  default**), presented by the same Monday job through the same ledger
  (`recognition_badges`, kind `streak`).
- A milestone (5, 10, 20 and 40 days, `motivation.streak.milestones`) is
  presented when the learner's run reaches it during the week, once per learner
  and milestone each school year, even after a manager withdraws it.
- It is a permanent award with source `badge`, so families are told through the
  existing `recognition.award_presented` notice, audit and outbox events.
- Where the other new events fit `recognition.*`: a weekly goal and a secure
  topic are not awards, so they have their own types; `recognition.*` is used
  only for the badge, which is an award.

### Parent digest

- Two new optional placeholders extend the 4.6 growth block:
  `{practice_goal_met}` (the goal's days, when the learner reached their goal
  that week) and `{practice_streak}` (the run at the end of the week, when it is
  two days or more). A template line with an empty one is left out, so an
  update never says a goal was missed or a run broke.
- The English starting text gains two lines. As before, a school sees them only
  once a manager approves a template version that contains them.
- The values are stored with the growth topics in the encrypted
  `weekly_parent_digests.growth` (`practice`).

### Stages

Daily practice is limited to junior and high school (4.1), so an early years
learner is refused the summary and goal (the module is not offered to them)
and a guardian is refused the summary for an early years child. No nudge,
celebration or "now secure" note goes to an early years learner account.

### Tenancy

Every new table is school-owned and in `SchoolOwnedTables`. Streaks, goals,
nudges, gaps and closures are read and written only in the learner's own
school. School B's run never nudges School A's families, and School B never
reads School A's streaks, goals or notices.

## Data

| Table / column | Purpose |
| --- | --- |
| `recognition_badge_settings.streak_enabled` | Practice run badges on or off (off). |
| `practice_motivation_settings` | One row per school: guardian and learner reminders (off), N school days, who changed it, revision. |
| `practice_goals` | One goal per learner: days a week, who set it, revision. |
| `practice_goal_weeks` | Celebration ledger: once per learner per week, with its notice. |
| `practice_nudges` | Nudge ledger: once per person per week, the learner it was about, its notice. |
| `mastery_topic_gaps` | Open gaps: learner, topic, audience, model version, opened at. |
| `mastery_gap_closures` | Closure ledger: once per learner, topic and term, with its notice. |

`down()` refuses while any of these rows are held, or any school has practice
run badges switched on.

## Endpoints

Both in the existing `learning/practice` group (`auth:sanctum`,
`school.context`, `module:daily_practice`, private no-store):

- `GET /api/v2/learning/practice/learners/{learner}/motivation`
  (`getPracticeMotivation`): `learner_id`, `can_set_goal`, `streak`
  (`current_days`, `best_days`, `rest_day_available`), `goal` (`days_per_week`
  or null, `practised_days_this_week`, `met`, `week_start`, `revision`) and
  `secure_topics` (up to 20 this school year: `topic_id`, `name`, `subject`,
  `secured_on`). No score, level or comparison.
- `POST /api/v2/learning/practice/learners/{learner}/practice-goal`
  (`setPracticeGoal`): `operation_id`, `revision`, `days_per_week` (1 to 7 or
  null). Learner only. Returns the same summary.

## Screens

- Panel: Academics, **Practice reminders** (`/practice-motivation`), for school
  managers: guardian reminders, learner reminders, school days before a
  reminder. Operation ID, revision check and audit
  (`practice.motivation_configured`).
- Panel: Awards, Persistence badges gains "Practice run badges".
- Apps: the existing practice screen already shows `streak_days`, which now
  follows the kind rule. A goal and summary card needs the regenerated Dart
  client (see "Not in this step").

## Notification types

| Type | Module | Portals | Channels | Notes |
| --- | --- | --- | --- | --- |
| `practice.goal_met` | daily_practice | learner | push on, email off, in-app on | notice-only; the learner's own |
| `practice.nudge` | daily_practice | learner, parent | push on, email off, in-app on | notice-only; its recipient's own |
| `mastery.gap_closed` | mastery | learner, parent | push on, email off, in-app on | notice-only; learner and current guardians |

All three are private notice scopes (`MotivationNotices`): the inbox shows them
only to their intended people while the learner is current and approved, the
relationship holds and the module is on, and delivery checks the same again.
Pushes use the existing quiet hours (none is a safety notice) and opt-outs.

## Acceptance tests

`tests/Feature/Learning/PracticeMotivationTest.php`

| Test | Shows |
| --- | --- |
| `test_a_run_counts_practice_days_kindly_with_one_rest_day_a_week` | Weekend practice counts; one missed school day a week is a rest day, a second ends the run; holidays and term breaks never break it; today never breaks it. |
| `test_learner_sets_a_weekly_goal_sees_progress_and_is_celebrated_once` | Happy path through the API; replay, stale revision 409, invalid 422; one celebration a week, never again after lowering the goal; the parent never sees the learner's note. |
| `test_guardians_read_but_never_set_and_other_people_cannot_read_a_streak_or_goal` | Guardian reads but cannot set; teacher (wrong role), another family's guardian and another learner (wrong owner) refused; School B context refused; withdrawn approval hides it. |
| `test_early_years_learners_get_no_goal_streak_or_nudge` | Stage rules: no summary, goal or nudge for an early years learner. |
| `test_a_family_visible_gap_that_becomes_secure_is_celebrated_once_a_term` | Unknown is not a gap; level 2 is still a gap; one notice to learner and both guardians; rebuilds and dips never notify twice; plain-words summary; teacher and another guardian never see it; a new term may celebrate again. |
| `test_staff_only_mastery_never_celebrates_and_the_minimum_evidence_holds_a_gap_open` | Unreleased marks never used; the evidence minimum; mastery map off. |
| `test_gap_closed_uses_rules_opt_outs_and_quiet_hours` | Rules, an opt-out, quiet hours hold pushes, withdrawn approval hides it. |
| `test_guardian_and_learner_nudges_follow_school_days_once_a_week_never_in_quiet_hours` | Off by default; weekends not counted; afternoons only; quiet hours hold and a later run sends; once a week; holidays; practising again stops nudges. |
| `test_nudge_opt_outs_and_learners_who_never_practised_are_left_alone` | Opt-outs; never-started learners. |
| `test_only_school_managers_configure_nudges_and_school_b_never_acts_on_school_a` | Wrong role 403 with nothing written; replay; 409; panel save, 422 and teacher 403; School B never reaches School A. |
| `test_streak_badges_start_off_and_are_presented_once_a_year_through_recognition` | Off by default; one milestone award through recognition with its family notice; never again that year. |
| `test_parent_digest_adds_a_reached_goal_and_a_run_but_never_a_missed_one` | Goal and run lines; a week without either leaves them out. |
| `test_rollback_refuses_while_motivation_records_are_retained` | Rollback refuses while rows are held. |

Every notice test also checks the text has no counts, scores, topic names or
comparative words, and every API payload is checked for ranking or comparison
fields.

## Not in this step

- **App screens** for the goal and summary: the API is in `openapi.yaml`, but
  the Dart client (`packages/eschool_api`) must be regenerated outside this
  environment before the apps can call it.
- **Teacher-set goals for younger learners.** Daily practice is not offered to
  early years (4.1), so no grade needs a teacher-set goal yet.
- **Tutor checkpoints as practice days.** A practice day is a completed daily
  practice set, as for the 4.6 practice badge.
- **Translations of the new digest template lines.** Only English starting text,
  as in 4.6; schools approve their own language versions.
- **Retention of the new ledgers.** Goal weeks, nudges and closures hold no
  private text (the notices are generic) and are kept like the 4.6 badge
  ledger; a retention rule for them belongs with the learner-record retention
  work.
