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
