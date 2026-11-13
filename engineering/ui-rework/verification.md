# Revision 3 admin UI verification

## Isolated execution

The local verification runtime uses PHP 8.3.36 from the existing application
image and a new MySQL 8.0 container on the `ui-rework-isolated` network. It does
not connect to the existing application or database containers. Each domain
has its own database ending in `_test`; the browser uses the separately seeded
`ui_rework_browser_test` database. All records and sign-ins used in the browser
are the repository's synthetic `DemoSchoolSeeder` fixtures.

The browser PHP backend serves the working tree on `http://127.0.0.1:8091`.
A local static front end at `http://127.0.0.1:8092` serves the same public assets
and forwards dynamic requests to that backend, avoiding static files queueing
behind application requests during concurrent verification. Debugbar is
disabled. A second local worker pool and static proxy on `8094` share only the
isolated browser database, allowing independent domain sessions to run without
interrupting in-flight requests on `8092`. PHP opcode caching is enabled with timestamp validation on every
request (`validate_timestamps=1`, `revalidate_freq=0`), so changes remain visible
while concurrent browser checks avoid repeatedly compiling the application. Production runtime settings were
not changed. New dependency migrations were applied to this isolated browser
database without dropping or reseeding it. Source suites later use the separate
`ui-rework-mysql-ram` MySQL host with a 4 GiB tmpfs directory to avoid slow
disk-bound schema rebuilding. Its databases still end in `_test`. This is
disposable correctness-test infrastructure, with no crash-durability claim.

The local runtime also uses an ignored `database/schema/mysql-schema.sql`
snapshot generated from an empty isolated database by running all 300 source
migrations, without seeders or fixtures. A separate restoration probe verified
all 613 table definitions and column metadata, 298 triggers, every row count,
and checksums of the migration-created reference records. User, learner,
financial and other application records were absent. The reference rows
(including the installation's default school and configuration) remain because
they are part of a clean migration's required state. Standard `migrate:fresh`
restores this baseline in 12 seconds and still runs pending migrations, normal
test seeders and assertions. The snapshot is excluded locally and is not a
repository deliverable. A full Academic Competitions smoke run then passed
13 tests and 268 assertions in 48 seconds. Earlier complete migration runs of
the same suite also passed. Runtime evidence is in
`/runtime/schema-verification.json` and `/runtime/schema-smoke.log`.

Private-document browser checks use a local ClamAV protocol fixture reached
through the unchanged `UploadScanner` adapter. It accepts only the two exact
synthetic PDF payloads used by the worksheet and private-profile tests, the
gallery's exact synthetic PNG, and its real `SafeImages` JPEG/thumbnail hashes.
It refuses every other byte sequence. Original/sanitized/thumbnail acceptance
and unknown-data refusal were verified.
Its loopback service and configuration are ignored local runtime files; no
production binding or fail-closed rule was changed. This qualifies the upload,
private storage, download and removal workflows, not a deployed antivirus
service or its signature database.

## Commands

Domain PHPUnit example (replace the database suffix with the assigned domain):

```sh
docker exec -e APP_DEBUG=false -e DB_HOST=ui-rework-mysql-ram \
  -e DB_DATABASE=ui_rework_people_test \
  ui-rework-php php -d memory_limit=1G artisan test --filter=ExampleTest
```

Browser checks use the real shipped assets, system Chromium, and the repository
Playwright dependency versions. The local PHP wrapper passes fixture commands
to the isolated container and forwards only explicitly named synthetic
`*_FIXTURE_*` controls; it is a workspace runtime convenience, not part of the
application. Logs from long PHP commands are redirected inside the container
to `/runtime/` (mounted at `/workspace/runtime/admin-ui`) so a host reconnect
does not lose their result.

```sh
cd admin-panel/e2e
PATH=/workspace/runtime/admin-ui/bin:$PATH \
  E2E_BASE_URL=http://127.0.0.1:8092 \
  E2E_CHROMIUM_EXECUTABLE=/usr/bin/chromium \
  npx playwright test --config=playwright.partition.config.ts --project=flows \
  tests/shared-form-recovery.spec.ts tests/admin-revision3.spec.ts tests/navigation.spec.ts
```

`playwright.prepare.config.ts` prepares authentication and the source-discovered
page manifest first. New regression files are included in the existing `flows`
project, so the CI partition's exhaustive-discovery check still includes them.

## Recorded evidence

| Check | Result |
|---|---|
| Final integrated Unit suite | 331 tests, 15,714 assertions; one existing skip |
| Full Pint | 3,365 files passed on the final source |
| Shared HTTP and workspace boundaries | 15 tests, 38,582 assertions passed; covers 48 new route boundaries |
| Personal notification endpoints | 6 tests, 39 assertions passed |
| Blade compile and PHP token parsing | 698 templates compile; PHP token parsing and uncompiled-directive assertions pass |
| Isolated configuration, route and view caches | Passed |
| Standard PHPStan; Finance level 5 | Zero findings |
| Shared form recovery, confirmation and CSRF refresh browser regressions | 20 passed in 28.1 seconds |
| Shared table recovery and stale selection browser regressions | 2 passed (parent verification) |
| Material/page components; compiled Blade acceptance | 12 tests/70 assertions; 5/40; 1/32,995 passed after asset versioning (parent verification) |
| Final shared component, Blade, navigation freshness, route boundary and translation batch | 26 tests, 72,336 assertions passed in 108 seconds (parent verification) |
| Academic competitions API/domain/panel regression | 13 tests, 268 assertions passed |
| People/Academics targeted qualification | 82 PHP cases, 37 browser workflows and 46 strict page checks passed; per-run assertions and evidence in the domain ledger |
| Awards/activities/discipline feature batch | 36 tests, 479 assertions passed; remaining 11 activity/file/fixture/discipline/chat browser workflows and final discipline follow-up passed |
| Community/Home/Settings targeted qualification | 95 PHP cases, 25 browser workflows and six strict page checks passed; domain ledger retains exact runs |
| Finance and ticket feature batch | 23 tests, 305 assertions passed |
| Finance family billing, receipts, giving/bursaries and settlement browser workflows | 8 passed at 390/1280 px; five ticket workflows and two final ticket presentation checks passed |
| Complete responsive shell, navigation, native context cancellation and populated tables | 95 passed across 320/375/768/1024/1280 px; two diagnosed failures passed targeted final reruns |
| Classroom and global year activation/restoration | Classroom 3/3 at 390/820/1280 px; wizard 1/1; original year and module state independently verified |
| Recognition: house points and Golden Boot creation/nomination/award history | 4 browser workflows passed at 390/1280 px |
| Mobile global Search and language utilities | 2 passed at 320/375 px: actual language change/restore, all module links, drawer Axe/overflow, learner search to report |
| Final Blade, translations and navigation rendering batch | 8 tests, 72,059 assertions passed on final source |
| Global search and shrinking Bootstrap baseline | 9 tests, 45 assertions passed |
| Persistence badge dedicated configuration and protected writes | 1 test, 14 assertions passed |
| Notification list/chooser and default-value form controls | 2 notification workflows; 2 strict field pages and both mobile/desktop option add/remove proofs passed |
| Role and staff-attendance page corrections | 4 strict phone/desktop page checks passed; source record actions and filters retained |
| TypeScript compilation | Passed after final mobile utility tests |
| CI Playwright partition inventory | Full suite ancestry retained; 1,241 tests complete/disjoint (310 per bulk shard and one wizard) |
| Generated Dart API contracts | 193 tests passed; analyzer zero errors/warnings (parent verification logs) |
| Expanded source/authorized-task discovery | 434 URLs, 255 panel screens; zero server/network failures after explicit retries |
| Complete 375/1280 px page sweeps, first pass | 232/258 and 233/258 passed; 26/25 source defects retained in the initial reports, zero skipped/flaky |
| Consolidated strict correction rerun | 56/56 passed in 2.7 minutes; covers all 27 affected routes at both widths plus the subject-allocation page |
| Reconciled complete page acceptance | All 516 checks qualified: 255 screens at 375/1280 plus six inventory checks; all 51 initial failures have passing replacements, zero unresolved/skipped/flaky |

### Broad sweep corrections

The strict sweep retains the initial failures; these source fixes have separate
targeted passing evidence and a final integrated 56-check correction run.
`/workspace/artifacts/ui-rework-final/page-verification.json` reconciles every
initial result by route and viewport with its qualified replacement; all 51
initial failures are resolved. The original full reports are retained as
`/runtime/pages-375-final.json` and `/runtime/pages-1280-final.json`, and the final
corrections as `/runtime/pages-corrections-final.json`. No accessibility exceptions were added. All obsolete accessibility exceptions
were removed after their live screens passed strict checks; the two retired fee
paths redirect to the already-qualified fee-plan and payment screens. The final
allowlist has no accessibility, console or scroll exemptions.

| Page | Initial finding | Qualified correction |
|---|---|---|
| `/bank-statements/new` | File input imposed 24px/306px page overflow at 375/1280 | Both page checks passed; keyboard file chooser and filename verified at 320/375/1280 (`bank-overflow-qualified.log`, `bank-file-picker-320-qualified.log`) |
| `/class` | Three filters and controller-generated icon links lacked names | Strict 375/1024/1280 checks passed; learner form also rechecked (`people-labels.log`) |
| `/chat-settings` | Delete button lacked a name | Visible action label; strict 375/1280 checks passed (`settings-strict-final.log`) |
| `/content` | Website wizard step numbers had low contrast | Material foreground/background tokens; strict 375/1280 checks passed (`settings-strict-final.log`) |
| `/email-tamplate` | Invalid tab/list roles | Correct tab relationships and keyboard behavior; strict 375/1280 checks passed (`settings-strict-final.log`) |
| `/exam-timetable`, `/exams/view-result` | Unnamed selectors and repeat-row action | Both pages passed at 375/1280; actual row add/remove and independent values verified (`exam-labels.log`) |
| `/form-fields` | Unnamed type/target controls and dynamic option fields | Strict pages and actual option add/remove passed at 375/1280 (`form-fields-qualified.log`, `form-field-options-proof.log`) |
| `/generate-id`, `/generate_staff_id`, `/generate_teacher_id` | Unnamed selectors; missing staff image requested `/storage` | Named controls and explicit empty-image fallback; strict pages passed at 375/1280 (`people-final-controls.log` and preceding identity batch) |
| `/grades` | Unnamed repeated grade fields and remove buttons | Strict pages and actual clone/remove with retained existing record IDs passed at 375/1280 |
| `/id-card-settings` | Scrolling preview could not receive keyboard focus | Named focusable preview retains scaling; strict 375/1280 passed (`id-card-accessibility-final.log`) |
| `/medium`, `/online-registration`, `/peer-tutoring/measure` | Unnamed delete/filter controls and low-contrast caption | All strict 375/1280 checks passed (`people-final-controls.log`) |
| `/promote-student` | Status filter lacked a name | Strict 375/1280 passed (`people-ci-followup.log`) |
| `/roles` | Icon-only edit/delete actions lacked names | Strict 375/1280 passed (`roles-qualified.log`) |
| `/section`, `/shifts`, `/subject` | Unnamed actions/time fields; missing subject image requested `/storage` | Reference directory strict checks and explicit shift/subject retries passed at 375/1280 (`school-reference-labels.log` and targeted follow-ups) |
| `/staff-attendance` | Unnamed date/view controls and low-contrast status badges | Strict 375/1280 passed (`staff-attendance-qualified.log`) |
| `/student/assign-roll-number`, `/students`, `/students-list`, `/students/create_bulk` | Filters lacked accessible names | Eight strict checks passed at 375/1280 (`learner-list-labels.log`) |
| `/timetable` | Select2 left a focusable control marked hidden | Strict 375/1280 passed (`timetable-labels-final.log`) |


The shared-form checks cover cleared checkbox/multiselect restoration,
draft isolation by user/school/year/form scope, excluded sensitive fields,
field-level 422 validation, 401 and 419 expiry, 409 conflicts, an unconfirmed
network outcome without automatic retry, and suppression of duplicate in-flight
submissions. The expanded checks also exercise confirmed native and enhanced
submissions through click, Enter and `requestSubmit`, preserving the named
submitter and submitting exactly once after confirmation. A confirmed custom action button
does not submit its parent form. Independent GET/POST submissions preserve
another native form’s unsaved guard and do not set a global bypass. They load the shipped `page.js` and Material 3 assets; only the
isolated fixture page and write responses are intercepted. No real application
endpoint is added for testing.

The responsive module suite checks every requested navigation destination at
320, 375, 768 and 1280 CSS pixels, including page overflow and serious/critical
axe findings. It also checks learner-form control bounds, the global year
selector, mobile drawer keyboard recovery, native dialog bounds and focus,
personal preferences and inbox, desktop sidebar non-overlap, and selected-tab
contrast. Real school/year cancellation checks retain unsaved native learner
input without posting or persisting private field values. The permanent
`pages-375` project applies the source-discovered page sweep to mobile as well
as the existing desktop widths. Optional modules are explicitly
enabled only by a guarded test fixture; production defaults remain unchanged.

## Request performance and final visual evidence

A real authenticated document profile initially recorded 6,023 SQL queries and
24.23 seconds. Request-local navigation presentation reuse and equivalent
early-exit Gallery/Family availability checks reduced the same workspace to
502 queries and 2.95 seconds. Public authorization methods, writes and other
actor contexts remain fresh; dedicated freshness and authority-boundary tests
passed. Runtime profiles retain the original and final measurements.

Final Home, People, Awards and Finance screenshots are stored at
`/workspace/artifacts/ui-rework-final/`, at 320, 375 and 1280 pixels. All twelve
rendered with zero page overflow; desktop content starts after the 260-pixel
sidebar. Screenshots disable transient layout animations. Awards screenshots
were refreshed after the final heading-size correction. Mobile utility drawer
screenshots (`drawer-320.png`, `drawer-375.png`) were captured and visually
reviewed after the actual language and search workflows passed. The drawer keeps
year, language, Search and close controls visible while its twelve modules scroll.

## Interpretation limits

Source coverage, browser UI verification, backend domain tests, staging
acceptance and production rollout are separate evidence. Tests run during
concurrent implementation can observe intermediate code or a migration list
captured before a dependency arrived; these runs are not final acceptance.
Record successful reruns after integration and retain any unresolved failures.
The full-page acceptance sweep uses `E2E_STRICT_ACCESSIBILITY=1`, so existing
legacy exceptions cannot conceal remaining label or contrast findings.
The first discovery pass had five request timeouts under concurrent load; their
explicit successful retries are retained in the runtime artifacts. The final
status inventory also records 43 permission refusals, primarily SaaS/platform
and role-private endpoints, plus redirects and parameter/state requirements.
These responses are not claimed as administrator page coverage. Separate
domain fixtures exercise their authorized school-role workflows.
No live payment provider, private Build Bible acceptance, production rollout,
or native mobile device qualification is implied by this local verification.
