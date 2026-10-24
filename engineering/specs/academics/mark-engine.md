# Exact task/question marks, snapshots and release

Phase 2 marks backend and its existing exam consumers. The shared capture grid
is delivered as the following client feature against these contracts.

## Stories and acceptance

Allocated teachers capture exact task or question marks for their group's
learners. One canonical record exists per learner/task; the capturing group is
provenance, so overlapping groups cannot duplicate an official subject result.
Question marks map to stable canonical question definitions. Partial question
capture is saved as incomplete; absent, excused and catch-up are explicit states.
A stale edit returns 409 without overwriting another teacher. Changes after
moderation require the grade head, a reason and before/after audit. A term's
school-timezone lock date refuses capture with 409, including grade-head writes.

The calculator uses rational arithmetic over integer hundredth mark units and
basis-point weights, rounding only the final configured boundary. Task/component
and component/overall normalization excludes excused tasks. Missing work never
silently becomes zero; only an explicitly absent task under the plan's zero
policy does. All-excused or missing components remain incomplete. Running marks
are clearly provisional until coverage and definitions are complete. Annual task
weights are independent from term weights and incomplete annual plans do not
block term calculation. Seven-point ratings use the final configured percentage.

A change queues immutable fingerprinted snapshots for each affected term and the
year. Official results are per learner/subject/grade/year/period, not per group.
Current published definitions are selected independently per term. Same-year
compatible definition revisions reuse canonical task marks; publication cannot
silently remove tasks with recorded mark/status history. Later corrections append
snapshots and do not rewrite already released evidence.

## Data and services

School-owned assessment_marks, question_marks, mark_snapshots and mark_releases
retain exact input units, optimistic revisions, moderation provenance, formula
inputs and release pins. A small academic_exam_links table identifies existing
exam projections. Input fingerprints contain IDs/units/definitions, never inferred
phase policy. No guessed subject ratio is installed: published school definitions
are the calculation contract.

MarkCalculator is pure. MarkRecording owns transactional canonical and legacy
writes. MarkSnapshots recomputes immutable results through a school-scoped queue
job/outbox. MarksPolicy authorizes current allocated capture, grade-head moderation
and school-authorized release separately. A preview uses the same calculator;
clients do not invent a second formula. Matrix rosters are paged at100 by default (maximum200) and school scoped.

Existing v1 exam routes and payloads stay. Their writes pass through the shared
recording/calculation service, preserving legacy grading and envelopes. Explicit
term release writes exam_marks/exam_results projections from the same snapshots;
legacy integer storage has hidden exact units and numeric accessors preserve
fractional obtained marks. No promotion rule is inferred from a rating; canonical
projections leave passing fields null. Projected exams cannot be edited/published
through a bypass of canonical lock/release rules. Parents/learners see only
explicitly released results with fresh own-child/approval checks.

## API, operations and verification

Add v2 capture/read/preview/moderation/release/results operations with thin
FormRequests/policies/resources. API dates are date-only strings; marks are decimal
strings; absent/null values remain distinct from zero. API retries authorize
before cached replay. Recompute jobs are idempotent and school-scoped.

The reconciliation CLI compares an operator's bounded CSV of student_number,
subject_code and percentage against latest official term snapshots and emits
unexplained differences as a failed exit status. Synthetic rehearsals do not
constitute a real pilot term or current DBE-policy acceptance.

Test phase ratios/rounding with independently worked examples, exact rating
boundaries, absent/excused/catch-up/partial questions, definitions/annual coverage,
wrong school/profile/group/learner, expired assignments, moderation/lock/stale
writes, queued recomputation/retries, immutable release, v1 regressions and
projection/reconciliation equality. Independent staging/security/full-term pilot
acceptance stays separately recorded; no production release is implied.
