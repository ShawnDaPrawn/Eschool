# Phase 3: AI learning and pastoral care

Phase 3 was built concurrently with Phase 4, following the owner's instruction. The
academic, attendance, report, content and communication implementations are
already in main. Their external acceptance evidence remains separately recorded.
This plan grows the existing Laravel backend and Flutter shells.

## Merged software checkpoint: 26 October 2026

The retained Phase 3 and Phase 4 software is merged in main
`990ea2a7bb22ca90091ad7bb32fd35b467831474`, whose tree is identical to the
qualified integration head `4a5a43015219c62fc5a05b039eac5adac0c13410`.
Its final CI inventory passed all 106 expected jobs; Security, Staff build
identities and Firebase documentation also concluded successfully. The linked
[verification record](../self-tests/phase-3.md) distinguishes this merged baseline
from the dated feature checkpoints below.

This closes implementation and integration of the retained software, including
alerts, all staff/family readers, teaching publication, My grade actions,
restricted support, transfers and the reconciled fee trend. It does not certify
live AI providers, real-model evaluation, privacy/safeguarding sign-off, observed
costs, representative user acceptance or deployment. Inference remains disabled
by default. The cancelled pilot campaign is not reinstated as preparation for
Phase 5; its removal does not turn unrun external checks into passes.

The bounded operational handover is recorded in
[Phase 3/4 recovery](../runbooks/phase-3-4-recovery.md). Financial posting, payment
providers, wallets and settlement remain Phase 5 work.

## Feature order and ownership

The Phase 3 agent owns steps 1–11 except the weekly parent digest; the lead owns that digest, shared contracts, review,
integration and step 12 (cover/support/dashboard) plus step 13 (transfers). Each row becomes one or more small feature PRs with its own spec,
automated isolation/authority tests and affected-profile self-test record.

| Work | Depends on | State / specification |
| --- | --- | --- |
| AI gateway, prompts, moderation, metering, evaluation | Existing transcription and platform kernel | Staff gateway, protocol adapters and feature-scoped ownership/approval/caps/deadline service implemented. Registered private vision resolution, approved crop/pixel checks and OpenAI/Anthropic image/moderation protocols are implemented and software-tested; concrete Tutor media storage, restricted safeguarding review/retention and school cost/cap controls are implemented and software/browser tested; real-provider qualification remains separate; [feature spec](../specs/ai/feature-inference.md), [vision spec](../specs/ai/vision-inference.md) |
| Bands, alerts and tutor/staff/counselling learner notes | Running marks, fresh allocation/guardian policies | Encrypted domain, private v2/typed SDK, panel/shared readers and all four app-profile entries implemented, verified and merged, including queued rule-aware alerts; [spec](../specs/growth/progress-notes.md) |
| Tutor sessions, subject/source lock, scanned photo feedback and summaries | Reviewed gateway, notes, content | Encrypted own-learner sessions/turns, exact source policy, scanned approved crop storage, feature moderation/provisional feedback, local safeguarding, metadata-only parent summaries and private typed HTTP operations are implemented/tested. Shared Student conversation/photo readers and separate Parent summaries are implemented; 19 focused shared cases, 5 Family entry/cleanup cases and actual Student/Parent Chrome flows pass. Published-topic selection and immutable teacher-approved first-answer checkpoints now update only practice mastery; 30 new/existing client cases and actual Student checkpoint recovery/withdrawal Chrome pass. External inference remains disabled pending qualification; [spec](../specs/ai/tutor.md) |
| Topic dependencies, evidence and mastery map | Question marks and published curriculum | Engine, v2 contract, panel and learner/parent/teacher views implemented and merged; [spec](../specs/growth/mastery.md); verified practice reviews and deterministic approved Tutor checkpoints update separate practice evidence without publishing official marks |
| Daily practice, FSRS reviews, offline daily sets and streaks | Mastery, published content, gateway | Pinned FSRS/answer engine, approved versioned bank, bounded daily-set/review, separate practice mastery and typed API published. Teacher/Student screens and encrypted persistent offline client implemented; full shared suite 458 tests and actual Teacher/Fac Teacher/Student/Parent Chrome qualification pass. AI-assisted question drafts are reviewed and separately published through the teaching workspace; external model qualification remains separate. |
| Licensed past papers and exam coach | Mastery, timetable, vision/media safety | Licensed paper/PDF, deterministic study plans and provisional photo-feedback domain plus 29 private typed operations implemented and tested; Teacher/Facilitator library and review clients plus Student study-plan/approved-crop/feedback clients are implemented; all four actual profile Chrome journeys pass, with Parent denial. The direct web byte-buffer regression and retained native client checks pass; [client evidence](../self-tests/exam-coach-clients.md) |
| Staff-approved teaching drafts and task insights | Marks, content and gateway | Private encrypted domain, fifteen typed API operations, scoped approved script crops, versioned review/explicit publication, approved PDFs and deterministic canonical task insights are implemented, backend/SDK tested and merged; Teacher and dedicated Facilitator editors, private crop/receipt recovery, explicit publication and independent insight readers are implemented, software/browser tested and merged. See [backend evidence](../self-tests/teaching-assistant-backend.md) and [app evidence](../self-tests/teaching-assistant-apps.md). |
| Language-approved weekly parent digest | Own-child records, notifications and approved templates | Deterministic encrypted per-child weekly receipts, Monday school-local job, versioned language approval panel and Parent reader/home action implemented; automated and browser evidence in `engineering/self-tests/weekly-parent-digests.md`. Fluent school wording approval remains an explicit school action. |
| Risk signals and staff-owned interventions | Marks, attendance and confirmed discipline counts; narrow own-authorised Counsellor case context implemented on read without shared persistence | Immutable dated observations, private typed API/SDK, separate caseloads, owner review/handoff panel and classic/native staff apps implemented, tested and merged; no learner-facing risk labels |
| Discipline framework, sanctions, detentions, escalation and acknowledgement | Fresh staff/guardian policies and audit | Year frameworks, encrypted records, reasoned grade-head review/overturn, principal hearings, revision-specific guardian acknowledgement, detention/count adapters and private HTTP/typed SDK and phone/desktop panel implemented, browser-tested and merged; classic teacher, dedicated facilitator-teacher, learner and guardian apps implemented and browser-tested; source-aware notices and fresh recipient actions are implemented and qualified; never automatic expulsion |
| My grade dashboard and actions | Existing dated AcademicLeadership appointments | Current-year head scope, active learner roster, attendance/academic watch, pending discipline metadata and eligible nomination choices implemented. Teacher and dedicated Facilitator hub/actions, exact register/discipline navigation, current grade-parent message preselection and private context/lifecycle boundaries are implemented, software/browser tested and merged; [spec](../specs/growth/my-grade.md). |
| Relief cover, restricted support plans and leadership dashboard | Timetable, pastoral records and shared counsellor policy | Cover and restricted support domain/API/panel/staff apps implemented and merged; principal attendance/activity dashboard and risk/discipline adapters implemented and browser-tested; reconciled current fee-receipt trends are implemented and browser-qualified in the closing increment; new financial products remain Phase 5 |
| Consented inter-school transfers and external signed packages | Existing dated changes and private exit packages | Signed domain, private v2/SDK, staff panel and Parent app implemented; actual two-school browser and Parent Chrome checks pass and the implementation is merged; [spec](../specs/academics/school-transfers.md) |

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

Concrete Tutor, Exam Coach, teaching-draft and school-assistance/translation
policies are registered with fresh source ownership and bounded replay. Approved
vision crops, quarantined media retention, safeguarding review and school cost/cap
administration are implemented and software-tested as recorded above. External
inference remains disabled by default and real-provider qualification remains
separate. Every registered purpose and the shared red-team corpus have
loader/scorer/CLI coverage (37 tests / 918 assertions in PR #297); synthetic
observations verify the harness and do not qualify a real provider. Real-model
observations, privacy/cost qualification and production acceptance are pending
external evidence. Synthetic HTTP protocol results do not certify live provider
safety, semantic quality or billing. Independent implementation continues.

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
