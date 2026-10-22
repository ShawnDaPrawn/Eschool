# API v2 skeleton (Phase 0, feature 0.8)

Build Bible section 3.3, 5.5 and section 6, build step 8. Work stream S7. No user-visible change: the installed apps keep calling v1. Underneath, new work has a home: `/api/v2` with the same envelope, real HTTP statuses, role groups, rate limits, idempotent writes, `GET /api/v2/me/context`, an OpenAPI 3.1 contract that CI lints and tests enforce, and a Dart client generated from it.

## Before

- Every endpoint lived in `routes/api.php` (v1, 159 routes) with hand-written validation and payloads, no contract and HTTP 200 on business errors.
- The apps learned who was signed in from each login answer and per-role profile calls; nothing told them the school's branding, timezone, terms, switched-on modules or a parent's children in one place.
- No idempotency on any write; limits were `api` (60 a minute per user), `login` and `heavy` only.

## After

| Piece | Where |
| --- | --- |
| Route file | `admin-panel/routes/api_v2.php`, loaded by `bootstrap/app.php` (`withRouting(then:)`) under `/api/v2` with the `api` middleware group |
| Role groups | every profile (`auth:sanctum`, `role.any`), `student/*`, `parent/*`, `teacher/*` (as v1), `staff/*` (`staff`: any role but Parent and Student), `auth/*` (`throttle:auth`), nested money groups (`throttle:money`, `idempotent:required`) |
| Middleware | `App\Http\Middleware\EnsureUserHasAnyRole` (`role.any`), `EnsureUserIsStaff` (`staff`), `HandleIdempotencyKey` (`idempotent`, `idempotent:required`) |
| Errors | `App\Exceptions\ApiV2ExceptionRenderer`: every exception under `/api/v2` in the envelope with its real status and a string code; v1 untouched |
| Limiters | `auth` (5 a minute per account, 20 per address) and `money` (30 a minute per token) in `RouteServiceProvider::configureApiV2RateLimiting()`; `api` (60 a minute per user) from the group, as before |
| `/me/context` | `Api/V2/Platform/ContextController`, `Services/Platform/ContextService`, `Services/Platform/SchoolProfile`, `Http/Resources/Platform/*` |
| Idempotency | `idempotency_keys` table, `Models/Platform/IdempotencyKey` (prunable), `Services/Platform/IdempotencyService`, `IdempotencyOutcome` |
| Contract | `admin-panel/openapi.yaml` (OpenAPI 3.1), `admin-panel/redocly.yaml`, CI job "OpenAPI contract (lint)" |
| Dart client | `packages/eschool_api` (dart-dio + json_serializable from openapi-generator 7.25.0), `tool/generate.sh`, CI matrix leg that regenerates and fails on a diff |

## User stories

- **Learner, parent, teacher (through the apps, once S2 switches `eschool_session` over):** on launch the app reads one answer with who I am, my school's name, crest, colours, timezone and terms, and, as a parent, my children with each child's school.
- **School admin and staff (web portals, later phases):** the same context through Sanctum's cookie mode.
- **Engineer:** I add an endpoint on v2 by the domain recipe; the route lands in a role group, errors are exceptions rendered in the envelope, the contract tells me (and CI) if my answer drifts, and the Dart client regenerates from the spec.
- **Product owner:** nothing the users see changes, and v1 keeps answering exactly as before.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| No token | `GET /api/v2/me/context` | 401 `{error: true, code: "unauthenticated", data: null}` | `ContextTest::test_it_needs_a_token` |
| A learner | they read the context | their user, roles `["Student"]`, school 1 with name, timezone, current year and its terms (one `current`), empty modules, rules and flags (`{}`), `["password"]`, no children | `ContextTest::test_a_learner_gets_their_user_their_school_and_no_children` |
| A parent of two | they read the context | both children, each with its school | `ContextTest::test_a_parent_gets_their_own_children_each_with_their_school` |
| A guardian and a mother of different children | they read the context | only their own child each (other user's record) | `ContextTest::test_a_parent_never_gets_another_familys_child` |
| A teacher, the Super Admin, a school-created role | they read the context | 200 with their roles and no children | `ContextTest::test_teachers_and_staff_get_their_context_without_children` |
| An account with no role | it reads the context | 403 `forbidden` (wrong role) | `ContextTest::test_an_account_with_no_role_is_refused` |
| Branding settings | the context is read | short name, tagline, crest URL and both colours exactly as stored | `ContextTest::test_branding_is_passed_on_as_the_school_stored_it` |
| No current year set | the context is read | `session_year: null` | `ContextTest::test_a_school_without_a_current_year_still_answers` |
| A parent with one child and one with two | the context is read | the same number of queries, at most 10 (7 for a teacher) | `ContextTest::test_it_takes_a_fixed_handful_of_queries` |
| Any v2 route | it is registered | it is under `/api/v2` with the `api` group | `ApiV2GroundRulesTest` |
| The staff group | Student, Parent, Teacher, Super Admin, a school role call it | 403, 403, 200, 200, 200 | `ApiV2GroundRulesTest` |
| v2 errors | 401, 404, 405, validation, a bug | envelope with the right status and code; validation lists every field; no exception text | `ApiV2GroundRulesTest` |
| v1 errors | an unauthenticated v1 call | still `{"message": "Unauthenticated."}` | `ApiV2GroundRulesTest` |
| `auth` limiter | 6 sign-ins for one account in a minute; 21 from one address | the 6th and the 21st get 429 with `Retry-After` | `ApiV2GroundRulesTest` |
| `money` limiter | 31 money writes with one token | the 31st gets 429; another token of the same user still works | `ApiV2GroundRulesTest` |
| A write with an Idempotency-Key | it is sent twice | the work runs once, the second answer is identical with `Idempotent-Replayed: true` | `IdempotencyKeyTest` |
| The same key, another payload | it is sent | 422 `idempotency_key_reused`, the work does not run | `IdempotencyKeyTest` |
| A key whose first request is still running | it is sent again | 409; after 5 minutes without an answer the key is free | `IdempotencyKeyTest` |
| Another user, the same key | they send it | their own request runs | `IdempotencyKeyTest` |
| 25 hours later | the key is reused | it runs again; `model:prune` deletes expired rows | `IdempotencyKeyTest` |
| A money write without a key | it is sent | 400 `idempotency_key_missing` | `IdempotencyKeyTest` |
| The migration | rolled back and run again | the table goes and comes back | `IdempotencyKeysMigrationTest` |
| Every v2 route | the Contracts suite runs | each has a spec operation and a scenario; each scenario's answer validates against the spec | `OpenApiContractTest` |
| `openapi.yaml` | CI lints it | no error or warning (`recommended-strict`) | CI "OpenAPI contract (lint)" |
| `packages/eschool_api` | CI regenerates it | no difference from what is committed; its tests decode the context and errors | CI matrix leg |
| The 162 v1 API calls | `ApiContractTest` runs | v1 snapshots unchanged; four new v2 snapshots, validated against the spec | `ApiContractTest` |

## Data changes

`idempotency_keys` (new, additive, reversible): `id`, `user_id` (foreign key to `users`, cascade), `key` (string 255), `key_hash` (sha256, unique with `user_id`), `method`, `route`, `request_hash` (sha256), `response_status` (null while running), `response_headers` (json), `response_body` (longtext), `expires_at` (indexed), timestamps. Rows live 24 hours; `model:prune` runs daily. No personal data beyond what the stored answer holds, for one day.

## Endpoints

| Method | Path | Group | Answer |
| --- | --- | --- | --- |
| GET | `/api/v2/me/context` | every profile | `ContextResponse` (see `openapi.yaml`) |

## Screens, notification types, module switch

None. `/me/context` already carries `modules`, `notification_rules`, `feature_flags` and `sign_in_methods` so the apps can read them now and Phase 1 fills them. API v2 itself has no module switch (it is platform).

## Security notes

- Deny by default: every v2 route is in a role group; the Tenancy suite fails on one that is not, and the Authorization matrix checks every wrong role gets 401 or 403.
- The context exposes only the caller's own data: their user, their roles, their school's public profile, and (parents) their own children, found through `Parents::children()` like `CheckChild`.
- `crest_url` and images are public storage URLs, as in v1. Colours are passed on as stored and shown only beside the NavigateEd theme.
- Errors never carry exception text unless `APP_DEBUG` is on. Idempotency rows are per user, so one user can never replay another's answer; keys are stored with their hash indexed, bodies for 24 hours.
- Rate limits per user (`api`), per account and address (`auth`), per token (`money`).

## Defaults taken

- School id is always 1 and the school comes from settings until Phase 1 adds `schools`; `short_name` reads a new optional setting `school_short_name` (null until set), `crest_url` the `logo1` setting (null when none, rather than the eSchool default logo).
- `api` stays 60 a minute per user (the Build Bible counts per token): per user is the stricter of the two and v1 already relies on it.
- `staff` admits Teachers too (the panel's rule, `CheckRole`); finer access is for policies.
- `/me/context` does not run the v1 session checks (a learner without a current-year session can still read it); v1 calls still do.
- Idempotency applies to signed-in writes only; answers with 5xx, 401, 403, 409 and 429 are not stored, so they can be retried; a first request silent for 5 minutes frees its key.
- The v1 session and policy middleware answer in their v1 shapes inside the v2 student, parent and teacher groups; no v2 route sits there yet.

## Self-test steps

1. `php artisan route:list --path=api/v2` lists `GET api/v2/me/context` with `auth:sanctum`, `role.any`, `idempotent`.
2. Sign in as a parent with the v1 parent login, then `curl -H "Authorization: Bearer <token>" -H "Accept: application/json" https://<host>/api/v2/me/context`: the user, the school's name, timezone and terms, and the parent's children.
3. The same without the header: 401 with `"code": "unauthenticated"`.
4. `php artisan test --filter='ContextTest|ApiV2GroundRulesTest|IdempotencyKeyTest|OpenApiContractTest'`: green.
5. `npx --yes @redocly/cli@2.57.0 lint openapi.yaml` in `admin-panel/`: valid, no warnings.
6. `packages/eschool_api/tool/generate.sh`, then `git status`: nothing changed. `flutter analyze --no-fatal-infos && flutter test` in `packages/eschool_api`: green.
