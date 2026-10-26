# Teacher Exam Coach phone assertions

The Android Teacher job `121684220891` on integration head
`135cd7230f96297c870750f79541ac06dca711fa` completed with 25 passing tests and
one failure: `runExamTeacher` could not find the exact `73.0%` confidence text
after scrolling to the feedback. The job finished in 41 minutes, so this was
an assertion failure rather than the 45-minute job limit.

The journey now reveals the confidence row independently before keeping the
same exact assertion. It also reveals the publication control before waiting
for it. Neither operation changes the application, submits a request, or
changes a timeout. Existing feedback, exact request retry, single-effect,
verified 750 mark-unit and subsequent lifecycle assertions remain intact.

Local proof uses the actual `ExamScreen`, `ApiExamRepository`, synthetic
integration API fixture and complete `runExamTeacher` journey. The unchanged
journey failed at an earlier off-screen publication wait at 390×600 and passed
at 411×914. With independent row revelation, both complete journeys pass.
Both full-journey widget cases pass; selected Dart analysis and whitespace
checks pass.

The exact hosted confidence failure was not reproduced locally: four direct
confidence-stage widget attempts passed at heights 400, 500, 600 and 914.
The local publication failure establishes one viewport assumption being
removed; it does not establish the precise hosted confidence layout or
image-decoding timing. Hosted Android qualification of the corrected
journey is still required. No Android emulator was run locally.

Evidence retained in the execution workspace:

- `/workspace/scratch/teacher-exam-viewport-red.log`
- `/workspace/scratch/teacher-exam-viewport-red-2.log`
- `/workspace/scratch/teacher-exam-viewport-green.log`
- `/workspace/scratch/teacher-exam-viewport-analysis.log`
