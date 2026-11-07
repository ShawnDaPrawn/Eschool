# Structured memos

Action Plan 3.15. Owner role: Backend (learning). Builds on the [exam coach](exam-coach.md) library (`past_papers`, `past_paper_versions`) and is the foundation for the marking engine (3.16), test assembly (3.17) and the accuracy gate (3.18).

An exam coach paper version already carries, per question, a prompt, free-text memo, total marks (`max_units`, in hundredths) and topics. That memo is prose: a marking engine cannot tell which part earns which mark. This step adds **mark points**: for each question, the list of things that earn marks, each with its type, an optional rule and its marks, and a reviewed state for the whole memo.

## User stories

- As the teacher who added a past paper, I break its memo into mark points question by question, so marking can later award each mark for a stated reason.
- As a grade head (or principal, or academic manager), I can capture and review the mark points of any paper of my grade.
- As another teacher of the group, I can read the mark points but not change them.
- As whoever builds marking, I can ask for the mark points of a version and get them only when a teacher has reviewed the memo.

## Rules

- **Mark point.** What earns it (2 to 500 characters); a type: `method`, `answer`, `accuracy`, `consistent_accuracy` or `explanation`; an optional rule; and its marks (a decimal with at most two places, more than zero, stored as hundredths). At most 20 points per question.
- **Rules.** `none`; `exact_value` (a value, a tolerance of plus or minus, default 0, and an optional unit); `units_required` (a unit only); and, since the marking engine (3.16), `expression` (an expression or equation the engine can read), `keywords` (1 to 10 terms separated by commas, alternatives after a slash) and `significant_figures` (a whole number from 1 to 15). A rule's fields must match its kind: a value with no rule, or a units rule with a value, is refused. The [marking engine](../learning/marking-engine.md) evaluates them.
- **Totals.** The mark points of a question must add up exactly to the question's total. This is checked in the FormRequest (`ExamMemoRequest`, through `PastPaperMemos::normalize`) and again in the service (`PastPaperMemos::save`). A draft may leave a question with no points; every other question must add up.
- **Questions.** The input lists every question of the version, in the version's order, by number. A mismatch is refused ("its questions changed").
- **Who may change it.** The same people who may change the paper (`PastPaperLibrary::editor`): its author or a lead of its grade, allocated to a current group the version is for, with exam coach writable. The paper must be active and the version draft or published (not withdrawn).
- **Licence.** Every read and write goes through `PastPaperLibrary::version`, so the licence attestation must still be confirmed and unexpired, and the topic references current. An expired licence refuses with 409.
- **Review.** A teacher who may change the memo marks it reviewed, confirming they checked every point. Review needs the exact revision, a draft memo, and every question complete (points adding up). Saving a reviewed memo returns it to draft and clears the reviewer, so every change is reviewed again.
- **Downstream use.** `PastPaperMemos::reviewed($actor, $group, $version, $staff)` returns the mark points only when the memo is reviewed and still complete; otherwise null. It checks the reader's access exactly as for the paper (a learner only for the published version of their own group), for server-side marking. Learners never receive mark points: no API returns them.
- **Concurrency.** Each save and review gives the expected revision (0 before the first save) and runs inside the school lock; a stale revision is refused with 409 and changes nothing.
- **Privacy.** Mark points are encrypted (`encrypted:array`), like the memo text they come from. The audit log records `exam_coach.memo_saved` and `exam_coach.memo_reviewed` with ids, states, revisions and a point count, never the text.
- **Schools.** `past_paper_memos` is school-owned; another school's version id is not found (404) and its rows are unchanged.

## Data

`past_paper_memos`: `id`, `school_id`, `past_paper_version_id` (unique per school), `state` (`draft` or `reviewed`), `revision`, `mark_points` (encrypted list of `{number, points: [{earns, type, mark_units, rule}]}`), `updated_by`, `reviewed_by`, `reviewed_at`, timestamps. One row per version: published versions never change, so their mark points stay matched to them. `down()` refuses to drop the table while it holds rows.

## Panel

| Page | Path | Name |
| --- | --- | --- |
| Papers per group, with each memo's state | `GET /exam-memos?group=` | `exam-memos.index` |
| One memo: questions, memo text, mark points, review | `GET /exam-memos/groups/{group}/versions/{version}` | `exam-memos.show` |
| Save mark points | `POST` same path | `exam-memos.save` |
| Mark as reviewed | `POST …/review` | `exam-memos.review` |

Private, no-store, under `module:exam_coach` (writes need it writable). The menu item "Exam coach memos" shows to teachers who can use exam coach (`exam_coach_staff`, `ExamCoachAccess::availableStaff`). The form shows the saved points plus two empty rows per question; an empty row is ignored. Its script packs the question controls into one JSON field during native form submission, below PHP's default 1,000-input limit even for 20 questions with 20 points each. The request decodes at most 1 MiB with bounded depth, restores the normal trimming rules, and applies the same ownership, question, point, total and revision validation. Invalid packed input is refused without falling back to separate controls. A rendered form exceeding the server's input-variable limit includes an empty payload guard before its question controls. The script replaces it; if the script is unavailable, validation refuses the submission and preserves every saved point and the revision. Native small forms remain supported without the script.

## Acceptance tests (`Learning/StructuredMemosTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_teacher_saves_mark_points_reviews_the_memo_and_only_then_it_is_usable` | A published two-question paper (3 and 2 marks) by Tara | Tara saves points, saves again, reviews, saves once more | draft at revisions 1 and 2, text encrypted, rule and hundredths stored; `reviewed()` null until review, then the points (also for the learner, server side); a later save returns it to draft and `reviewed()` to null |
| `test_mark_points_must_add_up_to_the_question_total_in_the_service_and_the_form` | The paper | points totalling 2 of 3, 3 of 2, or a zero-mark point, via service and form | refused on the question's field; no row written |
| `test_each_rule_needs_its_own_values_and_the_paper_questions_must_match` | The paper | an exact value with no value, a units rule with a value, a unit with no rule, an unknown type, a wrong question number | refused; no row |
| `test_a_memo_is_reviewed_only_when_every_question_is_complete_and_revisions_match` | A memo with question 1.2 empty | review (service and form), a stale review, a stale save | refused; memo unchanged at revision 1 |
| `test_wrong_role_learner_and_parent_cannot_change_or_review_a_memo` | A saved memo | the learner and a parent save, review or open it | 403 (the panel sends a parent to sign in); memo unchanged |
| `test_wrong_owner_another_teacher_of_the_group_reads_but_cannot_change_the_memo` | Theo also teaches the group | he opens, saves and reviews | reads the points with no form; save and review 403; unchanged |
| `test_wrong_school_version_ids_are_not_found_and_school_b_memo_is_unchanged` | A School B version and memo | Tara reads, saves or reviews it (service and panel) | 404; School B's row identical; nothing written in School A |
| `test_the_licence_attestation_and_a_withdrawn_paper_stop_memo_changes` | A saved memo | the licence expires; then the paper is withdrawn | save and `reviewed()` 409; after withdrawal save and review 409; unchanged |
| `test_panel_lists_papers_saves_rows_and_reviews_the_memo` | The paper | Tara uses the panel | menu item for Tara, not the learner; list and page private; blank rows ignored; review needs the confirmation; reviewed shows in the list; a stale form changes nothing |
| `test_a_scriptless_rendered_form_cannot_clear_the_last_questions_truncated_mark_points` | An 18-question saved memo whose final question lands at PHP’s input boundary | The actual rendered form posts without its packing script | Validation refuses the truncated form; all saved points and revision remain unchanged |

The full-paper PHP regression uses `parse_str` at the default `max_input_vars=1000` before posting the parsed body; it checks all 400 points and a stale retry. A second regression serializes the actual rendered 18-question form without JavaScript, exercises PHP's boundary immediately before the final question's points, and confirms the saved memo and revision remain unchanged. The small-form panel regression retains native submission. Packed-input regressions cover malformed/oversized JSON, totals, whitespace and wrong-owner/learner access. `e2e/tests/exam-memo-form.spec.ts` exercises a real browser's native submission of all 2,820 question controls with the empty guard present and a corrected retry without disabling the editor.

## Not in this step

- **AI conversion of memos into mark points.** The existing drafting path (`TeachingDrafts` through the AI gateway) is tied to learning-content versions, its own draft kinds, prompt, moderator output parser, quarantine participant and evaluation corpus, and inference is off. A "draft mark points from the memo text" action needs its own gateway feature with all of these (prompt `resources/ai/prompts`, corpus `resources/ai/evals`, feature policy, moderator, quarantine, HQ caps). Its output would land as a draft memo for teacher review (this step's draft state), never reviewed automatically. Conversion from uploaded PDF memos depends on that too.
- The marking engine and rule evaluation: built in 3.16 ([marking-engine.md](../learning/marking-engine.md)).
- Copying mark points from an earlier version of the same paper into a new version.
- Mark points in the staff memo PDF, and any API or app screen for mark points (no `openapi.yaml` or `/api/v2` change).
- More rule kinds (accepted alternatives, ranges); 3.16 added expressions, keywords and significant figures.
