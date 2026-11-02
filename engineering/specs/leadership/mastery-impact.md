# Mastery growth snapshots and the impact report (Action Plan 3.14)

Mastery is useful to a school only if it can see whether learning moved during a
term. This step keeps the mastery of each learner on each topic at the start of
every term and at its end (or the latest so far), and shows school leaders the
result as counts per topic and grade. It is the measurement behind the planned
learning-outcome study at a partner school (Grade 8 maths, one term).

## User stories

- As a principal or academic manager, I see, for a term, how many learners on
  each topic rose a mastery level since the term started, held it, went lower,
  were measured for the first time, or have no evidence yet, per grade and for
  the whole school.
- As a grade head, I see the same for the grades I currently lead, and nothing
  of other grades.
- As a learner or parent, nothing about me is ranked or named in any leadership
  report.
- As the operator, I can fill in the current term for a school straight away,
  and the nightly job keeps it current without any one-off steps.

## Data: `mastery_growth_snapshots`

School-owned (`school_id` not null, indexed, foreign key; model
`App\Models\Growth\MasteryGrowthSnapshot` uses `BelongsToSchool`).

One row per school, term, learner, topic, audience (`staff`, `family`,
`practice`, as on `mastery_records`) and mastery model version
(`mastery_records.policy_version`); unique key `mastery_growth_identity`.

| Column | Meaning |
| --- | --- |
| `term_id`, `session_year_id` | The school term (and its year) the row measures. |
| `subject_id`, `topic_id`, `grade_level_id` | The mastery topic, its subject and the grade the topic is taught in. |
| `learner_id`, `audience`, `policy_version` | Whose mastery, which view, which model. |
| `start_basis` | `replayed` (rebuilt from evidence dated before the term), `captured` (read on the term's first day) or `unavailable` (not known). |
| `start_level`, `start_score_bp`, `start_confidence_bp`, `start_evidence_count` | Mastery at the start of the term. Null level = no evidence. All null when the start is unavailable. |
| `end_level`, `end_score_bp`, `end_confidence_bp`, `end_evidence_count` | Mastery at the end of the term, or the latest so far. |
| `end_as_of`, `end_final` | The school date the end describes (today, or the term's last day) and whether the term is over. |
| `captured_at` | When the row was first written. |

## Rules

1. **Missing evidence is unknown, never zero.** A level or score with no evidence
   behind it is stored as null. A start that was not recorded is `unavailable`,
   never zero.
2. **Within one model version.** Each row holds one version, and start and end
   are both from that version. The report shows one version at a time
   (recent20-v1 by default, which is always computed; another version when the
   school has rows only for that one, or when chosen).
3. **Start once, end until the term is over.** The start is written when the row
   is created and never changes. The end is refreshed on every run.
4. **recent20-v1 is replayed.** It is a pure function of the evidence (the
   latest 20 items for the audience, as `MasteryRecompute` aggregates), so the
   start is computed from evidence observed before the term's first day, and the
   end from evidence observed up to the term's last day (or now). A backfill in
   the middle of a term therefore still gets a real start.
5. **Other versions are read, not replayed.** A version that depends on more than
   the evidence (such as model v2's time decay) is read from its stored record.
   Its start is `captured` only when the row is first written on the term's first
   day; otherwise it is `unavailable`. After the term ends its end no longer
   changes (`end_final` is set).
6. **Which term.** For each topic grade, the term that covers that grade
   (`grade_from`/`grade_to`) and today. Terms that ended in the last 14 days are
   still processed so their end settles on the term's last day. Two terms for the
   same grade that overlap (the term calendar refuses this; only imported data
   can have it) are skipped as ambiguous.
7. **Idempotent and scheduled.** `mastery:growth-snapshots` runs nightly at 04:20
   (UTC scheduler) and queues `CaptureMasteryGrowth` for every active school with
   the mastery module on. Running it again writes the same rows. The command with
   a school and `--backfill` captures that school's current term in the same
   process and prints counts only.
8. **Never across schools.** Capture runs inside one school's context, and every
   query is school-scoped. The report reads only the current school's rows.
9. **Who may see the report.** Staff in the school's own context (not across
   schools, not impersonating, not a platform identity, password set), with the
   leadership dashboard and mastery modules on:
   - a current principal appointment, or the `academic-structure-manage`
     permission (academic managers and administrators): the whole school;
   - a current grade-head appointment: those grades only;
   - anyone else: refused (403). A disabled module: not found (404).
   Authority is checked again after the counts are made.
10. **Counts only.** The report returns and shows counts per topic and grade and
    grade totals. It never returns a learner id, name or per-learner row, and has
    no list of learners who went lower.
11. **"No evidence yet"** counts learners with a snapshot but no evidence now,
    plus learners in the subject's teaching groups for that grade during the term
    who have no snapshot for the topic.

## Panel

`GET /leadership-dashboard/mastery-impact` (`mastery-impact.index`), in the menu
as "Mastery impact" (Grades and subjects), linked from the leadership dashboard
for those who may see it. A term picker (terms that have started, newest first;
the current term by default) and a version picker when the term has more than one
version. One table per grade: subject, topic, learners with evidence at start and
now, rose a level, held, lower level, first evidence this term, start unknown, no
evidence yet; a footer row of learner-topic totals for the grade.

## Acceptance tests

`admin-panel/tests/Feature/Leadership/MasteryImpactTest.php`

| Test | Checks |
| --- | --- |
| `test_capture_replays_term_start_and_latest_once_per_learner_topic_term_audience_and_version` | One row per learner/topic/term/audience/version; replayed start and latest end; no evidence stays null; a second run changes nothing; later evidence never moves a recorded start. |
| `test_term_end_settles_within_the_closing_window_and_is_then_left_alone` | After the term the end describes the term's last day and is final; after the closing window nothing changes. |
| `test_another_model_version_keeps_its_own_rows_and_an_unrecorded_start_stays_unknown` | A non-replayable version: start captured on the first day or unavailable; end frozen after the term; the report never mixes versions. |
| `test_principal_reads_whole_school_counts_without_any_learner_detail` | Principal sees rose/held/first evidence/no evidence yet per topic and grade totals; no learner names; dashboard links the report. |
| `test_grade_head_sees_only_their_grade_and_ordinary_staff_parents_and_operators_are_refused` | Grade head sees only their grade (and not the principal dashboard); ordinary teacher, expired appointment, parent, pending password and platform identity refused; module off is 404; nothing written. |
| `test_school_b_terms_and_rows_never_cross_into_school_a_and_capture_stays_in_its_school` | School B's term id is 404 from School A; School B rows never counted; capture in either school leaves the other's rows unchanged; no access in the other school. |
| `test_nightly_schedule_queues_one_capture_per_enabled_school_and_the_job_writes_rows` | Nightly schedule entry; one job per school with mastery on; invalid school refused; the job writes the rows. |

## Not in this step

- The learning-outcome study itself (partner school, Grade 8 maths, one term):
  this step provides its measurement; the study design and consent are separate.
- Any app or `/api/v2` view of the report (no API or OpenAPI change here).
- Replaying model v2 at a past date (needs v2's own as-of replay; until then v2
  rows rely on the first-day capture).
- Small-group suppression (hiding counts below a threshold); counts are shown to
  staff who can already see these learners' mastery.
- Learner grade from enrolment: rows use the topic's grade.
