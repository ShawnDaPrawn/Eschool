# Diagnostic start (the starting check)

Action Plan 3.7. Owner role: Learning. Depends on the platform content library
(3.3, `engineering/specs/learning/content-library.md`) and the mastery evidence
model (`engineering/specs/growth/mastery.md`).

A learner who has done no practice and has no marks yet has an empty mastery
map, so daily practice and the tutor cannot tell where to begin. The library
already holds *diagnostics*: a published list of practice questions of one
framework and subject. This step lets a learner take the library diagnostic for
their group once a school year, marks it on the server, and writes each answer
as mastery evidence of its own kind, so every learner starts with a filled map.

## User stories

- As a learner, my app offers a short starting check for a subject. I answer
  one question at a time. I am not told a mark or whether I was right: when I
  finish, I see where my practice will begin.
- As a guardian, I see whether my child has done the starting check and a
  plain starting-point message, never a score or anything to compare.
- As a teacher, I open the Starting checks page for one of my groups and see who
  has finished, and for each topic how many learners start where. Learners are
  listed by name, never ranked, with no total per learner.
- As a school, the check's answers count on the mastery map as their own kind of
  evidence and never as, or instead of, an assessment mark.

## Design choices

- **Who starts it: the learner.** Daily practice is learner-started (the learner
  asks for today's set; the teacher shapes the bank). The starting check follows
  the same flow: the learner starts it from the app, once per diagnostic and
  school year, for a group they are in today. It needs no teacher action and no
  new assignment table. The teacher sees progress on the Starting checks page.
  Teacher assignment (choosing when a class takes it) is left for later.
- **Which diagnostic.** The group's frameworks and subject codes come from its
  published curriculum, exactly as library practice reads them
  (`LibraryPractice::scopes`). The diagnostic is the earliest published library
  item of kind `diagnostic` whose topic has that framework, subject code and the
  group's grade, so every learner of a group takes the same check.
- **Short, fixed, not exhaustive.** The questions are the diagnostic's published
  practice questions (any grade of the same framework and subject, so an
  earlier grade's prerequisite can be checked). Topics are asked in prerequisite
  order (depths over the framework and subject's whole library graph, so a chain
  through a topic the check has no question for still counts), then in the order the diagnostic lists them. At most
  three questions per topic (two when the check has 7 to 10 topics, one when it
  has more), and at most 20 questions in all. **Stop rule:** a topic stops as soon
  as two of its answers agree (both right or both not yet); its remaining
  questions are skipped. Not adaptive across topics in this step.
- **The answer loophole.** The device receives only the next question's kind,
  prompt and options. It never receives accepted answers or explanations, for
  answered or unanswered questions, and the learner is not told right or wrong
  (a check, not practice, and answers could otherwise be passed to classmates).
  Marking uses `PracticeAnswers` on the server. Answers are stored encrypted, as
  daily practice answers are.
- **Question types.** The check plans only choice and number questions, which
  its answer contract can mark automatically. Written, spoken, diagram and
  per-part practice questions are omitted before ordering and applying limits.
- **Evidence.** One `mastery_evidence` row per answered question:
  `source_type = diagnostic`, `audience = practice`, `content_source = library`,
  `library_item_version_id`, `item_key = library_item:<id>` (shared with library
  practice of the same question, so facility is per question across both), on
  the school's mirror node of the question's library topic, `source_key =
  diagnostic:<attempt item id>`.
  - *Audience.* `practice`, like other self-study evidence. The aggregate already
    reads practice evidence in all three projections (staff, family and
    practice), so the map fills for each. It never enters the `staff` or
    `family` audience on its own, where only assessment marks live.
  - *Weight.* recent20-v1: one unit like a practice answer; marks and practice
    that follow soon push it out of the newest 20. weighted-decay-v2:
    `source_weights.diagnostic = 0.5`, as practice (it is server-marked, but a
    short one-off check); recency weighting means newer evidence outweighs it.
  - *Never a mark.* The source key namespace `diagnostic:` never collides with
    `mark:` or `release:` keys, and `MasteryRecompute::group` deletes and
    rebuilds only `question_mark` and `released_question_mark` rows, so a
    rebuild from marks leaves diagnostic evidence alone and a diagnostic never
    changes mark evidence. No `question_marks` row is written.
  - *Idempotent.* One attempt per learner, diagnostic and school year (unique);
    starting again resumes it, including a finished one. One evidence row per
    planned question (`updateOrCreate` on the source key). Sending the same
    answer again changes nothing; a different answer to an answered question,
    a skipped question, or an answer out of order is refused (409).
- **Teacher's starting map.** Per topic, from the check's own answers: *Secure*
  (all answers right), *Partly there* (some), *Start here* (none), *Not reached
  yet* (no answer yet). The page counts learners per band and topic, and lists
  learners by name with their bands. The mastery map itself keeps its own
  levels from all evidence.
- **Family and learner view.** A status (not started, started, finished), a
  plain message, and once finished up to three topic names where practice will
  begin (topics that came out Partly there or Start here, in the order asked).
  No level, score or band is sent to a learner or a guardian.

## Rules

- **Switches.** The school's library switch (`library_school_settings`), the
  `daily_practice` module (and its stages) and the `mastery` module (writable for
  a start or an answer). With the library switched off nothing is offered,
  starting is 404, and an existing attempt can be neither read nor answered (404)
  until it is switched on again, as for library practice. The teacher page says
  no check is available.
- **Stages (4.1).** `daily_practice` is for junior and high school learners.
  Early-years learners do not sign in; the service also refuses them (403) and
  the summary lists nothing for an early-years learner, to a guardian either.
- **Who.** Start, read and answer: the learner themself, approved by a guardian,
  in the group today (`PracticeAccess::learner`). Summary: the learner or a
  guardian (`MasteryPolicy::family`). Teacher page and API: a teacher allocated
  to the group today (`PracticeAccess::staff`). Everyone else is refused.
- **Schools.** Attempts and their questions are school rows (`BelongsToSchool`,
  listed in `SchoolOwnedTables::REQUIRED`). Another school's attempt, group or
  learner is not found. Library rows are only read; the only rows written are
  the school's attempts, their questions, mirror nodes (`mastery_topics`) and
  mastery evidence and records. Nothing about a learner flows to the library.
- **Mirror nodes.** `TopicGraph::mirror` makes the school's mirror node of a
  library topic. A diagnostic may reach an earlier grade's topic, which
  `TopicGraph::overlay` (grades of the published curriculum only) does not
  mirror. Mirror nodes stay out of the school's own graph, as before.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `diagnostic_attempts` | required school-owned | learner, group, subject, grade, school year, library diagnostic item and version, who started it, when finished; unique per school, learner, diagnostic and year |
| `diagnostic_attempt_items` | required school-owned | attempt, position, library question and version, library topic, the school's mirror topic, state (`waiting`, `answered`, `skipped`), encrypted answer, right or not, when answered |

`config/mastery.php`: `v2.source_weights.diagnostic = 0.5`.

## Endpoints

API v2, `PrivateMastery` (no-store), `auth:sanctum`, `school.context`,
`role.any`, `module:daily_practice`, `module:mastery`. Writes accept body
fields only. Described in `openapi.yaml` (tag `Diagnostics`); the Dart client
(`packages/eschool_api`) must be regenerated with `tool/generate.sh`.

| Method | Path | Who | Returns |
| --- | --- | --- | --- |
| GET | `/api/v2/learning/diagnostics/learners/{learner}` | learner, guardian | `DiagnosticSummary` |
| POST | `/api/v2/learning/diagnostics/learners/{learner}/groups/{group}/attempts` | learner | `DiagnosticAttempt` (started or resumed) |
| GET | `/api/v2/learning/diagnostics/learners/{learner}/attempts/{attempt}` | learner | `DiagnosticAttempt` |
| POST | `/api/v2/learning/diagnostics/learners/{learner}/attempts/{attempt}/items/{item}/answer` | learner | `DiagnosticAttempt` (body `answer`) |
| GET | `/api/v2/learning/diagnostics/groups/{group}` | the group's teacher | `DiagnosticGroup` |

Panel: `GET /starting-checks?group=` (`starting-checks.index`, modules
`daily_practice` and `mastery`, permission `curriculum-read` or
`academic-structure-manage`, then the group's teacher). Navigation: Grades and
subjects, "Starting checks".

## Acceptance tests

`Learning/DiagnosticStartTest` (School A teaches CAPS `MATH-SP` Grade 7; the
library has Whole numbers (Grade 6) ← Fractions ← Decimals with three, three
and two published questions and a Grade 7 diagnostic listing all eight, Decimals
first):

| Given | When | Then |
| --- | --- | --- |
| The switch on | Asha starts, answers right, right; right, wrong, wrong; wrong, wrong | Whole numbers first; its third question skipped; Fractions' third asked; 7 diagnostic evidence rows (practice audience, library, item keys, versions) on mirror nodes, the Grade 6 topic mirrored too; records in staff, family and practice; teacher sees bands Secure, Partly there, Start here and learners by name (Asha finished, Ben not started); Asha and her father see "Fractions, Decimals" and no score; library tables unchanged (`test_a_learner_takes_the_starting_check_and_fills_the_mastery_map`) |
| A ← B ← C, the check lists C first and has no B question | start | A is asked before C (`test_a_prerequisite_chain_through_a_topic_with_no_question_still_orders_the_check`) |
| A diagnostic lists a short written answer before a number question | start and answer | the number question is offered, the written question is omitted, and the check completes (`test_starting_check_skips_question_kinds_its_answer_contract_cannot_mark`) |
| An attempt | read and answer | no answers, explanations or right/wrong in any payload; the stored answer is encrypted (`test_the_device_never_receives_answers_or_explanations_and_is_not_told_right_or_wrong`) |
| An answered question | send it again, a different answer, out of order, malformed; start again after finishing; a skipped question | unchanged; 409; 409; 422; same attempt, nothing new; 409 (`test_submissions_are_idempotent_and_a_retake_never_double_counts`) |
| Recorded marks | take the check; rebuild from marks | mark evidence and `question_marks` unchanged; diagnostic evidence kept, practice audience only, on its own topics (`test_diagnostic_evidence_never_overwrites_or_masquerades_as_assessment_marks`) |
| Asha's attempt | the teacher, a guardian, Ben start, read or answer it; Ben or another guardian reads Asha's summary; a learner, a guardian, another teacher open the teacher view | 404; 404; 403 (`test_wrong_roles_and_other_learners_are_refused`) |
| School B on, School A off; then A on and finished | start; inside School B read, start, summary, teacher view | 404; nothing in School B, 404 or 403, nothing mirrored into School B (`test_school_b_cannot_see_assign_or_answer_school_a_diagnostics_or_evidence`) |
| The switch off; then on, started, off | start, summary, teacher view; read, answer | 404, empty, unavailable; 404, no evidence (`test_switching_the_library_off_withdraws_the_check`) |
| Mastery module off; Grade 7 early years | start, summary, teacher view | 403; 403 and an empty summary, nothing written (`test_the_mastery_module_and_early_years_stage_are_respected`) |
| The API | each endpoint as learner, guardian, teacher | matches `openapi.yaml`, no-store, query on a write 422, replay 200, conflict 409 (`test_the_api_and_panel_page_follow_the_contract`) |
| The panel page | the teacher, another teacher, a guardian | the map without questions or explanations; 403; nothing shown (`test_the_teacher_page_shows_the_starting_map_only_to_the_groups_teacher`) |

`Contracts/OpenApiContractTest` has a 401 scenario for each new route.

## Not in this step

- **Apps.** No Flutter screen yet; the Dart client needs regenerating from
  `openapi.yaml`.
- **Teacher assignment and retakes.** A teacher cannot assign, schedule or reset
  a check; one attempt per learner, diagnostic and school year.
- **Adaptive selection** across topics (skipping later topics after a
  prerequisite gap) and time limits.
- **Standalone users** (no school): the plan's "every standalone user later".
- **Mastery map display of library topics.** Mirror nodes are still not shown on
  the school's mastery map (as in 3.3); the starting map is on the Starting
  checks page. Linking library topics to the school's own curriculum topics is
  separate work.
- **Quality signals to the library** (weak diagnostic questions): nothing about
  learners flows to the library.
