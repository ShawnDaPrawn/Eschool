# Platform admin area (/saas): self-test record

Run on 26 October 2026 against [the spec](../specs/platform/saas-panel.md), on MariaDB 10.11 (the version CI uses) and PHP 8.3.6. Nothing here is a staging, acceptance or production result.

## Original author verification

| Check | Result |
| --- | --- |
| `phpunit tests/Feature/Platform/SaasPanelTest.php` | 22 tests, 181 assertions, passed |
| Related suites: `tests/Feature/Platform`, `tests/Unit`, `Panel`, `Authorization`, `Contracts`, `RoutesTest`, `PrivateRouteReachabilityTest`, `AdminAuthorizationTest`, `AccountSecurityTest`, `Ai/AiOperationsTest` | 1,120 tests, 7,321 assertions, passed, 1 skipped (not one of the new tests) |
| Deliberate breakage 1: remove the grant check so identity alone opens `/saas` | 3 of the new tests failed, as they should |
| Deliberate breakage 2: send module switches to school 1 instead of the chosen school | 2 of the new tests failed, as they should |
| `pint --test` on every changed or new file | passed |
| `composer validate`, `route:list`, `config:cache`, `route:cache`, `view:cache`, shard-partition unit test | passed |
| `migrate` on an empty database | passed, including `create_platform_admin_grants` |

## In a browser (Chromium, a local server on a separate database)

Signed in as an operator with identity and grant: list, set-up form, one-time password page, school page, suspend and restore with a reason, module switch off and on. No browser console errors; `Cache-Control: no-store, private` on the pages; no sideways scrolling at 390 px. Screenshots were reviewed by the author only.

## Original submission limits

- **Larastan** (`phpstan analyse`): the sandbox cannot download `phpstan` (its zip comes from `api.github.com`, which the sandbox blocks). Level 1 is configured and the new code follows the patterns of existing classes; CI is the first real run.
- **The rest of the PHP suite** (the other CI shards) and the Flutter, Playwright and OpenAPI jobs: not touched by this change and not run here. CI covers them.
- **Staging or production:** no deploy was made. The migration, the console commands and the first real school set-up need a staging run.
- **Mail:** none is sent; the first administrator's one-time password is shown on screen instead.

## Integration review — 26 October 2026

Frozen input: original PR #301 head `3f1004dfd856d69109ef1639cac754e0fd4f269e`, base `ecbef3e044243e9adfce6bbb859fc5eec85b7969`. Review ran on the qualified final integration `b81326839988c201b90c28e3d128e2960b4784b2` (public equivalent `93250cc1d8ad938d9ba0a7da8630fd5631b5a81c`, identical tree) plus that original head, on PHP 8.3.36 and an isolated Phase 4 MySQL test database. No production account, operator grant, school or paid service was created.

Every original changed file was read, with supporting login, tenancy, role/identity, private-response, audit and module code. This is a manual scoped review with automated regressions, not a sealed repository-wide security scan. The plugin's preflight/reference tooling was unavailable in this host.

Five real regressions were reproduced before correction:

1. A valid role-free global operator was rejected by actual POST `/login`; the original tests only used `actingAs`. Fresh identity plus grant now reaches `/saas`, without a school role or membership. Missing, revoked, expired, inactive, default-password and password-pending cases remain refused.
2. A package downgrade hid retained payments. The existing money-retention transition now keeps those records read-only. The actual school owner's authenticated fee-history endpoint still answers 200; unentitled new Core schools without history still answer 404.
3. A new administrator completed the forced password change but Home returned 500 because no year existed. Home and its entitled AJAX readers now handle an absent year without inventing dates or a school year.
4. The counts included future/ended memberships and disabled people. Aggregates now apply the school's calendar and active school/account eligibility, without selecting names or personal content.
5. An expired UTC grant became valid under an applied Honolulu timezone. UTC storage/comparison now rejects it; valid grants also survive a positive-offset Kiritimati context.

| Reviewer check | Actual result |
| --- | --- |
| Original owned suite, before corrections | 22 tests / 181 assertions passed |
| Final `SaasPanelTest` | 28 tests / 246 assertions passed |
| Related verification, disjoint cohorts | First 280 Platform cases passed; corrected-cookie Portal 8 cases passed; remaining related cohort 999 cases / 6,945 assertions passed with the existing real-ClamAV-daemon test skipped. This covers 1,287 related cases, including Platform, Unit, Panel, Authorization, Contracts, routes, account security and AI operations. The first partial attempt exposed missing inputs in a sparse checkout; verification used the complete tracked checkout. A later isolated runner cookie-name mismatch was corrected to the repository's `e_school_session`, and Portal was rerun successfully. |
| Actual HTTP purpose boundaries | 40 cases / 295 assertions passed: owned SaaS, existing clinical exclusions and real-server `AdminPagesTest`. `/saas` and `/saas/schools/create` explicitly require 403 for ordinary school administrators. |
| Real Chromium at 390, 820 and 1280 px | All 3 passed in 32.7 seconds: role-free operator sign-in, school creation with private POST-only password display, separate-cookie first administrator forced password replacement and empty-year Home, school-admin denial, fresh grant revocation, no own-origin 500/page errors, axe serious/critical and horizontal overflow checks. |
| Static/style/browser source | Changed production source Larastan: 0 errors; full Pint and Playwright TypeScript passed. |

Reproduction is checked in at `admin-panel/tests/Feature/Platform/SaasPanelTest.php` and `admin-panel/e2e/tests/saas-panel.spec.ts`; the new browser fixture refuses non-local/non-test databases and never gives the synthetic operator a school membership. The flow is included in the existing serial Playwright project. No API schema or generated SDK changed.

Full hosted CI, staging, production deployment and real operator provisioning remain separate. The initially described future platform tools remain outside this first version; this review does not claim they were built.
