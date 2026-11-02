# Growth-first weekly digest and persistence badges (Action Plan 4.6)

The Monday weekly job (`digest:weekly`, `Learning/Digests`) now leads with what
a child grew in. It adds a help-at-home block from the published curriculum, and
it presents persistence badges through the existing awards module. It never
lists rankings, comparisons with other children, or topics a child "fell in".

## User stories

- As a **parent**, my Monday learning update tells me which topics my child grew
  in last week, and which topics the school is starting next week, so I can talk
  about them at home.
- As a **learner**, I get a short in-app note: "This week you grew in Fractions.
  Keep going!" It goes only to my own inbox.
- As a **learner**, I earn a badge for practising on several days in a week, or
  for coming back to a topic I found hard and improving. The badge appears with
  my permanent awards, and my family is told about it the way it is told about
  other awards.
- As an **awards manager** (an Admin who manages the school, or the principal),
  I switch each badge on or off for my school and set its threshold. Both badges
  start off.

## Rules

### "This week you grew in…"

- **Week.** The school-local week from Monday to Sunday, the same week the
  parent digest covers.
- **Rises only.** A topic grew when its mastery level rose, or its score rose by
  at least 5 percentage points (`weekly_digests.growth.min_score_rise_bp`, 500
  basis points). Falls and unchanged topics are never listed. There is no "fell
  in" placeholder.
- **Unknown is not zero.** A topic with no evidence before the week is not
  "growth" when it gets its first evidence.
- **Like with like.** The mastery model is versioned (`policy_version` on
  `mastery_records`). Both ends of the week are scored with the model version
  the learner's current record uses (today `recent20-v1`). A topic whose record
  is on any other version is left out rather than compared across versions. The
  version used is stored with the digest.
- **Family visibility.** The block uses only evidence the family may already see
  on the mastery map. Released-mark evidence (`family`) is used only when the
  school has opted in to family mastery from marks. Otherwise only practice and
  tutor checkpoint evidence (`practice`) is used. Staff-only evidence
  (unreleased marks) is never used. The mastery map module must be on.
- **At most five topics** are named in the parent digest (`max_topics`), and
  three in the learner's note. The lists are sorted by subject and topic name,
  never by size of improvement.

### Help at home

- Topics come from **published curriculum plans** (the published assessment
  plan for a term, as the mark book uses) for each teaching group the learner
  is a current member of on the coming Monday.
- A topic is included when its planned teaching weeks (`week_from`, `week_to`,
  counted from the start of the term) overlap the coming week. Topics without
  planned weeks are not guessed.
- At most five, written as "Subject: Topic".

### Parent digest

- Two new template placeholders, `{grew_in}` and `{help_at_home}`. A template
  line that uses one of them is left out when it has nothing to show, so an
  update never has an empty or negative growth line.
- The English starting text gains two lines that use them. Language approval is
  unchanged: a school sees the new lines only once a manager approves a template
  version that contains them. Approved versions without the placeholders keep
  working exactly as before.
- The growth topics behind the update are kept, encrypted, in
  `weekly_parent_digests.growth` and erased with the update after 26 weeks.
- Who gets an update is unchanged: the existing digest checks (active learner,
  current enrolment, guardian approval, the actual guardian relationship, the
  digest module) decide it. The API response is unchanged; the growth lines are
  part of the summary text.

### Learner note

- Type `growth.weekly` (digest module, learner portal). **In-app only**: push
  and email are off, so topic names never appear on a lock screen or in a
  mailbox. Title "Your week of growth"; text "This week you grew in …. Keep
  going!".
- Sent once per learner per week (`learner_growth_weeks`), only to the learner's
  own account, and only while the learner is current and guardian-approved.
- The inbox shows it only to that learner, and only while the learner is still
  current and approved, the note has not expired, and the digest and mastery
  map modules are on. A parent, other learner or staff member never sees it.

### Persistence badges

- **Practice badge.** Practising (answering daily practice) on at least N
  different days in the week. N is 4 by default; a school can set 2 to 7. Needs
  the daily practice module.
- **Comeback badge.** A topic that grew this week (see above) after an earlier
  score below a set percentage (50% by default; 10% to 90%). At most three a
  week. It uses the same family-visible evidence as the growth block.
- Presented as permanent awards with source `badge`, dated the Sunday that ends
  the week, through the existing award notices (`recognition.award_presented`),
  audit and outbox events. Families see them in the existing award profile.
- **Idempotent.** A ledger (`recognition_badges`, unique per school, learner,
  kind, week and topic) is written before the award. Re-running the job, or two
  runs at once, never awards twice. If a manager withdraws a badge, it is not
  awarded again for that week.
- **Configured per school** in the awards settings (`/recognition/awards`,
  "Persistence badges"), the same way other recognition settings are: managers
  only, with an operation ID, a revision check and an audit record. Both badges
  start off. The manager who last switched badges on is recorded as the
  presenter.
- The awards module must be on (badges run even when the weekly digest module is
  off), and the learner must be current and guardian-approved.

### Tenancy

Every new table is school-owned. Growth, notes, settings and badges are read and
written only inside the learner's own school.

## Data

| Table / column | Purpose |
| --- | --- |
| `weekly_parent_digests.growth` | Encrypted: grew-in topics, help-at-home topics, model version and audience. |
| `learner_growth_weeks` | One learner note per learner per week: encrypted topics, model version, notification, expiry. |
| `recognition_badge_settings` | One row per school: each badge on/off, thresholds, who switched them on, revision. |
| `recognition_badges` | Idempotency ledger: learner, kind, week, topic, dedupe key, the award presented. |

## Acceptance tests

`tests/Feature/Learning/GrowthDigestTest.php`

| Test | Shows |
| --- | --- |
| `test_grew_in_lists_only_rises_from_evidence_the_family_may_see` | Rises only; falls left out; staff-only evidence never used; released marks only after the school opts in. |
| `test_growth_compares_like_with_like_within_one_model_version` | A topic on another model version is left out. |
| `test_first_evidence_is_unknown_not_growth` | First evidence is not growth. |
| `test_parent_digest_shows_growth_and_help_at_home_without_changing_the_api_shape` | Grew-in and help-at-home lines in the parent summary; other weeks' topics left out; API shape unchanged. |
| `test_a_week_without_growth_leaves_the_growth_lines_out` | No empty growth line. |
| `test_learner_gets_own_growth_once_in_app_only_and_parent_cannot_read_it` | Learner note once a week, in-app only; wrong owner (parent) cannot see it. |
| `test_withdrawn_guardian_approval_stops_growth_digest_learner_note_and_badges` | No digest, note or badge without guardian approval; nothing written. |
| `test_withdrawn_approval_hides_an_already_delivered_learner_note` | A delivered note disappears when approval is withdrawn or the mastery map is switched off. |
| `test_persistence_badges_are_awarded_once_through_recognition_and_never_again_after_a_correction` | Badges start off; awarded once as permanent awards with family notices; re-runs and withdrawals never award again. |
| `test_badges_follow_the_school_switch_threshold_and_awards_module` | The school threshold and the awards module are respected. |
| `test_only_award_managers_configure_badges_and_retries_replay` | Wrong role (teacher, parent, learner): 403, nothing written; exact retry replays; stale revision 409. |
| `test_panel_manager_saves_badges_and_teacher_or_other_school_cannot` | Panel form; other school's ID 403; invalid threshold 422; house points 404; teacher 403; nothing written on refusal. |
| `test_school_b_never_sees_school_a_growth_settings_or_badges` | School B sees none of School A's growth, notes, settings or badges, and School A never acts on School B's learner. |
| `test_rollback_refuses_while_badges_or_growth_receipts_are_retained` | Rollback refuses while rows are held. |
| `test_retention_erases_expired_learner_growth_notes` | Expired learner notes are erased by the daily retention run. |
| `test_starting_template_lines_render_only_when_there_is_something_to_show` | Optional template lines. |

## Not in this step

- **App screens.** No new learner or parent screen. The learner note uses the
  existing in-app inbox, and the parent sees growth in the existing weekly
  update text. A dedicated "my growth this week" card in the apps needs an API
  change (and a regenerated Dart client), so it is left for a later step.
- **API.** No `/api/v2` or `openapi.yaml` change. The digest detail response
  keeps its shape; the structured `growth` data is not exposed by the API yet.
- **Translations of the new template lines.** Only the English starting text
  has them. Each school's managers write and approve their own language
  versions, as before.
- **Practice streaks across weeks, goals and the `mastery.gap_closed`
  notification** (Action Plan 3.13).
- **Mastery model v2 (3.8).** When a new model version lands, it needs an
  "as of a date" score so its topics can be compared like with like. Until then
  its topics are left out of the growth block rather than compared across
  versions.
- **Tutor checkpoints for the practice badge.** Only daily-practice answers count
  as practice days for now.
