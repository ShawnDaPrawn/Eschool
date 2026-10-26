# Platform admin area (/saas): self-test record

Run on 26 October 2026 against [the spec](../specs/platform/saas-panel.md), on MariaDB 10.11 (the version CI uses) and PHP 8.3.6. Nothing here is a staging, acceptance or production result.

## Automated

| Check | Result |
| --- | --- |
| `phpunit tests/Feature/Platform/SaasPanelTest.php` | 28 tests, 225 assertions, passed |
| Related suites: `tests/Feature/Platform`, `tests/Unit`, `Panel`, `Authorization`, `Contracts`, `Facilitators`, `RoutesTest`, `PrivateRouteReachabilityTest`, `AdminAuthorizationTest`, `AccountSecurityTest`, `AccessControlTest`, `SecurityTest`, `AdminRoleTest`, `PermissionsTest`, `Ai/AiOperationsTest` (the login controller was changed, so the sign-in suites are included) | 1,175 tests, passed, 1 skipped (not one of the new tests). Two installer tests failed once because a `storage/installed` file left by the browser check existed; they pass without it |
| Deliberate breakage 1: remove the grant check so identity alone opens `/saas` | 3 of the new tests failed, as they should |
| Deliberate breakage 2: send module switches to school 1 instead of the chosen school | 2 of the new tests failed, as they should |
| Deliberate breakage 3, 4, 5: sign-in ignores operators; no first-run defaults for a new school; no read-only step on a downgrade | each made its own new tests fail, as they should |
| `pint --test` on every changed or new file | passed |
| `composer validate`, `route:list`, `config:cache`, `route:cache`, `view:cache`, shard-partition unit test | passed |
| `migrate` on an empty database | passed, including `create_platform_admin_grants` |

## Found in review and fixed

Codex's review of the pull request found three real gaps that the first tests did not cover: a school set up from `/saas` had no current year or settings, so its administrator's first `/home` returned a 500; a downgrade to a package without fees hid fee records that should stay readable; and an operator with no school role was logged straight back out at sign-in. Each now has a test that fails without its fix.

## In a browser (Chromium, a local server on a separate database)

Signed in as an operator with identity and grant: list, set-up form, one-time password page, school page, suspend and restore with a reason, module switch off and on. No browser console errors; `Cache-Control: no-store, private` on the pages; no sideways scrolling at 390 px. Screenshots were reviewed by the author only.

## Not run, and why

- **Larastan** (`phpstan analyse`): the sandbox cannot download `phpstan` (its zip comes from `api.github.com`, which the sandbox blocks). Level 1 is configured and the new code follows the patterns of existing classes; CI is the first real run.
- **The rest of the PHP suite** (the other CI shards) and the Flutter, Playwright and OpenAPI jobs: not touched by this change and not run here. CI covers them.
- **Staging or production:** no deploy was made. The migration, the console commands and the first real school set-up need a staging run.
- **Mail:** none is sent; the first administrator's one-time password is shown on screen instead.
