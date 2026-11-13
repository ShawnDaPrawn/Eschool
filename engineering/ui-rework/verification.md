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
synthetic PDF payloads used by the worksheet and private-profile tests and
refuses every other byte sequence. Both acceptance and refusal were verified.
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
| Full Pint | 3,364 files passed after integration |
| Shared HTTP and workspace boundaries | 15 tests, 38,582 assertions passed; covers 48 new route boundaries |
| Personal notification endpoints | 6 tests, 39 assertions passed |
| Blade compile and PHP token parsing | 694 templates, zero syntax errors |
| Isolated configuration, route and view caches | Passed |
| Standard PHPStan; Finance level 5 | Zero findings |
| Shared form recovery, confirmation and CSRF refresh browser regressions | 20 passed in 28.1 seconds |
| Shared table recovery and stale selection browser regressions | 2 passed (parent verification) |
| Material/page components; compiled Blade acceptance | 12 tests/70 assertions; 5/40; 1/32,726 passed (parent verification) |
| Final shared component, Blade, navigation freshness, route boundary and translation batch | 26 tests, 72,336 assertions passed in 108 seconds (parent verification) |
| Academic competitions API/domain/panel regression | 13 tests, 268 assertions passed |
| People/Academics feature batch | 55 tests, 701 assertions passed; additional workspace-mode checks tracked by domain |
| Awards/activities/discipline feature batch | 36 tests, 479 assertions passed |
| Finance and ticket feature batch | 23 tests, 305 assertions passed |
| Finance family billing, receipts, giving/bursaries and settlement browser workflows | 8 passed at 390/1280 px; ticket workflow checks tracked separately |
| Complete responsive shell, navigation, native context cancellation and populated tables | 95 passed across 320/375/768/1024/1280 px; two diagnosed failures passed targeted final reruns |
| TypeScript compilation | Passed after final shared form tests |
| CI Playwright partition inventory | Full suite ancestry retained; 1,139 tests complete/disjoint before final expanded page manifest |
| Generated Dart API contracts | 193 tests passed; analyzer zero errors/warnings (parent verification logs) |
| Expanded source/authorized-task discovery | 434 URLs, 255 panel screens; zero server/network failures after explicit retries |
| Final complete 375/1280 px page sweeps | 516 checks in progress; do not treat this row as passed |

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
