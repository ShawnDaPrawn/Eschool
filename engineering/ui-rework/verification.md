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
| Full Pint | 3,366 files passed after the final configuration-return follow-up |
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
| Final Blade, translations and navigation rendering batch | 8 tests, 72,072 assertions passed after the final configuration-return follow-up |
| Global search and shrinking Bootstrap baseline | 9 tests, 45 assertions passed |
| Persistence badge dedicated configuration and protected writes | 1 test, 14 assertions passed |
| Notification list/chooser and default-value form controls | 2 notification workflows; 2 strict field pages and both mobile/desktop option add/remove proofs passed |
| Role and staff-attendance page corrections | 4 strict phone/desktop page checks passed; source record actions and filters retained |
| TypeScript compilation | Passed after final mobile utility tests |
| CI Playwright partition inventory | 1,242 tests complete/disjoint after balancing: flows 114/114/112/112, one full viewport in parts 1–3, role/retired checks in part 4, isolated wizard |
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

## Balanced CI execution

The four isolated browser parts now each receive a native shard of the slower
workflow project. Parts 1–3 first run the complete 1280/1024/375 page project;
part 4 first runs teacher and retired-route checks. Each part's commands run
sequentially, retain the workflow project's single worker and preserve native
serial groups. A failure in either command makes the part fail while allowing
its remaining checks to run. Separate report/result subdirectories keep earlier
failure evidence intact. The wizard retains its separate schema and no-dependency
command.

Real prepared Playwright discovery verified 1,242 tests with no missing or
repeated identities, both within each part and across all five parts. The four
workflow shards contain 114, 114, 112 and 112 tests. Tool orchestration tests cover
failure aggregation, continued execution, artifact separation, overlap rejection
and complete coverage; all five CI-tool unit tests pass. Python syntax,
TypeScript and diff checks pass. This change qualifies distribution and coverage;
its actual hosted elapsed-time improvement remains a CI measurement.

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


## Configuration save destinations

The final whole-suite CI pass exposed older assertions for controls that now
live in dedicated task states. Those assertions now follow the real links and
retain their original writes, role/school restrictions, privacy and persistence
checks. This follow-up also audited successful-save destinations across every
owner, correcting actual stale handoffs while keeping domain services and API
responses unchanged.

- Fresh-install route/purpose coverage: AdminPagesTest **3 tests / 62 assertions passed**, including an optional office list denied while Groups is off, opening when explicitly enabled, and continued refusal of owner-only facilitator and private-staff purposes to the administrator.
- Short-day planning/restoration: **11 tests / 95 assertions passed**; persistence motivation: **14 / 472**; emergency/support tasks: **25 / 600**.
- Finance configuration returns: **180 tests / 1,465 assertions passed**, six spending/funding/child-return browser journeys and four withdrawal/authority cases passed. Twelve save destinations and three links retain their actual settings, account/year or vendor context. Money services, revisions, idempotency and permissions are unchanged.
- People configuration returns: six focused PHP cases and two browser workflows passed, including selected area/room/library editors and class-grade configuration. Intended creation-to-catalogue and scheduling-to-history handoffs remain.
- Leave, templates and classroom returns: **4 tests / 53 assertions** and two actual browser workflows passed. A local Leave configuration-year filter does not change the global viewed or current year. Classroom's optional panel-only return page is stripped before service execution and remains rejected by the strict API. Private one-time credential result pages are preserved.

Exact source changes and logs are recorded in each owner ledger. The successful
page sweep remains the complete layout/accessibility evidence; these additional
checks qualify the affected write-to-destination interactions.

## Final full-suite browser corrections

The completed final browser shard exposed deeper task interactions beyond the
read-only page sweep. The corrected chains were executed locally with their
original write, authorization, privacy, accessibility and persisted-result
assertions intact:

- Events, family visits and school-card reader setup: all nine serial cases
  passed. Programme save waits for the new document; a held-save regression
  additionally proves competing row actions are disabled, cancelling never
  deletes, and confirmation deletes exactly once. Only the programme domain
  script changed; shared form behavior was preserved.
- Peer tutoring and question authoring: five cases passed with retries disabled.
  The peer fixture reuses only its exact pending rehearsal, preventing an
  interrupted prior attempt from generating a legitimate scheduling collision.
  Question setup is atomic and uses a fresh worker namespace; captured Artisan
  output now survives any failure. The historical first-attempt exception was
  absent from the CI artifact and is not assigned a speculative cause.
- Relief cover: both complete serial cases passed; the full eleven-case Teacher
  suite passed. Tests follow the actual Request cover/My assigned lessons and
  workspace links, preserving every direct-route refusal and private substitute
  assertion.
- Wallet readers and fee lifecycle: eighteen backend tests/229 assertions and all
  nine lifecycle/reader browser cases passed. The panel retains UUID reader IDs,
  refuses malformed/foreign selections, and uses canonical GET links after
  revocation without exposing credentials. Calculation-definition labels use
  the on-surface token on shaded detail rows. Money services and permissions
  remain unchanged.

The original full CI logs are retained separately from these passing corrective
runs. CI allocation now pairs a complete viewport sweep with a serial workflow
shard, preserves independent schemas and per-command failure artifacts, and
verifies all 1,242 tests exactly once. The obsolete separate leadership preflight
was removed because that test remains in its verified workflow partition; this
also prevents a second global setup from changing discovery midway through CI.
