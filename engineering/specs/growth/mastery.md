# Mastery graph and evidence

Learners see up to three topics to work on and a short root-gap explanation;
teachers see current allocated learners' topic evidence. Records are learning
support, not a permanent profile label or promotion decision. The backend engine
and contracts land before shared app/panel views in a follow-on PR.

Use school/subject-scoped stable curriculum topic UUID anchors with published
source references. Explicit sourced prerequisite edges may cross grades but never
schools/subjects. Reject cycles, self-links, private draft references and bounded
size violations. A curriculum manager maintains the graph; ordinary teachers
read only their allocated subject/grade/year context. Do not invent a CAPS
progression from topic sort order. Imports retain source/version provenance.

Staff evidence uses canonical current published question definitions and actual
recorded question scores, excluding missing/absent/excused scores. Family evidence
uses frozen question scores from released canonical mark snapshots, never raw
captured/unreleased rows. Explicit school opt-in is also required for mark-derived
family mastery. Family reads use fresh own-child/guardian approval/departure checks.
Changing the setting or withdrawing approval suppresses reads immediately.

Background recomputation reacts to mark, release and published-definition events,
within the original school. It replaces derived evidence for the affected
subject/grade/year, handles retries and revisions without duplicate evidence,
and updates bounded recent-evidence aggregates. Missing evidence means unknown,
not level zero. Level 0–4 and confidence basis points have an explicit versioned
heuristic; confidence is evidence sufficiency, not a calibrated probability.
Prerequisite traversal returns at most three weak/unknown root topics with paths;
it never fabricates a score for an unassessed topic.

Practice reviews and tutor outcomes will add verified source adapters when those
features land; no public endpoint accepts a learner's self-reported mastery score.
The shared service for Phase 4 is `MasteryService::priorities(User,int learnerId,
int subjectId):array` with the same authorization as the view. Matching peers is
supervisor-mediated; peers cannot enumerate one another's mastery.

Test published/draft, role/school/owner, cyclic/cross-subject edges, revoked role,
canonical task versions, immutable release versus later raw edits, source removal,
replay, unknown evidence, exact integer thresholds, root paths and family opt-in/
approval/guardian withdrawal. Private no-store API contracts and generated SDK are
coordinated with the parallel Phase 4 stream; the backend alone does not claim app
or staging UX acceptance.

## Private v2 contract

The Growth API exposes staff year/group configuration, paginated allocated
rosters, staff learner views, own-family subject choices and views, published
subject graph reads, optimistic graph writes and queued scoped repair. All eight
operations recheck fresh school/module/role/owner authority, return private
no-store responses, and authorize before idempotent replay. Graph writes accept an
empty edge list, allowing a new graph to begin without fabricated prerequisites
and an existing graph to clear explicitly. A stale revision conflicts.

Family subject selection includes only the learner's current or released
subjects; evidence still requires release and school opt-in. Hidden mark-derived
evidence yields no artificial gap priorities. Nodes include human grade names
for client views. The optional `mastery` context capability is additive; the
module remains off until the school explicitly enables it. No mutation accepts
client-computed scores. Rebuild jobs coalesce pending identical school/group work.

`engineering/contracts/mastery.openapi.yaml` is the owned contract fragment. The
canonical complete contract and pinned generated Dart SDK include the parallel
Phase 4 activity contract. App and panel navigation/views follow separately;
these APIs do not claim completed learner-facing Phase 3 or live acceptance.

## Shared app and panel views

The learner/parent route resolves the signed-in learner or the selected own child
and binds school, reader identity, child and module capability before retrieval.
Staff selects an allocated teaching group, a paginated roster and learner. The
shared Material 3 screen shows up to three root topics and a separate evidence
view; phones have bottom navigation and larger screens a rail. Unknown evidence
is distinct from assessed level zero; school-hidden evidence never becomes an
invented zero count or no-gap claim. Account/school/child changes, backgrounding,
refusals and stale responses clear private state. No mastery cache is persisted.

The native panel adds scoped learner guidance and a curriculum-manager sourced
prerequisite editor. It explains that family evidence opt-in applies to the whole
school and still requires released marks/ownership/approval. Managers can refresh
published anchors, add/remove/clear sourced edges and compare a stale revision
with their escaped submitted text. Ordinary teachers cannot edit sources.
Historical panel contexts hide write controls. Client and panel verification is
recorded separately from human language, physical-device and live acceptance.
The derived mutex is school/subject-wide because a learner projection aggregates
evidence across grades and years. Rebuilds acquire it before any evidence
snapshot and hold it through replacement and aggregate writes. After-commit jobs
share this mutex with standalone verified-evidence aggregate refreshes; source
School/Subject rows are not locked. The initial, unmerged feature migration uses
one mutex row per school/subject. Former group members resolve released target
topics from both retained task and question definitions, so question-only tags
remain visible subject to the current published graph filter.

## Mastery model v2 (Action Plan 3.8)

`recent20-v1` adds up the marks of a learner's last 20 answers on a topic and
calls five answers full confidence. That over-claims: one right answer is level
4, a mark from last year counts as much as one from today, a 1-mark practice
answer beside a 10-mark question barely registers, and a hard question counts
the same as an easy one. Model v2, `weighted-decay-v2`, sits beside v1. Each
school chooses which one it reads.

### User stories

- As a curriculum manager, I choose my school's mastery model in the panel
  (Mastery map → Mastery model). Nothing changes until I opt in.
- As a teacher, learner or parent, I see which model produced the levels I am
  reading, so a v1 level and a v2 level are never confused.
- As a curriculum manager, I can switch back to v1 at any time and see v1
  levels at once, because v1 is always kept up to date.

### Rules

- **Version on every score.** Every `mastery_records` row has a
  `policy_version`, and a learner, topic and audience has one row per version.
  A v1 row is never recomputed by v2 or relabelled. Reads (`MasteryService::view`,
  which serves the panel, the API, the tutor context, peer matching and exam
  study plans; and `DailyPractice` candidate ordering) read only rows of the
  school's chosen model, and return that model in `policy_version`.
- **v1 is unchanged.** `MasteryScore` and its inputs (the newest 20 rows,
  units summed) are untouched. v1 rows are written for every school, always.
  v2 rows are written only while a school has chosen v2.
- **The switch.** `mastery_settings.model_version`, default `recent20-v1`, one
  row per school. Changing it needs `curriculum-publish` (the permission that
  already owns the school-wide mastery settings), goes through
  `MasteryPanelRequest` and `MasteryModels::change`, which checks the module
  and permission again, locks the school, compares the settings revision
  (a stale form is refused), bumps the revision, and records
  `growth.mastery_model_changed` in the audit log with the old and new model.
  Choosing v2 queues `RecomputeMastery` for every active group, the same set
  `mastery:rebuild` uses. Until a group's refresh has run, its v2 topics read as
  "not yet assessed", never as a borrowed v1 level. No HQ control: the choice
  is the school's, like the family-marks opt-in.
- **Difficulty respects audiences.** An item's success rate is computed from
  the evidence the audience may already see: family scores use only released
  marks and practice, never unreleased marks. A rate needs at least five
  different learners; below that the item has no difficulty weight.
- No API shape changes. `policy_version` was already a free-text string in
  `MasterySettings` and `MasteryView`; it now carries the school's model.
  `confidence_bp` keeps its meaning (evidence sufficiency, not a probability)
  but v2 grows it more slowly; the version label tells the reader which.

### Formulas

`MasteryScoreV2::calculate(rows, asOf, stabilities)` is pure and
deterministic: no database, no clock. For each of the newest `window` evidence
rows `i` (rank `i = 0` for the newest):

| Term | Formula | Meaning |
| --- | --- | --- |
| `p_i` | `achieved_units / maximum_units` | the answer's result |
| `t_i` | whole days from `observed_at` to `asOf` | its age |
| `S` | `(Σ own stabilities + n0 · S0) / (count + n0)` | the topic's memory stability: the learner's FSRS `stability_days` on the topic's practice items, blended with the default |
| `a_i` | `1 / (1 + t_i / (9 · S))` | freshness: the FSRS forgetting curve `FsrsScheduler` already uses. It enters only `E`, never `w_i` |
| `c_i` | source weight | marks 1, practice and tutor checkpoints 0.5 |
| `r_i` | `recency_ratio ^ i` | recency |
| `d_i` | `1 + strength · (1 − 2 · f_i)` | difficulty; `f_i` is the item's success rate, 1 when unknown |
| `w_i` | `c_i · r_i · d_i` | the row's weight: source, recency and difficulty |

Then `raw = Σ w_i p_i / Σ w_i`, and the fresh evidence `E = Σ c_i a_i`.

- `score = raw` when `raw ≤ m`; otherwise `score = m + (raw − m) · E / (E + ks)`.
  Above the midpoint `m`, a score keeps only the share of its lead that the
  evidence supports. As evidence ages, `E` falls and a strong score fades
  towards the midpoint.
- **Time never raises a score.** Time enters only through `E`. `raw` does not
  depend on the scoring date, and `E` only shrinks as the date moves later.
  For the same evidence, a later score is never higher, and a score at or
  below the midpoint does not change with time. Freshness is deliberately
  kept out of `w_i`. As all answers age, the ratio between an old and a new
  answer's freshness tends to 1. So an old success would gain weight against
  a recent failure, and a weak score would rise with no new work. The Codex
  review of #420 found exactly that: a wrong mark from 2026-06-01 and a
  correct mark from 2023-01-01 scored 2230 (level 0) on 2026-06-01 and 2855
  (level 1) a year later. Now both dates give 4872 (level 1), and only
  confidence falls (979 to 660).
- `confidence = E / (E + kc)`.
- Levels use v1's thresholds on `score_bp`: 85%, 70%, 50% and 25%.
- No rows: level and `score_bp` are null and confidence is 0. Unknown is never zero.
- Results are rounded half up to integer basis points.

The stored v2 score is as at `computed_at`. Any new mark, release, practice
answer, tutor checkpoint, group refresh or model switch recomputes all of that
learner's topics in the subject, so decay is applied then.

### Parameters (`config/mastery.php`) and why

| Parameter | Default | Rationale |
| --- | --- | --- |
| `window` | 40 | Twice v1's 20. The recency weight already makes older rows count less, so a longer window adds stability without letting old work dominate. |
| `recency_ratio` | 0.95 | The 14th-newest answer still counts half. A term's work stays in view; the latest answers lead. Recency is by rank, not by age, so the balance between answers never shifts as time passes. |
| `source_weights` | marks 1.0, practice 0.5, tutor 0.5 | Marks are teacher-set and teacher-marked. Practice is self-study with the answer available offline, and tutor checkpoints are first attempts after help. Both are real evidence but weaker, so two practice answers carry one mark's weight. v1 summed units, so a 1-unit practice answer beside a 1000-unit question barely registered. |
| `default_source_weight` | 0.5 | Any future source (for example 3.7 diagnostics) counts as practice until configured. |
| `difficulty_strength` | 0.5 | Item weights between 0.5 (everyone gets it right) and 1.5 (nobody does). Harder items say more, but no single item can dominate. |
| `difficulty_min_responses` | 5 | Fewer different learners give a noisy rate, and the rate would reflect one or two named classmates. |
| `default_stability_days` | 60 | Without practice memory, half of a fresh mark's freshness is gone after 540 days (`t = 9S`) and a tenth after 60 days. Freshness sets how fast a strong score fades and confidence falls, so a term-old mark still counts. |
| `stability_prior_count` | 3 | A learner's first practice answers (FSRS stability 0.4 to 5.8 days) inform the decay without making last month's marks vanish. |
| `midpoint_bp` | 5000 | The "secure" boundary of level 2. A lead above it must be earned with evidence. |
| `score_evidence_half` | 2 | One fresh correct mark gives 67% (level 2), three give 80% (level 3), six give 88% (level 4). |
| `confidence_evidence_half` | 12 | Sufficiency grows slowly: 1 fresh mark gives 8%, 5 give 29%, 12 give 50% and 40 give 77%. v1 said 100% after five. |

These defaults are reasoned, not fitted. P.4 (the Grade 8 maths study) should
calibrate them; a change of formula, rather than of tuning, is a new version.

### Data

- `mastery_settings.model_version` (string, default `recent20-v1`).
- `mastery_records` identity is now school, learner, topic, audience and
  `policy_version` (`mastery_record_version_identity`).
- `mastery_evidence.item_key` (nullable): `question:<canonical question id>` or
  `practice_item:<id>`, written by every evidence writer and backfilled by the
  migration. It is indexed with school and subject for item success rates.
- The migration's `down()` refuses while any v2 record or v2 choice is kept.

### Acceptance tests

| Test | Covers |
| --- | --- |
| `MasteryScoreV2Test::test_v2_empty_evidence_gives_no_score_not_zero` | No evidence is unknown; a real zero stays zero |
| `MasteryScoreV2Test::test_v2_golden_values_for_the_fixture_learner` | Exact v2 and v1 numbers for a fixed five-row learner, now and a year later |
| `MasteryScoreV2Test::test_v2_decays_over_time_and_slower_with_more_stable_practice_memory` | Decay over time, stability slows it, weak scores never rise |
| `MasteryScoreV2Test::test_v2_an_old_success_never_lifts_a_recent_failure_as_time_passes` | The Codex review case: the same score on both dates, lower confidence later |
| `MasteryScoreV2Test::test_v2_score_never_rises_with_time_for_the_same_evidence` | Property test: 400 generated evidence sets, each scored on five increasingly later dates; score, level and confidence never rise, and scores at or below the midpoint never change |
| `MasteryScoreV2Test::test_v2_harder_items_weigh_more` | Difficulty weighting and the five-learner minimum |
| `MasteryScoreV2Test::test_v2_sufficiency_rises_slowly_with_evidence_count` | Slow confidence growth, the score lead and the window |
| `MasteryScoreV2Test::test_v2_practice_counts_half_of_marks` | Practice against marks, configurable |
| `MasteryScoreV2Test::test_v2_is_deterministic_and_rejects_invalid_evidence_and_parameters` | Determinism and validation |
| `MasteryScoreV2Test::test_recent20_v1_output_is_byte_for_byte_the_recorded_snapshot` | 55 v1 cases recorded from main before this step, compared as JSON |
| `MasteryModelV2Test::test_default_school_keeps_recent20_v1_records_and_views_exactly_as_before` | Default v1; records equal those recorded before this step |
| `MasteryModelV2Test::test_v2_school_golden_values_for_the_fixture_learner_beside_unchanged_v1` | Exact v1 and v2 records for the fixture learner (marks, release, practice), the labelled views, and decay after 30 days |
| `MasteryModelV2Test::test_switching_back_to_v1_is_immediate_and_stored_v2_rows_keep_their_label` | Switching back; v2 rows are not rewritten |
| `MasteryModelV2Test::test_curriculum_publisher_switches_the_model_in_the_panel_with_audit_and_queued_rebuild` | Happy path, audit, queued rebuild, stale revision, invalid model, label in the panel |
| `MasteryModelV2Test::test_wrong_role_cannot_switch_the_model_and_nothing_is_written` | Teacher 403, guardian signed out, service refuses teacher, learner and guardian; counts unchanged |
| `MasteryModelV2Test::test_school_b_model_choice_does_not_change_school_a` | School B's v2 choice leaves School A on v1; School A's switch leaves School B's row unchanged |
| `MasteryModelV2Test::test_item_keys_are_backfilled_and_rollback_refuses_retained_v2_choice` | Backfill and refused rollback |
| `MasteryEngineTest::test_empty_projection_migration_rolls_back_and_reapplies` | The new migration rolls back and reapplies when empty |

### Not in this step

- **App label.** The apps already receive the model in `policy_version`, but
  their screens do not show a human label for it. Showing one needs the
  Flutter screens and is not built here; the Dart client and API are unchanged.
- **Scheduled decay refresh.** A v2 score is recomputed when the learner's
  evidence changes, not on a timer. A learner with no new evidence keeps the
  score from their last recompute until a group refresh. A nightly refresh for
  v2 schools is a follow-on.
- **Leadership reports.** No leadership report reads mastery today. 3.14
  (measurement) must group by `policy_version` and never compare across versions.
- **Calibration.** The defaults are not fitted to outcomes yet (see P.4).
- **Age gaps in the weights.** Recency in `raw` is by rank only. Weighting
  answers by the gap in days to the newest answer (with exponential decay,
  whose ratios don't drift with time) would keep the no-rise rule, but it is
  a formula change and so a new version.
- **An API to switch the model.** The switch is panel-only.
