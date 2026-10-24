# Phase 2 verification record

## Academic references and class mapping

Implementation: academic reference API and explicit class mapping complete; no
new panel/app screen or mark engine is included in this slice.

Local verification: SDK generation, 13 Dart tests, analysis with no errors or
warnings (generator style infos only), OpenAPI strict lint and Larastan passed.
The final academic/contract/tenancy/authorization run passed 327 tests with
14,245 assertions, including migration rollback/reapply and revoked-permission
retry protection. Formatting and git diff whitespace checks passed. CI's full
backend, native app, browser, documentation and security results are recorded on
the feature PR; all required checks must pass before merge.

Covered checks: reference content and exact rating boundaries; authorized mapping,
clear and audit; wrong-role/school/class and expired membership refusals; v2
contract, generated SDK, migration rollback/reapply and unchanged v1 behaviour.

Staging verification: pending isolated staging access. Review the reference and
mapping as school admin; verify teacher, parent and learner refusal; smoke-test
installed clients. No staging, pilot or production check is claimed as passed.

Phase acceptance: pending the remaining academic core, operational/security/UX
checks and a full term of reconciled pilot evidence. Starting this foundation does
not complete Phase 0, Phase 1 or Phase 2. Production release remains unapproved.

## School terms and templates

Feature spec: ../specs/academics/school-terms.md. Owner-authorized semester
retirement replaces the earlier bridge plan. Local verification passed: 26 term /
year tests (437 assertions), 278 API/tenancy/authorization regression tests
(3,780 assertions), migration down/up with 51 identity/link/grant assertions,
and two isolated browser flows (admin and teacher, including accessibility and
1024/1280 overflow checks). Both app unit suites and core/people/SDK package
suites passed; SDK date-only writes and explicit null clears are covered.
App analysis had no errors/warnings; OpenAPI strict lint, documentation build,
Larastan and changed-file formatting passed. Initial full-suite regressions were
fixed and their affected tests rerun; final full-suite/native/security CI results
are recorded on the feature PR and must pass before merge.

The panel flow covers grade calendars, template preview/apply, teacher refusal,
accessibility and overflow at 1024/1280 pixels. Staging, real devices and pilot
acceptance remain pending. No assessment-lock or report-release engine is claimed.

Review follow-up: 37 term/year/academic authorization tests (507 assertions)
passed, including non-destructive class enable, repeat submissions and foreign
school/year/role refusal. The learner term-picker regression passed with no
initial current term. App analysis and Larastan were rerun for these changes.

Consumer integration follow-up: 25 panel browser checks passed, covering exam /
elective pages, teacher-related pages at 1024/1280, mouse/keyboard navigation and
term/template/class-enable flows. The new class-settings read is school-scoped,
permission-checked and tested against foreign years. The final targeted backend
cohort passed 37 tests with 511 assertions. Old navigation URLs and removed config
requests are retired; the full CI browser suite must pass before merge.

## Subject standards and school mapping

Spec: ../specs/academics/subject-standards.md. The trusted operator importer and
scoped catalogue/mapping/create APIs and panel are implemented. Local checks:
100 feature/contract tests (674 assertions), the ownership and rollback/reapply
cohort (12 tests, 485 assertions), generated SDK regeneration and 19 Dart tests,
SDK analysis without errors/warnings, Larastan, strict OpenAPI lint and docs build
passed. Final browser and CI evidence is recorded on the feature PR.

The bundled 629-entry DBE-NCS-2015 catalogue is historical source data with no
assessment weights and is not installed or published automatically. Source and
policy confirmation remain explicit operator/school decisions; no existing mark
or assessment plan changes from a subject mapping. Staging and external
acceptance remain pending.

The subject-standard browser flows passed for admin mapping/creation and teacher
read/refusal, including axe and overflow at 1024/1280. The first browser retry
needed its CSRF header on a cookie-authenticated write; production CSRF checks
were left intact. Ignored build outputs from completed worktrees were removed
to recover workspace disk space, after which docs build and browser checks passed.

Final subject-standard regression cohort: 15 tests and 675 assertions passed,
including the unchanged v1 snapshot test and schema rollback/ownership checks.

## Curriculum and assessment definitions

Implemented scoped versioned drafts, topic/task/question editing, exact maximum
units, independent term publication, immutable headers, optimistic revisions,
same-year source provenance and explicit new-year draft history copying.
Local API/contract cohort: 114 tests, 775 assertions passed. Panel/navigation
cohort: 24 tests, 648 assertions passed. Generated SDK: 21 tests passed; analysis
has no errors/warnings (79 generator style infos). Strict OpenAPI and Larastan
passed. Final regression, browser and CI outcomes are recorded on the feature PR.

Browser setup initially needed explicit labels for dynamic selects and the
isolated demo's public avatar symlink. Runtime access/CSRF rules were preserved.
Staging, device and real-school policy/pilot verification remain pending.

Final curriculum checks passed: 180 academic/contract/ownership/migration tests
(12,202 assertions), 22 targeted route/tenancy tests (187 assertions), two browser
flows covering question entry/publication/copy and teacher controls, axe and
1024/1280 overflow, plus documentation build and E2E TypeScript checking.
The browser checks used an isolated synthetic school database.

Curriculum review follow-up: all three browser flows passed, including reopening
empty term/annual component maps, adding percentages and saving them intact.
The editor normalizes empty PHP arrays into JSON objects before editing.

## Teaching groups and dated allocations

Implemented groups, effective memberships and teacher/homeroom/leadership records,
class mapping forms, scoped API contracts, projection refresh and canonical date
authority. Local API/contract cohort passed 123 tests (841 assertions); panel and
navigation passed 21 tests (676 assertions). After a workspace disk-full incident,
96 academic/schema/authorization checks passed again (11,770 assertions). Generated
SDK regeneration and 24 Dart tests passed; analysis has only generator style infos.
Strict OpenAPI, Larastan and documentation build passed. Final browser/route/CI
outcomes are recorded with the feature PR. No external staging/pilot result is
claimed from synthetic fixtures.

The disk incident stopped the disposable test database; that regression run was
invalidated. Completed local preview servers were stopped, 875 MiB of ignored
generated caches in clean, already-merged worktrees were removed, and the synthetic
database was restarted before rerunning. Source files and live data were retained.

Final allocation follow-up: 124 API/contract tests (852 assertions), 14 focused
service tests (164 assertions), all eight role-matrix checks and 11 targeted
tenancy-route checks passed. Two browser flows passed for admin mapping/group/
membership/teacher allocation and teacher roster/refusal, with axe and overflow
at 1024/1280. Projection reuse adopts exact legacy IDs and flattens matching
coexisting group grants without duplicate app assignments.

Allocation review regressions passed: 31 group/enrolment tests (241 assertions),
including actual term-filtered parent teacher responses and chat scope, and a
mixed-group learner moving to a different grade. Mixed groups never create
whole-class legacy authority. Bound projections recheck grade/homeroom and current
dates. Incompatible memberships close in the enrolment transaction with audit.

The existing parent/teacher API security suites passed 15 tests and 162 assertions
after the dated-assignment term consumer and enrolment reconciliation changes.


## Exact marks backend and release

Implemented canonical task/question recording, exact rational calculation,
audited revision/moderation/lock rules, immutable queued snapshots, selected
release previews/pins, family reads, protected existing-exam projections and a
bounded weekly reconciliation command. The shared adaptive capture grid follows
as a separate client feature; this backend adds no new panel page.

Local verification so far:164 calculator/mark/API-contract/audit checks
(1,011 assertions),139 mark/contract/migration checks (1,008 assertions), and47
existing API/authorization/tenancy checks (422 assertions) passed in separate
cohorts. SDK regeneration and28 Dart tests, analysis with no errors/warnings
(130 generator style infos), strict OpenAPI lint, Larastan, changed-file Pint,
whitespace checks and documentation build passed. The entire backend suite and
final profile additions are running/pending; final CI evidence belongs on the PR.

Covered profiles include allocated teacher, unallocated teacher, school releaser,
grade head, learner/family authorization and ended guardian relationships.
Synthetic reconciliation covers exact matches/differences/duplicate refusal and
foreign-school terms. No real school sheet, full-term pilot, staging performance,
independent security review, device timing or production acceptance is claimed.


The full local backend run passed1,185 tests but failed two portal-cookie
fixture checks (24,490 assertions). This worktree's intentionally blank ignored
.env left APP_NAME at Laravel's default, so the session store was initialized
with a different cookie name before those fixtures changed configuration.
Restoring the repository example's APP_NAME=e-School locally, without changing
application code or credentials, made all31 final mark/profile/portal checks
pass (313 assertions), including principal expiry and grade-head correction.
The original full run is not reported as wholly passing; the final CI full suite
remains required. The failure is recorded rather than waived.


Compatibility review caught the installed teacher model's int-only obtained_marks
fields. Removed floating accessors before merge. Official projections now use
integer basis-point scores out of10,000, so all legacy numeric score fields remain
whole numbers and percentages stay exact. Hidden hundredth columns retain exact
inputs; legacy whole-score presentation rounds once. Targeted projection,
recording, snapshot and client-decoder regressions must pass on the updated head.


The integer-projection regression passed36 calculator/mark checks
(257 assertions). The strengthened real parent-v1 response/shared teacher-model
fixture then passed23 mark checks (209 assertions) and the existing teacher
Dart decoder test. Fractional percentages remain exact without changing any
installed int-only score field. The source review and final CI continue on this
updated commit; earlier green checks alone do not authorize merging a changed head.


## Timetable engine backend

Implemented rooms, bell schedules/periods, dated lessons, actual-time clashes,
explicit CSV label mappings, atomic preview fingerprints, audited revisions and
cancellation, scoped week/next-period reads and protected v1 projections. The
shared app/panel editor is the following client slice.

Local checks:15 timetable tests passed (199 assertions), including cross-school
configuration IDs, own-child reads and guardian revocation, fresh permissions
before idempotency replay, homerooms, overlapping calendars, shared learners,
legacy conflicts/protection, alternate weeks, stale previews, cancellation and
used-period provenance. Earlier140 timetable/OpenAPI checks passed (854
assertions); after expanding coverage,145 timetable/API checks passed (1,079
assertions). Five v1 snapshots add only nullable room_name; internal source IDs
are hidden. Migration rollback/reapply passed in the wider regression run, whose
initial API-shape failure was the intended room label and was then updated and
verified without snapshot-update mode. SDK regeneration,32 SDK tests, strict
OpenAPI and Larastan passed. The initial inline-array enum was replaced in the
contract with documented string clash codes after generator validation failed;
generated Dart was never edited by hand.

Full final CI, staging, real vendor export rehearsal, classroom timing, physical
devices, independent review and pilot acceptance remain unclaimed here.
## Shared mark capture and released-result clients

Implemented the shared academics package, teacher Home/Settings entry points and
family Settings entry point behind the server feature flag. School/child changes
clear private screens, server capabilities govern actions and conflict responses
retain drafts. Families discover only authorized released periods, including
archived calendars, and never request a staff matrix.

Local verification: 22 package tests passed, including exact bounded paste, late
loads/previews, failed/revoked saves, school/child switches, release preview/CAS
conflicts, 390/1024/1280px layouts, larger text and keyboard traversal across 35
virtual learners on phone and desktop. Package analysis reports no issues. The
new teacher and parent Chrome integration flows both passed against fake HTTP
responses through actual app routes. Teacher full suite:28 passed. Backend:
147 mark/API/contract checks passed (1,159 assertions); a strengthened archived-
calendar regression then passed all24 marks tests (254 assertions). Strict
OpenAPI, Pint, Larastan and workflow syntax checks passed. Context snapshots add
only the intentional academic_marks capability flag.

These are synthetic local checks. Classroom capture timing, physical devices,
staging performance, real pilot sheets and independent review remain pending.
No production deployment or Phase2 acceptance is claimed.


### Timetable workbench and clients — implementation verification in progress

Shared adaptive phone/week layouts, manager preview/commit editor, original
revisions and context boundaries are implemented. Both apps expose the week from
Settings and a room/teacher next-period home card. The panel configures rooms,
bells, lessons and explicitly mapped CSV/TSV imports using the same services.
Initial school week/calendar selection is server-controlled; read-only calendars
aggregate only authorized lessons. School/child changes clear previous data.

Local evidence: 35 shared academics tests pass; 24 panel/context/timetable tests
(287 assertions) pass; API contract regeneration/review adds only the timetable
capability to the three v2 context snapshots; 180 contract assertions pass.
Teacher Chrome next-period-to-week flow passed. Panel Playwright passed all
three flows: 1024px and 1280px room/bell setup, placement/clash, mapped import,
axe/overflow/console checks and teacher refusal. SDK32, teacher28 and family45
tests pass. OpenAPI, workflow lint and docs build pass. Family Chrome passed on a separate run after an initial browser-tab crash.
Pint passed908 files and Larastan found no errors. The combined local suite ran
1,240 tests:1,238 passed; design-token and Arabic navigation-label checks found
two presentation issues, corrected with targeted verification below. Exact-head
CI remains required before merge. No physical room walk, 20-minute editor UX trial,
staging performance or owner acceptance is implied by synthetic fixtures.
Timetable follow-up: the first full backend run passed1,226 tests (24,924
assertions). Four review findings then received fixes: current-term projection
for year-wide lessons, exact-provenance cancellation after teacher/group changes,
a school lock covering the entire projection refresh, and earliest authorized
next-period reads across overlapping calendars. Managers retain visibility of
obsolete placements to cancel them; family reads stay scoped. All19 focused
review checks passed (217 assertions), including both real installed-family API
paths and a competing second MySQL connection that times out while refresh
holds its lock. The final combined tree includes the marks clients merged in#90.

Targeted verification after the two presentation fixes:35 tests passed
(816 assertions), including navigation, design tokens, all timetable/context
regressions. The timetable backend merged as#91 after31 exact-head checks passed.


### Attendance backend verification in progress

Dated homeroom/period registers, atomic durable offline operations, correction
authority/audit, private QR issue/rotation and timezone cutoff notices are
implemented.26 combined API compatibility, teacher-security, register and
tenancy checks passed (869 assertions);154 register/contracts checks passed
(878 assertions). Larastan is clean. Migration rollback/reapply is rehearsed;
v1 snapshots are unchanged. SDK generation produced419 files; final SDK tests
and additional alert/transport checks remain in progress. Shared tap/QR/offline
clients and panel overview are the next slice. Physical-device timing, staging
delivery, independent security review and full-term pilot evidence remain open.

Attendance follow-up:43 register, notification and queued-delivery checks pass
(216 assertions), including approved leave, current guardian rights before
transport and stale amendment suppression.35 SDK tests pass; SDK analysis has
no errors/warnings (generated informational lints remain). OpenAPI lint and
route-cache/clear pass. Additional API choice/foreign-scope/revoked-QR replay
assertions are being verified before the backend PR.

Register provenance verification:181 combined register/timetable/contract checks
passed (1,187 assertions). After allowing safe room/future-range edits while
retaining captured occurrences,37 register/timetable checks pass (365 assertions).
The existing OpenAPI paths/schemas/responses/parameters are semantically unchanged.
Larastan remains clean. Full Pint checked929 files and found one configuration
import-format issue; that file was formatted and its targeted style check passes.

Final regenerated SDK verification:35 tests passed after register-label additions.

Attendance reason privacy:14 register checks pass (92 assertions), including
encrypted correction reasons in the database and audit snapshots.

Timetable client completion:PR#92 merged after the complete CI, Security and
Firebase documentation workflows passed on c8b5d7939839aa6d9c0e50d20941974bedaac74e.
This includes the full backend, both Android suites, web builds and Chrome flows.
There are no unresolved timetable review threads. This is implementation/automated
verification; physical classroom and pilot acceptance remain separate.

Attendance compatibility/review follow-up:161 register/contract checks pass
(985 assertions).19 register/old API checks pass (344 assertions), preserving
v1 response shapes and canonical presence over old leave overlays.32 register
and queued-delivery checks pass (244 assertions), including executing queued
mail/push after correction and later leave approval with no transport call.
Larastan is clean. Existing half-day rows remain visible, preserve IDs on
correction, and retain fractional reports. App account activation does not drive
physical/historical rosters; no account is activated by capture.

### Attendance clients — implementation verification in progress

Shared register tests: 55 passing, including 35-learner tap capture at 390/1024/1280,
offline duplicate/order/conflict/revocation, persistence failure, encrypted disk
reopen/account purge, QR duplicate/foreign matching, large text/read-only dates,
family-only requests and late child-response suppression. Both app analyses have
zero errors/warnings (existing informational lints remain). Panel and Chrome
integration evidence is being collected before this feature is published.
Physical camera/device and classroom timing acceptance remain pending.

Attendance client results (24 October 2026): shared academics 55 tests passed and
analysis clean; teacher app 28 tests and family app 45 tests passed; both app
analyses have no errors/warnings. Teacher Chrome sign-in → Home → allocated
35-learner register → explicit capture → sync passed. Parent Chrome Home → own
child attendance/QR passed, with every request pinned to that child/school and no
staff roster request. Panel Playwright passed three flows: capture, QR,
concurrent-tab conflict review/correction at 1024 and 1280; teacher earlier-day
read-only register. Axe serious/critical violations and page overflow checks pass
at both widths. Native/JSON row-count mismatch was found in browser rehearsal,
fixed, and both widths rerun successfully. Documentation build passed; PHP style
passed 933 files; Larastan clean. These are local/synthetic results, not physical
camera, real classroom timing, staging or full-term pilot acceptance.

Attendance client full CI found a Bootstrap class on the new page (1,280 other
PHP checks passed) and an early QR Enter submission in clean browser capture.
The page now uses scoped M3 token styles. QR/bulk enhancement starts after DOM
readiness, and its controls stay disabled until handlers are attached; native
row capture remains available. The 24 targeted PHP/style checks pass. All four
panel flows pass, including an intentionally delayed script load proving QR is
disabled before initialization and Enter never submits attendance. Exact updated
CI remains required before merging the client slice.

Report backend local evidence (24 October 2026): the full local backend suite
passed 1,334 tests (25,919 assertions). After adding paged retained history, fresh
family-link access, explicit ungraded/excused provisional results and current
grade-head correction after year rollover, the final report/register/contract
cohort passed 204 tests (1,372 assertions). These include real private PDF output,
ordered approvals, per-scope comment revisions, stale source/job refusal, immutable
releases, no fee gate, own-child signed URLs and transport revocation checks.
SDK generation is pinned to 7.25.0 (527 generated files); all 37 SDK tests pass,
with no analysis errors/warnings (existing generator style infos remain).
OpenAPI lint passes. Full PHP style previously passed 958 files; changed-file
style passes after the follow-ups. Extracted synthetic PDF text contains 75.00%,
rating 6 and escaped literal markup. Final Larastan, route-cache and complete CI
results belong to this feature's final head. Report clients/panel are a separate
slice. No staging, real parent delivery, device or pilot acceptance is claimed.

Report review follow-up: history lists now use the saved report grade, matching
detail access after a homeroom remap. The old grade head retains report history;
the new grade head cannot see its summaries; expired appointments lose access.
All 181 report/API contract checks pass (1,164 assertions), with clean Larastan
and changed-file formatting. Exact updated CI must pass before merge.

Report retry follow-up: 197 report/idempotency/contract checks pass (1,284
assertions), with clean Larastan. Private report retry bodies are encrypted,
replayed headers hide internal encryption metadata, and operation keys are bound
to the active school even for one staff account assigned to both schools.

Annual reporting follow-up: panel rehearsal exposed a legacy school-year date
being a string rather than a term date object. Annual roster/evidence now parse
the explicit year dates and refuse undated years. A full annual selection through
approval/private PDF generation passes. All 198 report/idempotency/contract checks
pass (1,291 assertions); Larastan and formatting are clean.

Report backend completion: PR #95 merged after complete CI, Security and Firebase
documentation workflows passed on c432cd9cab2e57a30efdcdeb575561308802b1b8.
The current-head review thread is resolved. Main includes annual reports, private
retry receipts and history authority regressions; no production release is made.

### Report clients and panel verification

Shared academics: 90 tests pass; analysis clean. Family app: 45 tests pass; teacher
app: 28 tests pass; both app analyses have no errors/warnings. Core API/fake-asset
cohort: 28 tests pass. Regenerated SDK: 37 tests pass, no analysis errors/warnings
(generator style infos remain). Strict OpenAPI lint, documentation build, E2E
TypeScript and workflow validation pass.

Both actual Chrome flows pass: parent sign-in, selected-child report history,
fresh signed access and PDF rendering; teacher sign-in, Reports, allocated subject
comment and saved revision. Family traffic is pinned to its child/school and never
fetches staff rosters. Panel preparation, comment correction, ordered signatures,
private PDF preview and release passed at 1024 and 1280 pixels. The separate
teacher two-tab conflict/review flow passed; axe and overflow checks passed.

Final local PHP cohort: 207 report/contract/navigation/read-only-year/route/style
checks pass (1,836 assertions), with clean Larastan and changed-file formatting.
It covers native replay redirects, revocation before replay, explicit historical
batch writes despite the legacy header, and scoped restoration of rejected text.
Browser rehearsal caught required correction reasons and test navigation/scope
selectors. A real restoration bug was fixed: one comment's old input could fill
another comment or a generic bank field; only the exact submitted form now restores
its draft. The regression asserts the private draft occurs once and is not saved.

Final client CI, native device acceptance and real provider delivery are separate
checks. Synthetic browser timings do not establish classroom or full-term pilot
acceptance. No production deployment or additional paid service was performed.

Report client review follow-up: selected bank comments resolve to their effective
text before conflict review, so review/save retains the attempted comment rather
than stale textarea text. The allocated-comment list now excludes leadership
overrides; those remain in batch review with a correction-reason field. All 190
report/API contract checks pass (1,279 assertions), with clean Larastan and style.
Final CI #333, Security and Firebase workflows passed on 0a005bbb; PR #96 merged into main.


### Mid-year learner changes and signed exit copies (24 October 2026)

Added office join/leave/subject-change previews and commits, grade-policy CAS,
immutable encrypted facts and retained cancellation history. Learner access ends
on the day following the inclusive departure date, checked before issuance and
on requests; another school and parent/staff roles remain usable. Joins may proceed
before subject allocation, and unmapped legacy homerooms may process departures.
No money is posted and no approval/activation is inferred. Carried canonical marks
are reused without score copies; catch-up/excused never invent zeros.

Exit evidence includes selected actual released report PDFs and permitted current
documents, released marks, captured attendance and enrolment history. Tests verify
real ZIP bytes, every file checksum and a detached Ed25519 manifest signature,
encrypted private storage, reader/school/owner/field revocation, stale generation
attempts, source changes, corruption, copy expiry and source-record retention.
Changing retention requires re-preview for a new copy; queued copies retain their
pinned expiry. Larger histories/files fail with a scoped-export instruction.

A full local backend run completed with 1,392 passes and two failures: the new
navigation label was absent from panel translations, and updating class projections
exposed an old dangling-homeroom fixture. Both were fixed; the subsequent affected
279-check cohort passed (2,728 assertions), including legacy marks, identity,
reports, navigation, all v2 contracts and tenancy migrations. Final follow-up
coverage adds inactive-learner office access and document-field revocation.

All 40 SDK tests pass; generated SDK has 607 files and analysis has no errors or
warnings (generator style infos remain). A real SDK test caught date-only values
being serialized as timestamps: transition school-day fields now use explicit
YYYY-MM-DD text. Strict OpenAPI lint, E2E TypeScript, actionlint, Composer validation,
route cache/clear and documentation build passed. Runtime/CI explicitly require
sodium and zip; Composer dependency package versions did not change.

Actual Chrome/Playwright: full join → subject change → departure → preview → queued
signed ZIP download passes at 1024 and 1280; ordinary teacher access returns 403.
All three flows pass axe serious/critical, overflow and console checks. Browser
rehearsal found and fixed the unapproved-learner document-lookup 404 and checksum
text contrast. Rehearsals create fresh synthetic learners without deleting history
or activating an account. CI on the published head is still required before merge.

Wider Phase 1 retention-policy acceptance, real recipient hand-off, physical
phones, full-term parallel pilot, staging load budgets and independent security
review remain external evidence. They do not freeze independent Phase 2 work.
No production deployment or paid provisioning was performed.

Final mid-year follow-up: 219 backend checks pass (1,766 assertions), with clean
Larastan/style after the browser fixes. This includes the inactive learner office
page, retained-export field revocation and the retention preview race.

PR #97 review follow-up: the legacy parent session gate now uses fresh guardian
rights and preserves retained-record access after the only child departs, including
a later year without a session. Ended links and unrelated manual deactivation still
refuse access. Join fingerprints include year dates and the reviewed membership
end. All 35 mid-year/parent security/policy checks pass (359 assertions).

Mid-year final delivery: all CI, Security and Firebase workflows passed on
e263e53f67c73dcea36f30b9049225406feb221f. PR #97 merged to main as 5924d20,
including both resolved review findings, native Android and full backend checks.


### Private content backend/service — local evidence, 24 October 2026

- 21 content-engine tests passed, 334 assertions: upload/replay; scan refusal and
  outage; retry/generation races; review/publication immutability; draft/published
  audience separation; wrong role/school/author/child; current guardian delivery;
  curriculum publication tags; source capability/tampering/expiry; queued
  identifier-only outbox; interrupted lease/quota recovery; actual-duration
  metering and safeguarding quarantine. Positive v2 responses are contract checked.
- Converter: 42 tests passed after version-ID protocol alignment, including a real
  30-page PDF, Word/slides/spreadsheet and pinned-model audio/video; malicious
  archives/source URLs, scratch removal, offline/resource controls and duration
  refusal. Python production dependency audit: no known vulnerabilities found.
- Generated Dart SDK: 673 files; 40 existing tests and four new content contract
  tests passed; analysis has no errors/warnings (existing style infos remain).
  OpenAPI strict lint and actionlint passed. Larastan reported no errors.
- Full PHP regression/cache checks and actual real-clamd/image CI are tracked
  separately below when complete. The local daemon test is intentionally skipped
  without a real private clamd; it is not recorded as passed.
- Security Diff Scan's referenced preflight/helpers are unavailable in this host.
  A manual change review and normal CI checks proceed; no completed plugin scan
  or independent security acceptance is claimed. Panel/app profile and browser
  evidence will be recorded with their separate feature slice.


### Private content ready-to-review checks, 24 October 2026

- Final focused backend/guard/sanitizer/style cohort: **32 passed, 395 assertions**;
  all 1,034 PHP files pass Pint and Larastan reports no errors. This includes
  the native panel's upload/edit/conflict/preview/publish flow, school suspension,
  role/owner/school boundaries, bounded/resumable notification fanout and leases.
- The broad local regression ran before the final panel changes: 1,453 passed,
  one daemon integration skip, one response-return guard failure. The handler was
  corrected and that guard passes in the final cohort; the exact PR-head full CI
  suite remains the required final regression evidence. No broad local all-pass
  claim is made from that earlier run.
- Playwright **3 passed**: complete teacher upload, draft edit, human publication,
  immutable replacement draft/history and withdrawal at 1,024/1,280 px; another
  teacher's private-item refusal. Both widths pass serious/critical axe checks,
  horizontal overflow and own-origin console/error checks. Demo data and a fake
  converter/scanner isolate UI rehearsal from service/AV integration evidence.
- Browser checks caught and fixed legacy jQuery MIME validation blocking valid
  filename-extension accept lists. Native multipart forms keep browser constraints
  and server FormRequests; other legacy file forms retain their old behaviour.
- Generated SDK: **44 tests passed**, including multipart selection/source/school,
  human-review booleans, null queued/publication state and scoped reader leases.
  Python: **42 passed**. TypeScript, OpenAPI strict lint, actionlint, route/view/
  config cache creation and cleanup pass.
- Optional local compose/worker wiring, current-definition real clamd/EICAR and
  six-format read-only offline image smoke are committed CI checks. Their actual
  run/merge evidence is recorded after the PR completes. Physical-device,
  end-to-end staging latency, language accuracy and independent security/pilot
  acceptance remain separate. Shared app screens are the next content slice.


PR #98 review follow-up: **31 tests passed, 408 assertions**, covering nested
numbered lists/paragraphs/code and stable code whitespace across repeated saves,
a real v2 save above 1 MiB, worst-case JSON escaping at the 2 MiB text bound,
unchanged limits on other routes/methods and understated length-header refusal.
Larastan and strict OpenAPI lint pass; the 673-file SDK was regenerated.
CI run 40111629362 verified the real ClamAV/EICAR step and actual PDF, Word,
slides, spreadsheet, audio and video inside the read-only offline runtime.
Security run 40111956196 passes after the exact public-checksum exception;
93769303 is superseded by the review fixes, so final-head CI is still required.
