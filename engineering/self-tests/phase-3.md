# Phase 3 verification record

## AI gateway staff foundation

Implemented typed completion/embedding contracts and a vision entry point that
refuses until private media policy exists; versioned bounded prompts; fresh
school/membership/staff-role checks; impersonation exclusion; atomic school/actor
token and conservative integer-cent reservation; private encrypted output/source
receipts; exact UUID replay/conflicts; input/output moderation interfaces;
quarantine flags; protocol validation; conservative failed-outcome charging; and
recorded-observation evaluation with nonzero failure status.

Production inference is disabled and has no configured feature or live provider.
The Phase 2 local transcription implementation remains intact.

Automated verification: **42 tests passed, 502 assertions** across
`InferenceFoundationTest` and the existing `ContentEngineTest`. This covers
happy dispatch/replay, wrong role/school/owner, accidental Student/Parent settings,
revoked membership/context replacement, impersonation, caps, quarantine, unavailable
moderation, uncertain outcomes, invalid embeddings/token reports, prompt traversal,
retained-data rollback refusal and empty rollback/reapply. Evaluation tests catch
leakage, missing/duplicate cases and disabled leakage thresholds. Changed-file
Pint and the complete configured Larastan analysis pass. Final-head CI belongs on
the feature PR and remains required before merge.

A subsequent focused run passes **15 tests, 80 assertions** and checks the final
context recheck before output moderation;
it prevents invoking a school-specific moderator after context replacement or
membership revocation. The source still rechecks again before releasing output.

No new HTTP endpoint or app/panel screen exists in this slice. No live provider,
vision/media, per-learner authorization/budget, counselling access, safeguarding
review/retention workflow, real-model safety evaluation, device, staging, cost
observation or production acceptance is claimed. Prompt/model provenance is
stored for completed inference, but the sample prompt is only a staff-draft
foundation, not an approved tutor or parent prompt. The two-case evaluation
fixture and test-only adapter responses are protocol evidence only.

Review follow-up: moderation callbacks can also change the current school.
Both input/output moderation results now recheck the original school and fresh
authority before any quarantine flag is written. Final focused run: **16 tests
passed, 93 assertions**, including both callback directions, zero foreign flags,
original-school settlement and suppressed output. Changed-file Pint passes.

## Mastery graph and canonical evidence engine

Implemented published stable-topic anchors, explicit sourced acyclic prerequisite
links, separate staff/family evidence and projections, exact integer 0–4 levels,
unknown-versus-zero handling, bounded recent-evidence confidence and up to three
root-gap recommendations. Current grade/year topics plus published prerequisite
closure are eligible; unrelated future-grade and draft nodes are excluded.

Staff uses canonical current question marks. Family uses frozen released snapshot
question evidence plus explicit school opt-in, with fresh own-child/approval and
module/visibility checks. Later raw edits do not alter released mastery. Dedicated
school/subject lock rows serialize derived rebuilds without taking the school
mark-write lock. Mark, release, definition and module-enable events queue rebuilds;
`mastery:rebuild <school> --group=<owned-group>` supports trusted bounded repair.
The module is registered off by default and may be explicitly enabled through the
existing school module management. No learner self-score endpoint exists.

Verification: affected mastery/mark/tenancy cohort **40 passed, 800 assertions**.
Final focused engine/repair-command suite **14 passed, 64 assertions**; changed-file
Pint and complete configured Larastan analysis pass. Covered role/school/owner,
approval/guardian/module/visibility revocation, frozen release versus raw edits,
missing question values, cyclic/duplicate/cross-subject links, draft/future topic
exclusion, replay without duplicate evidence, integer boundaries, empty rollback/
reapply and retained-data rollback refusal.

This backend engine does not claim completed mastery app/panel UX or v2 contract
publication. Those follow after the parallel Phase 4 contract/SDK publication slot.
Practice/tutor evidence adapters arrive with their verified review workflows.
Confidence is a versioned evidence-sufficiency heuristic, not a calibrated risk
probability, diagnostic label or promotion rule. No official CAPS prerequisite
edges, live model evidence, device/staging acceptance or production release is
invented.

Mastery publication review follow-up: a published curriculum header is insufficient
when assessment terms publish independently. Source anchoring and every graph/read
projection now require a published assessment for the exact curriculum and topic
term. A regression publishes then withdraws a second-term reference, verifies no
unreviewed/withdrawn label or edge reaches views, and confirms historical node/link
rows remain retained. With the shared pastoral-role/migration rehearsal changes,
**27 tests passed, 269 assertions** (mastery, role matrix, tenant migrations).
Changed-file Pint and complete configured Larastan analysis pass.

Fresh teaching-authority follow-up: merged the shared role-transition guard, which
requires the current Teacher role before retained Teacher profiles/allocations can
authorize academic reads. The mastery/role/migration cohort remains **27 passed,
269 assertions**; the three Counsellor/Facilitator/Safeguarding transitions pass
**3 tests, 21 assertions**. Complete configured Larastan analysis passes.

Mastery review fixes: changed the derived mutex to school/subject-wide, acquired
before the evidence snapshot and retained through cross-year aggregation. A real
second MySQL connection attempts a different grade/year rebuild during the first
read; the regression failed before the fix and now waits behind the mutex. After
release, former group members keep question-only tagged targets even when the
task tag list is empty; that regression also failed before the fix. The focused
engine/concurrency/tenant migration cohort passes **18 tests, 249 assertions**.
The six new tenant tables still rehearse rollback/reapply successfully.
## Provider and moderation protocol adapters

Implemented fixed-origin HTTPS protocols for OpenAI text/embedding, Anthropic
Messages and DeepSeek text, plus OpenAI text moderation. Separate output/total
budgets, exact returned-model checks, bounded response streams, TLS verification,
timeouts, no redirects/retries, sanitized errors, pseudonymous metadata and staged
UUID headers are covered by synthetic Http fakes. Moderation callbacks now receive
operation/pseudonym context; existing tenant rechecks remain in place.

Final focused protocol/foundation run: **24 tests passed, 186 assertions**.
The existing 27 content-engine tests also passed with the updated moderator
interface. Changed-file Pint and full configured Larastan analysis pass.
No real endpoint was contacted and no provider was enabled or qualified.
These are protocol tests, not real-model safety, privacy, latency or cost evidence.
See the provider adapter spec and qualification record for the release boundary.

## Scoped mastery API and generated SDK

Eight private no-store v2 operations now cover staff configuration/allocated
rosters/learner views, own-family subject selection/views, graph read/write and
queued scoped repair. Fresh role/module/school/owner checks precede replay; empty
graph creation and clearing are supported with exact optimistic revisions. Family
marks hidden by school policy produce no artificial gap recommendations.

The canonical contract combines the parallel activity-group contract before the
pinned SDK generation. Final affected API plus complete contract cohort: **247
tests passed, 1,375 assertions**. The preceding integrated run also passed all
15 engine tests, 21 activity tests and the tenant migration rehearsal; its five
API/route coverage failures were corrected and rerun in the final cohort. Added
negative HTTP transitions for Counsellor, Safeguarding and Facilitator with
retained Teacher profiles. Changed-file Pint and full configured Larastan pass.
SDK generation completed (854 Dart files); six mastery/activity SDK roundtrip
tests pass. SDK analysis reports only 310 generated informational lints, with no
errors or warnings. The owned fragment matches the canonical paths/schemas.

App and panel entry points, layouts and interaction checks are a separate next
increment; this contract PR does not claim those views or whole Phase 3 complete.
No real provider calls, production changes or human/staging acceptance occurred.
Changed-file Pint and complete configured Larastan analysis pass after both mastery review fixes.

Mastery API review propagation includes the school/subject aggregate mutex,
released question-only target fallback, and the latest shared-role and retained
activity-subtree checks. The integrated mastery/activity cohort passes **48 tests,
706 assertions**, with changed-file Pint and complete configured Larastan pass.
An explicit private-response middleware now covers every HTTP method, including
graph receipts, conflict errors, cached receipt replay and repair POST responses;
Laravel's cache-header middleware only covers cacheable methods.

## Feature-scoped inference, learner caps and bounded transport

Implemented the separate server-registered feature-policy contract, immutable
resolved school/actor/learner/subject scope, fresh guardian approval and own-family
checks, staff/family audience separation, learner quotas alongside actor/school
quotas and source/role checks before replay and around every external stage.
Registered features cannot bypass their resolver using the old staff entry point.
Existing staff receipt hashes and local transcription history remain compatible;
the existing nullable learner usage reference is reused with additive budget
indexes. Feature calls restore the caller's tenant after callback misbehavior.

Provider and moderator preflight validate locally before any external moderation.
Known-not-dispatched refusals settle zero, including qualification revoked after
input moderation; unknown HTTP outcomes retain the full reservation. Validated
provider results settle known tokens even when output is quarantined. Per-stage
HTTP/socket timeouts clip to one monotonic overall deadline; response-size and
usage-total consistency checks remain strict.

Verification: affected feature/foundation/provider/content/tenant-migration cohort
**65 passed, 869 assertions**. Focused feature/provider run **21 passed, 173
assertions**, changed-file Pint and full configured Larastan pass. Tests include
wrong role/owner/school/subject, unapproved and revoked learner access, exact-intent
replay, source/role withdrawal, shared learner caps across guardian/student
accounts, context replacement, zero HTTP with an unqualified inference provider,
qualification change after moderation, unknown HTTP failures, late-result
suppression, timeout option propagation, quarantine metering and retained content
transcription behavior. The HTTP fake recorder omits failed requests, so unknown
outcome tests explicitly count attempted callbacks as well as ledger settlement.

These checks prove software authorization/accounting/protocol boundaries using
synthetic fixtures. They do not certify real-provider safety, privacy, latency or
cost. The production registry remains empty, all providers unqualified, and no
paid inference was activated or called. Vision and real domain source policies
remain tied to their upcoming feature workflows.
