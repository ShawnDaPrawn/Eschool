# Test infrastructure (Phase 0, feature 0.13)

Build Bible section 5.2 and section 6, build step 13. Shared by streams S7 (the route suites and the API contract), S3 (`DemoSchoolSeeder`, Playwright) and S4 (k6). This file holds S7's part; the other streams add theirs as their own sections.

## S7: Tenancy, Authorization and Contracts suites

### Before

API routes were checked by hand-picked tests (`AccessControlTest`, the `*ApiSecurityTest` suites): a new route was covered only if someone remembered to add it. Nothing walked every route, and nothing checked any response against a contract (`ApiContractTest` compares v1 shapes with snapshots).

### After

| Suite | File | What it checks | Speed |
| --- | --- | --- | --- |
| Tenancy | `admin-panel/tests/Feature/Tenancy/ApiRouteTenancyTest.php` | one case per API route and method (v1 and v2) from the router: exists, needs a token (401 without) unless on `PUBLIC` with a reason, sits in a role group unless on `ANY_PROFILE`; stale list entries fail | 162 cases, about 8 s, no database |
| Authorization | `admin-panel/tests/Feature/Authorization/RoleMatrixTest.php` | every role-grouped route against Student, Parent, Teacher, Super Admin, a school-created role and no role: each profile outside the group gets 401 or 403; a role in the database missing from the matrix fails | 706 requests, under 10 s, one DemoSchool |
| Contracts | `admin-panel/tests/Feature/Contracts/OpenApiContractTest.php` | every v2 route has a spec operation and a scenario, every operation a route; every scenario's response validates against `openapi.yaml`; the Dart client's fixture validates; broken responses are caught | about 8 s, one DemoSchool |

Support: `tests/Support/ApiRoute.php` (routes from the router, role groups), `tests/Support/OpenApiSpec.php` (the spec and response validation with `league/openapi-psr7-validator`), `tests/Concerns/UsesDemoSchool.php` (one DemoSchool per class in a fresh database, each test in a rolled-back transaction), `tests/Concerns/ValidatesOpenApiResponses.php` (every v2 response a test receives is validated). `ApiContractTest` now covers v2 too.

The DemoSchool is `tests/Support/DemoSchool` (the branch had no `DemoSchoolSeeder`); when S3's seeder lands, `UsesDemoSchool` can build from it instead, and Phase 1's second school turns the tenancy cases into real cross-school checks.

### User stories

- **Test role:** I run three suites and know every API route needs a token, sits in a role group, refuses every wrong role, and (for v2) answers what the contract says.
- **Engineer adding a route:** the suites find my route themselves; they fail and tell me what is missing (a role group, a spec operation, a scenario).
- **Phase 1 engineer:** I add School B and replace `assertIsolated()`; every route already has its case.

### Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| Every API route | the Tenancy suite runs | each case passes: token needed or listed as public, role group or listed as any-profile | `ApiRouteTenancyTest::test_route_is_guarded` |
| The public and any-profile lists | they name a route that is gone | the suite fails | `ApiRouteTenancyTest::test_the_lists_only_name_routes_that_exist` |
| Each profile | it calls every role-grouped route outside its group | 401 or 403 every time | `RoleMatrixTest::test_the_wrong_profile_is_refused_on_every_role_grouped_route` |
| A new role in the database | the matrix runs | it fails until the role is in `profiles()` | `RoleMatrixTest::test_the_matrix_knows_every_role` |
| Every v2 route | the Contracts suite runs | it is in the spec and has a scenario; its responses validate | `OpenApiContractTest` |
| A response with an extra field, a missing field, a wrong type or an undocumented status | it is validated | it fails | `OpenApiContractTest::test_a_response_that_breaks_the_spec_fails` |
| The whole panel suite | CI runs | green | CI `admin-panel` job |

### Data changes, endpoints, screens, notifications, module switch

None; tests only. Dev dependency `league/openapi-psr7-validator` ^0.24 (installed from source).

### Security notes

These two suites are the cross-school and authorisation checks of each phase's security gate (the Build Bible, section 5.1). Phase 0 asserts authentication and role groups on every route; the cross-school assertion arrives with the second school in Phase 1.

### Self-test steps

1. `php artisan test --filter='ApiRouteTenancyTest|RoleMatrixTest|OpenApiContractTest'`: green.
2. Add a route to `routes/api.php` without middleware and rerun: the Tenancy suite fails naming it. Remove it.
3. Add a v2 route without describing it in `openapi.yaml`: the Contracts suite fails. Remove it.
