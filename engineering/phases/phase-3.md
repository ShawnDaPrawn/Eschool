# Phase 3: AI learning and pastoral care

Phase 3 runs concurrently with Phase 4, following the owner's instruction. The
academic, attendance, report, content and communication implementations are
already in main. Their external acceptance evidence remains separately recorded.
This plan grows the existing Laravel backend and Flutter shells.

## Feature order and ownership

The Phase 3 agent owns implementation; the lead owns shared contracts, review and
integration. Each row becomes one or more small feature PRs with its own spec,
automated isolation/authority tests and affected-profile self-test record.

| Work | Depends on | State / specification |
| --- | --- | --- |
| AI gateway, prompts, moderation, metering, evaluation | Existing transcription and platform kernel | First staff-only foundation in progress; [spec](../specs/ai/gateway-foundation.md) |
| Bands, alerts and tutor/staff/counselling learner notes | Running marks, fresh allocation/guardian policies | Queued; counselling needs restricted shared policy |
| Tutor sessions, subject/source lock, scanned photo feedback and summaries | Reviewed gateway, notes, content | Queued; no learner inference enabled by the foundation |
| Topic dependencies, evidence and mastery map | Question marks and published curriculum | Queued; publish read contracts early for Phase 4 |
| Daily practice, FSRS reviews, offline daily sets and streaks | Mastery, published content, gateway | Queued |
| Licensed past papers and exam coach | Mastery, timetable, vision/media safety | Queued |
| Staff-approved teaching drafts and task insights | Marks, content and gateway | Queued |
| Language-approved weekly parent digest | Own-child records, notifications and approved templates | Queued; no unreviewed free generation |
| Risk signals and staff-owned interventions | Marks, attendance, discipline and wellbeing counts | Queued; no learner-facing risk labels |
| Discipline framework, sanctions, detentions, escalation and acknowledgement | Fresh staff/guardian policies and audit | Queued; principal hearing referral, never automatic expulsion |
| My grade dashboard and actions | Existing dated AcademicLeadership appointments | Queued; award nominations integrate after Phase 4 awards |
| Relief cover, restricted support plans and leadership dashboard | Timetable, pastoral records and shared counsellor policy | Queued; later money data remains a separate integration |
| Consented inter-school transfers and external signed packages | Existing dated changes and private exit packages | Queued; round-trip authorization and atomic enrolment tests |

## First foundation boundary

Extend `AiGateway` while retaining its existing local transcription path. Build
versioned prompts, typed inference contracts, conservative quota reservations,
input/output moderation interfaces and a recorded-observation scoring command.
The initial executable inference path is internal staff text/embedding only.
Production configuration is disabled with no provider adapters or credentials.
Vision refuses until a Files-backed scanned/redacted media policy exists.
Students/parents remain refused even if mistakenly listed in feature settings.

Bounded provider/text-moderation adapters are the next implemented slice
([spec](../specs/ai/provider-adapters.md)); all remain unqualified and disabled.
Learner authorization/caps, source loaders, feature-specific moderation policies, safeguarding review/retention UI, live evaluation capture and
cost administration are subsequent work, not completed by this first slice.

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
