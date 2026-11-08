# Marking accuracy gate

Action Plan 3.18. Owner role: Backend (learning) with Content. Builds on the [marking engine](marking-engine.md) (3.16) and [structured memos](../ai/structured-memos.md) (3.15).

Learners receive automatic marking in a subject only once the marking engine agrees with teachers on enough de-identified, teacher-marked scripts, and an HQ approver switches the subject on. The rule: agreement within the tolerance moderation accepts for human markers, on at least 200 scripts per subject. A new engine or profile version needs a new passing run. Teacher marking assistance comes later; the teachers' own memo preview is not gated.

Code: `app/Services/Learning/Marking/Accuracy/`, models in `app/Models/Learning/Marking/`, HQ pages at `/saas/marking-accuracy`.

## User stories

- As content staff, I import an evaluation paper and its teacher-marked scripts, check them with a dry run first, and nothing identifying is accepted.
- As content staff, I run the engine over a subject's scripts and see how often it agrees with the teachers, by mark point type and rule, and which mark points disagree most, so I can improve the profile, a rule or the memo.
- As the owner (approver), I switch learner marking on in a subject only against its latest passing run on the current versions, with a reason, and it is audited.
- As the platform, I never let a learner receive engine marking in a subject whose engine or profile changed since its last passing run.

## Settings (owner decisions, placeholders)

`config/marking.php` → `gate`. Every figure is a placeholder until the owner decides; there are no environment variables, so a change is a reviewed commit.

| Setting | Placeholder | Meaning |
| --- | --- | --- |
| `subjects` | `mathematics`, `physical_sciences` | Profiles the gate covers. `generic` (every other subject) is never switched on. |
| `owner_confirmed` | `false` | Until true, runs are measured but no subject can be switched on. |
| `min_scripts` | 200 | Fewest scripts in a subject before a run can pass (the plan's figure). |
| `tolerance.point_marks` | 0 | How far the engine may differ from the teacher on one mark point, in marks. |
| `tolerance.script_percent` | 3 | How far the engine may differ on a script total, as a percentage of the script's maximum. |
| `thresholds.point_agreement_percent` | 95 | Share of mark points that must agree within the point tolerance. |
| `thresholds.script_agreement_percent` | 90 | Share of script totals that must agree within the script tolerance. |
| `thresholds.max_bias_percent` | 1 | Largest systematic bias: mean signed difference of script totals, as a percentage of the mean script maximum. |
| `max_scripts_per_run`, `max_scripts_per_import`, `max_import_bytes` | 5000, 1000, 2 MB | Bounds on one run and one import. |

## Data

All four tables are global (platform-owned, no `school_id`), written only from `/saas/marking-accuracy`; the models refuse any write while a school is the current school ([tenancy](../platform/tenancy.md)). None has a learner, school or school-record column.

| Table | Holds |
| --- | --- |
| `marking_evaluation_papers` | `subject` (profile key), `code` (unique per subject), `title`, `source`, `questions` (`[{number, max_units, points: [{earns, type, mark_units, rule}]}]`, as structured memos store them), `max_units`, `fingerprint`, `created_by`. Papers do not change once imported: a corrected memo is a new code. |
| `marking_evaluation_scripts` | `marking_evaluation_paper_id`, `reference_hash` (HMAC with the app key of the source's own script reference; the reference itself is never stored; unique per paper), `marker` (a marker code), `source`, `questions` (`[{number, answer, marks: [units per mark point]}]`), `teacher_units`, `max_units` (of the questions answered), `import_batch`, `imported_by`. |
| `marking_evaluation_runs` | `subject`, `engine_version`, `profile_version`, `script_count`, `point_count`, `passed`, `failures`, `metrics`, `point_stats` (every mark point measured, worst first, at most 500), `settings` (a copy of the settings the run was measured against), `scripts_fingerprint`, `started_by`. Never changed. |
| `marking_subject_gates` | one row per subject: `enabled`, the run it was switched on against with its `engine_version` and `profile_version`, `approved_by`, `approved_at`, `reason`, `changed_by`, `revision`. |

Marks are in hundredths (mark units), as structured memos store them.

## Import

`EvaluationImports`, HQ only (`marking.evaluate`). Every import has a dry run (the form's default) that checks everything and saves nothing. An import is all or nothing: one bad script and nothing is saved.

**Paper (JSON).** `{"subject", "code", "title", "source", "questions": [{"number", "max_marks", "points": [{"earns", "type", "marks", "rule_kind", "rule_value", "rule_tolerance", "rule_unit"}]}]}`. Mark points are validated by the memo editor's own rules (`PastPaperMemos::normalize`): rule kinds and values, marks adding up to each question's total. At most 20 questions.

**Scripts (CSV or JSON), for one paper.**

- CSV: a header row and one row per answered question: `reference, marker, source, question, answer, marks` and optionally `question_total`. `marks` lists the teacher's mark for each mark point in order, separated by `;` or `|`. Rows of one script share its reference, marker and source. Answers may span lines (quoted).
- JSON: `{"scripts": [{"reference", "marker", "source", "total", "questions": [{"number", "answer", "marks": []}]}]}`; `total` is optional.

Checks: the reference is 1 to 40 letters, numbers, `.`, `-`, `/` or `_`; the marker is a code of at most 60 characters; the source 3 to 200 characters; each question is in the paper once; the answer fits `LearnerAnswer` (40 lines of 300 characters, 4,000 in all); one mark per mark point, from 0 to its worth; totals, when given, match; a script is not in the file twice and was not imported before.

**De-identification.** Only the listed fields are accepted, at every level of the JSON and as CSV columns. A field that names a person or a school (name, learner, student, ID number, e-mail, phone, admission or exam number, LURITS, school, date of birth, guardian, …) refuses the whole file with a plain reason; any other unknown field is refused too. An e-mail address, a South African identity number (a valid date and Luhn check digit) or a phone number in the answer, reference, marker or source refuses the script. A written name cannot be recognised: the form says scripts must be de-identified before import. The reference is stored only as a keyed hash.

## Evaluation run

`AccuracyEvaluation::run($actor, $subject)` (`marking.evaluate`) marks every script of the subject's papers with the engine learners would get (judgement switched off) and the subject's profile, in chunks of 200, and stores one run with the current engine and profile versions. Refused with no scripts or more than `max_scripts_per_run`.

| Measure | How |
| --- | --- |
| Mark point agreement | Exact: the engine awarded what the teacher did. Within tolerance: the difference is at most `point_marks`. A mark point the engine sends to a teacher never agrees and counts as not awarded, because a learner would not have received it. |
| Script total agreement | Exact, and within `script_percent` of the script's maximum. |
| Mean absolute difference | In marks, per mark point and per script total. |
| Systematic bias | Mean of engine minus teacher, in marks; for script totals also as a percentage of the mean script maximum. Above zero the engine is more generous. |
| By mark point type, by memo rule | The same figures per `method`, `answer`, `accuracy`, `consistent_accuracy`, `explanation`, and per rule kind, plus the share sent to a teacher. |
| Worst mark points | Per paper, question and mark point: scripts, agreement, engine higher, engine lower, sent to a teacher, the most common engine reason when they disagreed. |

A run **passes** when it has at least `min_scripts` scripts, both agreement shares reach their thresholds and the absolute bias is at most `max_bias_percent`. Failure codes: `too_few_scripts`, `point_agreement`, `script_agreement`, `bias`.

## Switch-on gate

`MarkingGate::open($subject)` is true only while: the subject is in `subjects`; its gate row is enabled; the row's engine and profile versions are the current ones; and the latest run on those versions passed (a later failing run, for example after more scripts are imported, closes it).

- **Switch on** (`PUT /saas/marking-accuracy/subjects/{subject}/gate`, `marking.approve`, Owner only by default): needs `owner_confirmed`, the latest run on the current versions (the form sends its id) to have passed, a reason (10 to 500 characters), the confirmation that the run and its worst mark points were reviewed, and the revision the page showed. The row is locked and the runs read again inside the lock. Audited as `marking_subject_switched_on` with the run, versions, script count and reason.
- **Switch off** (`DELETE` same path, `marking.approve` or `marking.evaluate`): a reason and the revision. Audited as `marking_subject_switched_off`.
- **Versions.** `MarkingEngine::VERSION` and each profile's `version()` (`MarkingVersions`). `MarkingVersionsTest` pins each version to a fingerprint of its code without comments or whitespace, so a change in how the engine or a profile marks fails the build until the version is bumped; the gate then closes until a new run passes and an approver switches the subject on again.

**Where it is enforced.** `SubjectProfile::learnerMarkingEnabled()` asks the gate; `PaperMarking::learner()` (the exam coach flow) returns null before marking anything while it is closed. Practice tests (3.17) mark only choice and number questions and never call the engine; `TestMarkers` documents that an engine marker must check the gate. `MarkingAccuracyGateTest` lists every use of `MarkingEngine` in `app/` (today `PaperMarking` and the evaluation run) so a new learner-facing use cannot be added without it.

## Endpoints (HQ panel)

| Route | Ability | Does |
| --- | --- | --- |
| `GET /saas/marking-accuracy` | `marking.evaluate` or `marking.approve` | Settings (placeholder banner), subjects with gate state, profile version, papers, scripts, latest run and blockers; switch forms; recent runs; papers; import forms and the last import report. |
| `GET /saas/marking-accuracy/runs/{run}` | same | A run's verdict and failures, agreement for mark points and script totals, by type, by rule, the 25 mark points that disagree most. |
| `POST /saas/marking-accuracy/papers` | `marking.evaluate`, `throttle:20,1` | Import (or dry run) a paper. |
| `POST /saas/marking-accuracy/scripts` | `marking.evaluate`, `throttle:20,1` | Import (or dry run) scripts for `paper_id` in `format` csv or json. |
| `POST /saas/marking-accuracy/subjects/{subject}/runs` | `marking.evaluate`, `throttle:6,1` | Run the evaluation. |
| `PUT`/`DELETE /saas/marking-accuracy/subjects/{subject}/gate` | see above | Switch on / off. |

No API change (`openapi.yaml` untouched). HQ pages are in English, like the other HQ pages.

## Notification types

None.

## Module switch

None: the gate is platform-wide per subject. A school's `exam_coach` module still decides whether its learners reach the exam coach at all.

## Security notes

- **Schools.** No school reads or writes these tables; every service refuses inside a school (403), and the models refuse writes while a school is current. Scripts carry no learner or school link.
- **Roles.** Content (`marking.evaluate`) imports and runs, and may switch off; only `marking.approve` (Owner) switches on. Other HQ roles and all school staff are refused.
- **Personal data.** Field allow-lists, refusal of identifying fields and of e-mail addresses, identity and phone numbers in text, and a keyed hash in place of the reference.
- **Bounded work.** File size, scripts per import, scripts per run, answer bounds, throttled routes, errors listed up to 100.

## Acceptance tests

`tests/Feature/Learning/MarkingAccuracyGateTest.php`:

| Test | Then |
| --- | --- |
| `test_the_placeholders_are_the_plans_and_no_subject_is_on_by_default` | 200 scripts, not confirmed, two subjects, no config flag; every subject closed |
| `test_content_staff_import_a_paper_and_scripts_after_a_dry_run` | Dry runs save nothing; imports save, are audited; papers do not change; the reference is never stored; a repeated file imports nothing |
| `test_bad_scripts_are_refused_and_an_import_is_all_or_nothing` | Unknown question, wrong mark counts, marks over the point's worth, duplicates, totals, bounds, bad JSON or CSV header, bad memo rules and sums, uncovered subject |
| `test_no_identifying_data_is_accepted` | Identifying fields in JSON at every level and as CSV columns; e-mail, identity and phone numbers in any text; ordinary maths accepted; no identifying columns; tables are global |
| `test_a_run_passes_only_with_enough_scripts_that_agree` | 19 perfect scripts fail on the minimum, 20 pass; metrics, versions and settings stored; the minimum is configurable |
| `test_a_run_below_the_threshold_fails_and_shows_the_worst_mark_points` | Agreement, bias and MAD figures; failure codes; the worst point; tolerance and thresholds change the verdict; needs-a-teacher counts as disagreement; the run page |
| `test_switching_on_needs_an_approver_a_passing_run_and_the_owners_confirmation_and_is_audited` | Content refused; refused until confirmed; validation and stale revision; switched on and audited; a later failing run closes it; switched off by an evaluator; never with an old or failing run |
| `test_a_new_engine_or_profile_version_needs_a_new_passing_run` | A new engine or profile version closes the gate, refuses switching on until a new run passes, and a new approval reopens it |
| `test_wrong_roles_and_school_staff_cannot_reach_the_gate` | Finance, Support, Reviewer, Sales, Operations and both schools' Super Admins refused everywhere; a guest sent to sign in; nothing changes |
| `test_evaluation_data_is_never_read_or_written_from_inside_a_school` | In School A or B, even HQ staff are refused by every service and model |
| `test_the_engine_reaches_learners_only_through_the_gated_paper_marking` | The only uses of the engine are `PaperMarking` and the evaluation run |

Unit: `AgreementMetricsTest` (arithmetic, each pass rule, needs-a-teacher, tolerance, worst first, identifying-data screen), `MarkingVersionsTest` (version fingerprints), `MarkingEngineTest` (profiles ask the gate). `MarkingPreviewTest` opens the gate through the same tables for its learner tests.

## Self-test steps

On staging, as Content: import a small paper with a dry run, then for real; import a CSV of synthetic scripts (dry run first); run the evaluation and open the run. As Owner: confirm the switch-on form is absent while `owner_confirmed` is false.

## Not in this step

- The real scripts (200+ per subject from partner schools or moderation samples) and the owner's figures: placeholders until decided.
- Teacher marking assistance; app screens; any learner endpoint for the exam coach marking (none exists yet).
- Editing or deleting imported papers or scripts from HQ (a corrected memo is a new code); queued runs (runs are synchronous and bounded).
- Recognising written names in answers.
