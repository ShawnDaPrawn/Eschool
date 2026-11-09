# Marking engine: subject profiles and rules

Action Plan 3.16. Owner role: Backend (learning). Builds on [structured memos](../ai/structured-memos.md) (3.15): a reviewed memo's mark points are what the engine marks against. Test assembly (3.17) and the accuracy gate (3.18) build on this.

The engine marks one typed answer against one question's mark points and says, for every mark point, whether it is awarded, which rule decided it, a plain reason and a confidence. Rules are evaluated in code. Method and explanation marks that no rule can decide need judgement: that seam exists but is switched off, so those marks go to a teacher and are never awarded automatically.

Code: `app/Services/Learning/Marking/`. Pure engine (`MarkingEngine`, no database or network) plus `PaperMarking` for the exam coach paper flow.

## User stories

- As a teacher of a paper's group, I type an answer the way a learner would and see how the memo's mark points decide each mark, so I can check my mark points before learners rely on them.
- As the builder of practice tests (3.17), I call one service that marks a learner's answer against a reviewed memo and returns a per-mark-point breakdown.
- As the platform owner, I know no learner receives engine marking in a subject until that subject passes the accuracy gate (3.18).
- As a learner (later, once a subject is switched on), I see which marks I earned and why, never the memo's own words.

## Answers

A typed answer is a list of steps, one per line (`LearnerAnswer`). Blank lines are ignored. At most 40 lines of 300 characters and 4,000 characters in all. The last line is the final answer. A line's **results** are what it states after its last `=`; "x = 2 or x = -3" (also `;`, "and", Afrikaans "of" and "en") has one result per clause. Leading "Answer:", "Therefore", "Antwoord:", "∴" are ignored.

Text is normalised first (`AnswerText`): one kind of minus and space, `÷` as `/`, `≈` as `=`, superscripts as powers (s⁻¹ → s^-1, x² → x^2), `√` as `sqrt`.

A **stated value** is a number followed only by a unit or words ("12,5 m/s east", "The speed is 12,5 m/s"); a number inside a calculation ("100/8") is not a stated value.

## Rules

Each mark point has a type (`method`, `answer`, `accuracy`, `consistent_accuracy`, `explanation`) and an optional rule. 3.15 had `exact_value` and `units_required`; this step adds `expression`, `keywords` and `significant_figures` to the memo editor (same form controls: the rule's value field now takes the value, expression, terms or figure count).

| Rule | Stored as | Decides | Limits |
| --- | --- | --- | --- |
| `exact_value` | `value` (decimal with a dot), `tolerance` (±, default 0), optional `unit` | Answer, accuracy and CA marks: a stated value within the tolerance, the final line first (an earlier line counts with lower confidence). Method marks: the value anywhere in the working (a substitution), also with the opposite sign (a sign convention) at lower confidence. | Floats compared with a 1e-9 allowance relative to the values compared, with no absolute floor (a memo value of 0.00000001 keeps its precision). Numbers: decimal comma or point (a comma is always decimal), spaces between digit groups (1 000), scientific notation (3 × 10^8, 3e8, 10^-3). |
| units (inside `exact_value`) | `unit` | The learner's unit is read and compared by SI dimensions; an equivalent unit is converted before the value is compared (45 km/h for 12,5 m/s). A wrong unit loses the mark; an unreadable one, or a memo unit the engine does not know, goes to a teacher. A missing unit loses the mark only where the profile requires units. | Base and derived SI units (m, g, s, A, K, mol, cd, N, J, eV, W, Pa, Hz, C, V, Ω, F, T, L), min, h, %, °, rad, prefixes p to G, powers and quotients (m/s, m·s⁻¹, m.s-1, ms-1, kg/m^3, J/(kg·K), km/h) and unit words (metres per second). °C is never converted to K. "ms-1" is read as m·s⁻¹. "M" (molar) is not known. |
| `units_required` | `unit` | The final answer (the lowest line that states a value) has the memo's unit or an equivalent. | As above. |
| `expression` | `value`: an expression (2x + 6) or an equation (v = d/t) | An equation: any line (first side against each later side) that is the same equation. An expression: a result that matches (method marks: any part of the working). Profiles that accept equivalent forms compare by value; others compare the written form: the same tokens in order, with spaces and multiplication signs aside (2*x matches 2x, but 2*3 never matches 23). | Numbers, variables, + − × ÷ ^, brackets, implicit multiplication, π, sqrt, sin, cos, tan, ln, log, abs; at most 160 tokens, 32 levels and 8 variables. Equivalence: equal (to one part in ten million, relative, no absolute floor) at ten fixed sets of positive and negative values, ignoring undefined points, at least four defined. Equations: one is a non-zero multiple of the other, or one is solved for a variable and substituting it makes the other true and the other is not always true (v = d/t and d = vt). Implied but narrower equations (v² = d²/t²) also pass. a/bc reads as (a/b)·c. Inequalities are not read. Trigonometry is by value only. |
| `keywords` | `terms`: 1 to 10 terms, alternatives after a slash (oxygen/O2, catalyst) | Every term appears as whole words, ignoring case, accents and a plural s/es. | Presence is not understanding: for explanation marks the count is evidence for a teacher, never an award. No synonyms beyond the alternatives. |
| `significant_figures` | `figures` (1 to 15) | The final answer's significant figures equal the memo's. | Trailing zeros of a whole number (1200) are ambiguous: awarded at confidence 0.6 when the count is possible. |
| none | — | Method marks go to the judgement seam; answer, accuracy and CA marks go to a teacher. | — |

**Follow-through (consistent accuracy).** A `consistent_accuracy` point whose own value is wrong may follow through only when an earlier answer, accuracy or CA point of the same question with a value rule was not awarded. The engine then needs a line `calculation = result` whose calculation is plain arithmetic, uses a value the learner stated on an earlier line, and gives the stated result to its own precision (half a unit in its last place); the unit still counts where the profile requires units. Then it is awarded with `decided_by: follow_through` at confidence 0.7. Anything else goes to a teacher. Follow-through works for `exact_value` CA points only.

**Judgement.** Explanation points, and method points without a rule, go to `MarkingJudgement`, bound to `DisabledMarkingJudgement`: no model call, "needs a teacher", confidence 0.1. Whatever a future implementation returns, the engine turns it into "needs a teacher": judgement never awards or refuses a mark on its own. A real implementation needs its own AI gateway feature (prompt, evaluation corpus, policy, moderation, quarantine, HQ caps) once a provider is qualified (3.1). The marking code does not reference the AI gateway (a test checks this).

A blank answer earns nothing: every point is not awarded, without judgement.

## Subject profiles

`SubjectProfiles` chooses a profile from the subject's name, or the name of the published subject standard it follows, in English or Afrikaans.

| Convention | Mathematics (Mathematics, Maths, Wiskunde, Technical Mathematics) | Physical Sciences (Physical Sciences, Fisiese Wetenskappe, Physics) | Generic (everything else, including Mathematical Literacy) |
| --- | --- | --- | --- |
| Equivalent forms for expression rules | Yes; but when the mark point's wording asks for a form (simplify, factorise, expand, "in the form", surd; Afrikaans too), an equivalent but different form goes to a teacher | Yes | No: the written form only |
| Consistent accuracy follow-through | Yes | Yes | No: to a teacher |
| Unit missing when the memo gives one | Allowed | Loses the value mark | Allowed |
| Rounding | The memo's tolerance only | Also a final answer correctly rounded to at least two decimal places, or given to more places than the memo and rounding to it | The memo's tolerance only |
| Direction | Not checked | When the mark point's wording names one direction (left, right, up, down, north…, Afrikaans too), the answer must name it; no direction, or a sign as well as a direction, goes to a teacher; the opposite direction loses the mark | Not checked |
| Formula first | No | A formula mark (method point, equation rule) needs a calculation in the answer: a side of an equation made only of numbers, operators, functions and a unit, or a line stating a value (a formula's own coefficients and powers do not count); when it is lost and the learner wrote a different formula, the later answer, accuracy and CA marks go to a teacher | No |
| Letters in expressions | xy is x·y | Runs of letters are one quantity (vf, Fnet, Ek, v_f = vf), split only by the memo's own quantity names (with m, g and h in the memo, mgh is m·g·h); a Greek letter starts a new quantity (aΔx) | xy is x·y |

Memo authors in Physical Sciences should separate quantities with spaces or × (Ep = m g h).

## Result

```json
{"engine": "rules-v1", "profile": "physical_sciences", "question": "1.1", "max_units": 300,
 "awarded_units": 200, "pending_units": 100, "status": "needs_teacher", "confidence": 0.85,
 "points": [{"index": 1, "earns": "Uses v = d/t", "type": "method", "mark_units": 100, "awarded_units": 100,
   "status": "awarded", "rule": "expression", "decided_by": "rule", "reason": "The formula from the memo is written on line 1.",
   "confidence": 0.95, "line": 1}]}
```

- `status` per point: `awarded`, `not_awarded` or `needs_teacher`; `decided_by`: `rule`, `follow_through` or `teacher`.
- `awarded_units` never includes a mark that needs a teacher; `pending_units` is their sum. The question `status` is `complete` when nothing needs a teacher.
- `confidence` per point is 0 to 1 (needs-a-teacher at most 0.4); the question's is the lowest of the decided points, or null.
- Reasons are plain, translated sentences that never quote the memo's expected value.
- `MarkingEngine::forLearner()` drops `earns` and `rule` (the memo's words); learners never receive mark points.
- `engine` is the rules version, so 3.18's agreement figures are tied to the version measured.

## Where it is used

| Use | How |
| --- | --- |
| Teacher preview | `GET /exam-memos/groups/{group}/versions/{version}/marking` (`exam-memos.marking`) shows the paper's markable questions (points add up to the total), the memo's state, the subject profile, its conventions and whether learner marking is on. `POST` same path (`exam-memos.marking.preview`, `throttle:60,1`) marks the typed answer and shows the breakdown table. Linked from the memo page once it has mark points. Same middleware as the memo pages (`module:exam_coach,read`, private, no-store). It uses the memo's current points, draft or reviewed, and says which. Nothing is stored or audited. |
| Exam coach flow (service) | `PaperMarking::learner($actor, $group, $version, ['question', 'answer'])`: only a reviewed memo (`PastPaperMemos::reviewed`, with the learner's own access checks: their group, the published version, the licence) and only when the subject's accuracy gate is open; otherwise null before anything is marked. Returns the learner shape. No endpoint calls it yet. Practice tests (3.17) mark only choice and number questions and never call the engine. |

## The accuracy gate

Learner marking in a subject follows the [accuracy gate](marking-accuracy-gate.md) (3.18): `SubjectProfile::learnerMarkingEnabled()` asks `MarkingGate::open($profile->key())`, which is true only after an HQ approver switched the subject on against its latest passing evaluation run on the current `MarkingEngine::VERSION` and the profile's `version()`. The generic profile is never switched on. The teacher preview is not gated: it is for teachers checking their own memos. Each profile has a `version()` (`mathematics-v1`, `physical_sciences-v1`, `generic-v1`); `MarkingVersionsTest` pins the engine and each profile version to its code, so a change in how they mark needs a new version and so a new passing run.

## Data changes

None. Mark points keep their 3.15 shape; new rule kinds add `{kind: 'expression', value}`, `{kind: 'keywords', value, terms: [[alternatives]]}` and `{kind: 'significant_figures', value, figures}`. Existing memos are unchanged.

## Endpoints

No API change (`openapi.yaml` untouched). Panel routes above.

## Screens

`learning/exam-memos/marking.blade.php`: explanation, memo state, profile conventions, a question select and an answer textarea, then the summary ("Awarded 2.00 of 3.00 marks. 1.00 marks need a teacher.") and a `page-table` of mark points with result, rule, decided by, reason (with line) and confidence. Empty state when no question is ready. The memo editor's rule value field accepts the new rule kinds, and its read-only list shows them.

## Notification types

None.

## Module switch

`exam_coach`, as the memo pages.

## Security notes

- **Schools.** Every read goes through `PastPaperLibrary::version`: another school's version id is not found (404) and its memo is untouched.
- **Roles and owners.** The preview needs a teacher of the paper's group (a learner is refused 403, a parent is sent to sign in, a teacher outside the group is refused). The learner call needs the learner's own group and the published version.
- **Memo text.** Learners never receive `earns` or rules; reasons never quote the expected answer.
- **No storage, no model calls.** The preview writes nothing (not even the audit log). The engine has no database, network or AI gateway access.
- **Bounded work.** Answer size limits, expression token, depth and variable limits, unit readings capped at 16, a fixed number of evaluation points; the preview is throttled.

## Acceptance tests

Unit (`tests/Unit/Learning/Marking/`):

- `NumbersAndUnitsTest`: decimal comma and point, digit groups, scientific notation, significant figures and decimals (including ambiguous trailing zeros), tolerance and rounding, units written several ways, SI conversions with their factors, wrong, missing, unreadable and unknown units, answer lines and stated values, input bounds.
- `ExpressionTest`: equivalent and non-equivalent forms, symbols versus letters, splitting by memo quantities, equivalent equations (rearranged, multiplied, solved for another variable) and trivial ones refused, constants, unreadable and oversized input.
- `MarkingEngineTest`: breakdown and confidence shape and the learner shape; exact values (tolerance, final line first, several answers); method values; units by profile (required, converted, wrong, unreadable, unknown); `units_required`; Physical Sciences rounding and direction; significant figures; keywords; equivalent forms by profile and "asks for a form"; formula first; follow-through (awarded, wrong arithmetic, not from the earlier answer, unit, generic, nothing to follow); method and explanation to a teacher with the AI gateway mocked to never be called; judgement can never award or refuse; no AI reference in the marking code; each profile asks the accuracy gate about its own key and has a version; profile choice by subject name; unknown rule kind.

Feature (`tests/Feature/Learning/MarkingPreviewTest`):

| Test | Then |
| --- | --- |
| `test_memos_store_expression_terms_and_significant_figure_rules` | New rule kinds are stored; unreadable expressions, units on expressions, empty or too many terms, bad figure counts and non-numeric exact values are refused; nothing changes |
| `test_teacher_previews_marking_against_the_memo_and_nothing_is_stored` | The memo links the preview; the page is private and shows profile, memo state and flag; answers are marked with reasons; memo rows and the audit log are unchanged |
| `test_preview_input_is_bounded_and_needs_complete_mark_points` | Incomplete questions are not offered and are refused; unknown question 404; long answers and bad question numbers refused; a blank answer earns nothing |
| `test_wrong_role_learner_and_parent_cannot_use_the_preview` | Learner 403, parent to sign-in, service refuses both |
| `test_wrong_owner_a_teacher_outside_the_group_cannot_preview_until_allocated` | Refused before allocation, allowed after |
| `test_wrong_school_school_b_memos_cannot_be_used_and_are_unchanged` | 404 from the page, the preview and the learner call, even with learner marking on; School B's memo row is identical |
| `test_learner_marking_is_off_until_the_subject_passes_the_accuracy_gate` | With a reviewed memo and the gate closed, the learner call returns null and the engine is never invoked |
| `test_learner_marking_once_switched_on_uses_only_the_reviewed_memo_and_hides_its_words` | A draft memo is never used; the result has no memo words; unknown question 404; another learner, a parent and a teacher are refused |

## Self-test steps

On staging, as a teacher of a Physical Sciences or Mathematics group: open a memo with mark points, follow "Try marking an answer against this memo", mark a correct answer, one without units, one with a different formula and a blank one, and check each row's reason. As a learner, confirm the preview URL is refused.

## Not in this step

- Test assembly, timed mode and any learner endpoint or app screen (3.17).
- The evaluation harness and switching any subject on ([accuracy gate](marking-accuracy-gate.md), 3.18).
- Photographed or handwritten answers (vision is a gateway feature, inference is off).
- Judgement by a model for method and explanation marks.
- Marking answers stored as `ExamAnswer` working text; storing marking results.
- Accepted alternative answers, ranges, inequalities, intervals and set answers; follow-through for expression rules; molar (M) and other non-SI units.
