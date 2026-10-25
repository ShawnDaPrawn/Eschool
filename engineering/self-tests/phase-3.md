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

## Progress-band and learner-note domain

Implemented the step-2 domain with four tenant tables: configurable integer
80/65 bands and minimum assessed share (25% implementation default), encrypted
note bodies and immutable encrypted revisions, and separate dated note caseload
assignments. Academic authority uses fresh current teaching scope or dated
leadership. Counselling requires both the dedicated restricted-role boundary and
an explicit note assignment; principal metadata administration grants no body
access. Visibility/learner/subject/author remain fixed during body edits.
Author access is withdrawn immediately when their scope ends. Body and history
reads are audited without private text or clinical labels. Historical writes and
destructive rollback with retained notes/caseload are refused.

Staff bands use current running snapshots; family bands use the running value of
an approved learner's frozen released snapshot, preserving official totals as a
separate value. Unreleased corrections cannot change a family's band. Insufficient
assessed coverage remains distinct from an assessed zero.

Verification: a combined cohort passed **39 tests, 883 assertions** covering
notes/bands, mastery, restricted-role access, actual migration rollback/backfill
and schema/model tenancy registration. The final expanded focused suite passed
**11 tests, 81 assertions**, including a maximum 20,000-character multibyte body
whose ciphertext exceeds 64 KiB, immutable history, refusal before body SQL loads,
clinical audit redaction, role/allocation/guardian/caseload revocation, wrong
school/learner/subject/author and retained-data rollback. Pint and full configured
Larastan passed. No model/provider calls were made. HTTP/client workflows and
rule-aware queued alerts are subsequent work; step 2 is not yet complete.

## Mastery app and panel views

Implemented shared learner/parent/staff Material 3 screens, own-subject and
allocated group/learner selectors, paginated rosters, three root topics, human
grade/path labels and a separate evidence view. Phones expose bottom navigation;
tablet/desktop layouts use a rail. Home, Settings, module guards and deep links
are wired in both existing apps. Reader/account/school/child boundaries,
backgrounding, refused refreshes and late responses suppress private state; no
mastery cache is persisted. Hidden evidence is distinct from assessed zero.

The panel provides scoped learner guidance, published-source refresh and a
sourced acyclic prerequisite editor with empty/add/remove/clear behavior,
school-wide family opt-in wording, optimistic conflict comparison and escaped
validation draft recovery. Ordinary teachers have no graph editor. Private-cache
headers cover all response methods. New en/af/zu labels are supplied; all other
existing app languages retain explicit English fallback keys. Human language
review remains external acceptance, not an automated fluency claim.

Verification: **30 PHP tests passed, 308 assertions**, changed-file Pint and full
configured Larastan pass. **21 shared Flutter unit/widget/localization tests
passed**, including account/school/token/subject/audience refusal races, lifecycle
clearing, unknown-versus-zero, hidden evidence, pagination and 390/820/1280px
layouts. Full-app headless Chrome flows pass for **learner, parent and teacher**
using synthetic HTTP fixtures. The actual panel Playwright workflow passes at
**390, 820 and 1280px**, including saved edge/source values, teacher read-only
authority, no serious/critical axe issues, no horizontal overflow and no console
or request failures. TypeScript typecheck and JavaScript syntax check pass.
Physical devices, live school datasets, calibrated learning judgments and human
staging acceptance were not run. Tutor/practice evidence adapters remain tied
to their upcoming verified review workflows.

The mastery-specific session boundary also unmounts private evidence when the
same account receives changed roles, guardian-child authority or module context;
three regression tests pass. Both app language/shared-label suites pass (six tests
each), and focused new Dart sources/tests analyze without issues. The new
learner/parent and teacher Chrome flows are included in the CI matrix.

Cookie-session account replacement is now rejected before mastery data loading:
all mastery app reads send the optional expected-account header, and the owned
contract fragment matches the shared canonical contract. A same-account context
refresh recreates the mastery state and reloads server authorization even when
roles/module flags remain identical, covering direct allocation or permission
revocation. Cookie replacement and unchanged-profile permission refresh have
specific regression tests. Combined mastery/API/panel/activity checks pass:
**40 PHP tests, 758 assertions**; full Larastan and Pint pass. The shared suite
now passes **23 tests** and focused changed Dart analysis has no issues. The
shared expected-account, SDK and activity application additions are retained.
These new regression checks do not claim physical-device or live-school testing.

The restricted-note boundary also excludes platform identities from principal
caseload metadata administration. A regression first proved the old metadata
read was allowed, then passed with the explicit exclusion on both read and write.
The domain suite now passes **12 tests, 84 assertions**; Pint and full Larastan
pass. The isolated check uses only the same synthetic school fixtures.

The mastery engine now includes canonical main's relief-cover prerequisite with
both modules, package entitlements and tenant registries preserved. The combined
engine/cover/tenant-schema/migration cohort passes **37 tests, 795 assertions**.
Pint and full Larastan pass. The static check exposed two legacy lowercase
`softDeletes` imports that depended on a different worker loading the trait first;
Shift and Stream now use canonical `SoftDeletes`, and both cold-autoload checks
pass. This integration adds no API or SDK changes.

## Progress and learner-note v2 API

Fifteen private operations now support staff configuration/current rosters,
principal assignment recipients, own clinical caseload, running staff bands,
approved own-family released periods/bands, note metadata/body/history, revisioned
note writes and separate principal caseload administration. Stable operation UUIDs
use encrypted LONGTEXT receipts and keyed intent hashes, with fresh authority
before replay. Bodies do not enter global plaintext idempotency storage. Context
replacement is refused and the original school restored. Expected-account headers
protect cookie replacement. Visibility/owner changes are rejected explicitly.

The combined domain/API/facilitator/tenancy/actual-migration cohort passed
**39 tests, 1,671 assertions**, validating every new API response against the
combined canonical contract. This includes complete maximum multibyte receipt
replay, role/assignment/guardian revocation, platform exclusion, wrong
school/learner/subject/author and no clinical-body SQL during feature discovery.
Pint, full configured Larastan, strict OpenAPI lint and TypeScript pass.

Pinned generation produced **1,041 Dart files**. All **67 SDK tests** pass;
analysis has only **591 generated informational lints**, with no errors or
warnings. Optional facilitator support_plans/learner_notes properties preserve
older three-field JSON omission and serialize supplied false values. A clean
second pinned regeneration reproduced every generated Dart byte. The canonical
contract retains prior bytes and adds the owned paths/schemas and two optional
capabilities; all owned definitions match the canonical contract.

Panel/app note workflows and queued progress alerts continue in the next
increment. No physical-device, live-school, clinical-language or real-model
qualification is claimed, and no provider was activated or contacted.

The complete canonical API catalogue/contract suite also passes **283 tests,
1,379 assertions**, including an anonymous refusal scenario for each new route
alongside the feature suite's real authorized workflows. The merged teacher app
analyzes with no errors or warnings (27 existing informational lints). The latest
reviewed support browser fixes and flow selector are retained.


## Progress and notes panel/shared readers

The actual PHP-backed panel browser passed **two widths (390 and 1280px)** in
47.8 seconds. It exercised Teacher academic create/read with escaped script text,
assigned professional clinical reading, principal metadata with no body/editor,
immediate revoke and former-professional 404 refusal. Both widths had zero
serious/critical axe findings, duplicate IDs and console/network errors; horizontal
overflow was at most one pixel. The browser exposed an unpublished-assessment
409 from the optional band card; the panel now keeps note authoring available
when that independent band source is not yet published.

Shared Flutter qualification passes **17 tests**: three staff widths, clinical
own-caseload discovery without teaching APIs, principal metadata without note/body
APIs, exact immutable unknown-outcome retry, background private-state clearing and
late-response discard, immediate erasure on fresh read refusal, family-only
released bands with unknown distinct from zero, pinned transport identity and
private URL logging suppression, plus four session-refresh/revocation boundaries.
Classic/native app menu wiring and actual application Chrome qualification follow
this shared library. These checks do not claim physical-device, live-school,
clinical-language or human clinical acceptance. Queued alerts are not built here.


## Immediate restricted-note caseload revocation

Principals can explicitly end a dated note-only grant immediately, including on
its first day, without rewriting retained grant dates. Optimistic revisions and
an encrypted operation receipt make exact retries safe; regranting is explicit.
All clinical reads, history, writes and own-caseload discovery exclude revoked
grants. Clinical subject-neutral listing covers the granted learner's subject
notes without expanding academic general-note visibility.

The domain/API/tenant-migration cohort passes **25 tests, 599 assertions**; full
configured Larastan and changed-file Pint pass. It includes a real HTTP grant,
clinical body read, immediate revocation, exact replay, body/history refusal and
empty former caseload, plus wrong-role and foreign-school refusals. No private
clinical text enters the metadata receipt. The additive nullable SDK property
preserves omission from older assignment responses. This prerequisite adds no
new panel or app surface and does not claim clinical user acceptance.

The complete canonical route/contract catalogue passes **284 tests, 1,383
assertions**. Strict OpenAPI lint and all **68 SDK tests** pass. SDK analysis
contains 593 generated informational lints and no errors or warnings.

Panel automated verification passes **7 tests, 52 assertions**, including
independent notes when the term assessment remains unpublished, private escaped
validation/conflict drafts without session flash, historical-year authorization,
cookie replacement, role/owner/school refusal and immediate clinical revocation.
Changed-file Pint and complete configured Larastan pass. Final combined shared
qualification passes **17 tests** and changed Dart analysis reports no issues.


## Application progress and note entry points

Classic Teacher home/settings routes, learner and parent home cards/deep links,
and the native clinical Notes card are wired with fresh school/account/child/module
boundaries. Support and activity-group navigation remain present. The updated
Support selected-ID contract and latest wellbeing/roster SDK are preserved without
another generation; its reader suppresses private URL debug logging too.

Actual Chrome passed **all four affected profiles**: native Counsellor startup
and clinical body access/refresh withdrawal; learner and parent released-band
selection with unknown distinct from zero and resume refusal; and classic Teacher
allocated note reading with role withdrawal erasure. The browser caught a missing
ParentHome card, which is now present, and a fixture path mismatch corrected to
the canonical /periods and /bands. A positive transport regression now proves the
canonical family calls, avoiding an unregistered fake endpoint masquerading as an
authorization refusal. New browser flows are in the existing CI matrix.

Private notes API and panel writes also reject query parameters before merged
validation or historical draft reflection. The real HTTP cohort passes **19 tests,
347 assertions**, with complete Larastan and Pint. Final combined shared tests
pass **25 tests**, including the positive family transport and Support private-log
regression. The focused staff app suite passes **20 tests**, and family entry/label
checks pass **3 tests**. Both app analyzers have no errors or warnings; remaining
informational lints belong to existing shared route/style patterns. No physical
device, live-school, fluent clinical-language or production acceptance is claimed.

## Queued progress-band alerts

Independent staff-running and family-released band state, minimum coverage,
transition deduplication, immutable alert revisions, outbox jobs and threshold
rechecks are implemented. Generic notification payloads expose no learner or mark
values. Fresh source/role/guardian/approval and destination checks run at fanout,
Notifier and transport; older release events cannot overwrite newer family state.

The affected domain/notes/tenant migration cohort passes **18 tests, 316
assertions**. The broader alert/Notifier/preferences cohort passes **34 tests,
135 assertions**. Final focused qualification passes **6 tests, 37 assertions**,
including stringified push IDs, boolean refusal, changed destination, stale
snapshot, out-of-order released evidence and settings requeue. Changed-file Pint
and complete configured Larastan pass. Tests use synthetic schools, fake dispatch
and no actual transport or provider. Generic inbox history contains no private
learner values; application progress readers enforce current per-record access.


## Early warning domain and owned interventions

The current domain cohort passes **16 tests, 87 assertions**, including immutable
current-day evidence, unknown versus zero, minimum coverage/sample, dated rosters,
wrong school/role/owner, fresh professional grant withdrawal, encrypted maximum
multibyte history, body-free metadata/owner handoff and retained-year care. A real
second MySQL connection proves the grade mutex protects source reads; a simulated
source callback replacing school context is refused and restored. A grade change
after source read cannot append stale old-grade observations. Metadata body
affordances also recheck the original authority after a grant is revoked.

The prior affected risk/dashboard/actual-tenant-migration cohort passes **24 tests,
335 assertions**; complete configured Larastan and changed-file Pint pass. Final
merged Admin-viewer/dashboard/actual-tenant-migration checks pass **26 tests, 340
assertions**, with changed-file Pint and complete configured Larastan passing.
No actual counsellor review, diagnosis, clinical risk disclosure, real-school
observation or staff panel/app acceptance is claimed by this service increment.
The HTTP contracts and staff workflows remain the next increment.


## Early warning private API and typed client

The combined private HTTP/domain/actual-tenant-migration cohort passes **25 tests,
571 assertions**, with every new HTTP response checked against the canonical
contract. It exercises creation, exact retries, intent conflicts, original-kind
revocation before replay, owner-only encrypted body/history, metadata queue and
handoff without body SQL, bounded owner choices, settings, separate risk grants,
classroom/leadership scopes, foreign learner/school, wrong account/role/owner,
password/module/platform refusals and replaced-context rollback. Ordinary staff
cannot assign another owner or discover other professional grant holders.
Changed-file Pint, complete configured Larastan and strict OpenAPI lint pass.

Pinned generation produced **1,280 Dart files** for sixteen additive operations
and forty-three schemas. All **77 SDK tests** pass. SDK analysis reports 702
generated informational lints and no errors or warnings. Calendar days round-trip
without timestamps; unknown signals remain distinct from measured zero.
The global route/contract catalogue passes **331 tests, 1,571 assertions**. A
second clean pinned regeneration reproduces every generated Dart byte.
Panel/native interaction acceptance remains the next increment; no family risk
labels, clinical evidence projection or live-school acceptance is claimed.
Panel automated verification passes **7 tests, 52 assertions**, including
independent notes when the term assessment remains unpublished, private escaped
validation/conflict drafts without session flash, historical-year authorization,
cookie replacement, role/owner/school refusal and immediate clinical revocation.
Changed-file Pint and complete configured Larastan pass. Final combined shared
qualification passes **17 tests** and changed Dart analysis reports no issues.


## Application progress and note entry points

Classic Teacher home/settings routes, learner and parent home cards/deep links,
and the native clinical Notes card are wired with fresh school/account/child/module
boundaries. Support and activity-group navigation remain present. The updated
Support selected-ID contract and latest wellbeing/roster SDK are preserved without
another generation; its reader suppresses private URL debug logging too.

Actual Chrome passed **all four affected profiles**: native Counsellor startup
and clinical body access/refresh withdrawal; learner and parent released-band
selection with unknown distinct from zero and resume refusal; and classic Teacher
allocated note reading with role withdrawal erasure. The browser caught a missing
ParentHome card, which is now present, and a fixture path mismatch corrected to
the canonical /periods and /bands. A positive transport regression now proves the
canonical family calls, avoiding an unregistered fake endpoint masquerading as an
authorization refusal. New browser flows are in the existing CI matrix.

Private notes API and panel writes also reject query parameters before merged
validation or historical draft reflection. The real HTTP cohort passes **19 tests,
347 assertions**, with complete Larastan and Pint. Final combined shared tests
pass **25 tests**, including the positive family transport and Support private-log
regression. The focused staff app suite passes **20 tests**, and family entry/label
checks pass **3 tests**. Both app analyzers have no errors or warnings; remaining
informational lints belong to existing shared route/style patterns. No physical
device, live-school, fluent clinical-language or production acceptance is claimed.
