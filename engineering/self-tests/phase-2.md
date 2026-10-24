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
