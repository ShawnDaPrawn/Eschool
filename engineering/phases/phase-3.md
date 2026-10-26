# Phase 3: AI learning and pastoral care

Phase 3 runs concurrently with Phase 4, following the owner's instruction. The
academic, attendance, report, content and communication implementations are
already in main. Their external acceptance evidence remains separately recorded.
This plan grows the existing Laravel backend and Flutter shells.

## Feature order and ownership

The Phase 3 agent owns steps 1–11 except the weekly parent digest; the lead owns that digest, shared contracts, review,
integration and step 12 (cover/support/dashboard) plus step 13 (transfers). Each row becomes one or more small feature PRs with its own spec,
automated isolation/authority tests and affected-profile self-test record.

| Work | Depends on | State / specification |
| --- | --- | --- |
| AI gateway, prompts, moderation, metering, evaluation | Existing transcription and platform kernel | Staff gateway, protocol adapters and feature-scoped ownership/approval/caps/deadline service implemented. Registered private vision resolution, approved crop/pixel checks and OpenAI/Anthropic image/moderation protocols are implemented and software-tested; concrete Tutor media storage and operations UI continue; [feature spec](../specs/ai/feature-inference.md), [vision spec](../specs/ai/vision-inference.md) |
| Bands, alerts and tutor/staff/counselling learner notes | Running marks, fresh allocation/guardian policies | Encrypted domain, private v2/typed SDK, panel/shared readers and all four app-profile entries implemented and verified in review; queued rule-aware alerts are in independent review; [spec](../specs/growth/progress-notes.md) |
| Tutor sessions, subject/source lock, scanned photo feedback and summaries | Reviewed gateway, notes, content | Encrypted own-learner sessions/turns, exact source policy, scanned approved crop storage, feature moderation/provisional feedback, local safeguarding, metadata-only parent summaries and eleven private typed HTTP operations are implemented/tested. Shared Student conversation/photo readers and separate Parent summaries are implemented; 19 focused shared cases, 5 Family entry/cleanup cases and actual Student/Parent Chrome flows pass. Explicit topic selection and verified exercise outcome integration follow. External inference remains disabled pending qualification; [spec](../specs/ai/tutor.md) |
| Topic dependencies, evidence and mastery map | Question marks and published curriculum | Engine, v2 contract, panel and learner/parent/teacher views implemented in review; [spec](../specs/growth/mastery.md); verified practice/tutor evidence adapters follow |
| Daily practice, FSRS reviews, offline daily sets and streaks | Mastery, published content, gateway | Pinned FSRS/answer engine, approved versioned bank, bounded daily-set/review, separate practice mastery and typed API published. Teacher/Student screens and encrypted persistent offline client implemented; full shared suite 458 tests and actual Teacher/Fac Teacher/Student/Parent Chrome qualification pass. AI-assisted drafting remains. |
| Licensed past papers and exam coach | Mastery, timetable, vision/media safety | Licensed paper/PDF and deterministic study-plan domain plus 17 private typed operations implemented and tested; clients and provisional photo-feedback increment in progress |
| Staff-approved teaching drafts and task insights | Marks, content and gateway | Private encrypted domain, fifteen typed API operations, scoped approved script crops, versioned review/explicit publication, approved PDFs and deterministic canonical task insights are implemented and backend/SDK tested in review; Teacher and dedicated Facilitator screens and browser workflows follow. See [backend evidence](../self-tests/teaching-assistant-backend.md). |
| Language-approved weekly parent digest | Own-child records, notifications and approved templates | Deterministic encrypted per-child weekly receipts, Monday school-local job, versioned language approval panel and Parent reader/home action implemented; automated and browser evidence in `engineering/self-tests/weekly-parent-digests.md`. Fluent school wording approval remains an explicit school action. |
| Risk signals and staff-owned interventions | Marks, attendance and confirmed discipline counts; wellbeing unavailable pending explicit disclosure | Immutable dated observations, private typed API/SDK, separate caseloads, owner review/handoff panel and classic/native staff apps implemented and tested in review; no learner-facing risk labels |
| Discipline framework, sanctions, detentions, escalation and acknowledgement | Fresh staff/guardian policies and audit | Year frameworks, encrypted records, reasoned grade-head review/overturn, principal hearings, revision-specific guardian acknowledgement, detention/count adapters and private HTTP/typed SDK and phone/desktop panel implemented and browser-tested in review; classic teacher, dedicated facilitator-teacher, learner and guardian apps implemented and browser-tested; source-aware notices follow; never automatic expulsion |
| My grade dashboard and actions | Existing dated AcademicLeadership appointments | Current-year head scope, active learner roster, attendance/academic watch, pending discipline metadata and eligible nomination choices implemented. Teacher and dedicated Facilitator hub/actions, exact register/discipline navigation, current grade-parent message preselection and private context/lifecycle boundaries are implemented and software/browser tested in review; [spec](../specs/growth/my-grade.md). |
| Relief cover, restricted support plans and leadership dashboard | Timetable, pastoral records and shared counsellor policy | Cover and restricted support domain/API/panel/staff apps implemented in review; principal attendance/activity dashboard implemented and browser-tested; risk/discipline adapters follow, later money data remains separate |
| Consented inter-school transfers and external signed packages | Existing dated changes and private exit packages | Signed domain, private v2/SDK, staff panel and Parent app implemented; actual two-school browser and Parent Chrome checks pass in review; [spec](../specs/academics/school-transfers.md) |

## Gateway implementation and release boundary

The existing local transcription path is retained. Versioned prompts, typed
complete/embed contracts, input/output moderation, encrypted receipts, bounded
HTTP adapters for three providers and recorded-observation scoring are built.
The legacy entry point remains staff-only. Registered features must use
`AiGateway::runFeature` and cannot bypass their policy through the staff API.
Feature policies resolve immutable authorized sources; the gateway independently
checks fresh actor/school/guardian/learner/subject scope, learner/actor/school
budgets, receipt intent and a ten-second overall deadline. Qualification and
bounded payload preflight make no network calls; known local refusals settle zero
while unknown dispatched outcomes consume the full conservative reservation.

The feature-policy registry is empty by default, external inference is disabled,
and providers remain unqualified. Concrete feature source loaders, safe Files
vision, safeguarding review/retention and cost administration UI, and live-model
evaluation capture remain subsequent feature work. No fake adapter result
qualifies real provider safety or billing. Independent implementation continues.

## Concurrent integration and evidence

Phase 4 groups, facilitator identity, consultations, admissions and cards proceed
without waiting for this phase. Its assistant/translation needs the reviewed
inference contract; peer tutoring needs mastery; My grade award nominations need
Phase 4 awards. Shared permissions and tenant isolation are agreed before either
stream introduces counsellor records. No platform impersonation bypass is allowed.

Acceptance checkpoints do not freeze independent development. Provider/privacy,
representative safeguarding review, real-model red-team evaluation, cost and
performance evidence remain explicit. No fake adapter result certifies a model,
no paid provider is activated, and no production release is authorized here.
