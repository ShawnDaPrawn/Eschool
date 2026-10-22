# 0002. API v2 under `/api/v2` with `openapi.yaml` as the contract; v1 frozen, add-only

Status: Accepted (Phase 0). Source: Build Bible sections 3.3 and 5.5, Phase 0 step 8.

## Context

- API v1 (`routes/api.php`) has 155 routes in three role groups (`student/`, `parent/`, `teacher/`) plus 14 general routes. Payloads are hand-built arrays, there is no machine-readable description, and `ResponseService` ends each response with `exit()`.
- The two apps hard-code about 160 endpoint strings each (`lib/utils/api.dart`) and map error `code`s by hand. Builds already installed on phones keep calling v1 for months after a release; a forced update is the last resort.
- The 162 snapshots in `admin-panel/tests/snapshots/api/` pin the shape of v1 responses, and `ApiContractTest` checks them.
- Multi-school (Phase 1) needs one call that tells an app who the user is, which schools and roles they have, and what each school has switched on. New modules need typed clients in the new Flutter packages (`packages/eschool_api`), not more hand-written strings.

## Options

1. **Keep extending v1.** No new surface, but every new field lands in untyped, role-split controllers, and multi-school context has nowhere natural to live.
2. **GraphQL.** Flexible reads and one typed schema, but a new server stack beside Laravel's routing, policies and rate limiting, new caching and authorisation patterns, and no reuse of the v1 envelope the apps' `ApiClient` already understands.
3. **REST v2 under `/api/v2`, spec first.** `admin-panel/openapi.yaml` is written with the endpoint, CI validates it, responses are checked against it in tests, and the Dart client is generated from it.
4. **REST v2, code first**, generating the spec from annotations or route inspection. Less writing, but the spec then follows whatever the code does, which is the opposite of a contract.

## Decision

Option 3.

- **Base path** `/api/v2/`, loaded from `routes/api_v2.php` with `auth:sanctum` and the role groups. The **envelope stays** `{error, message, data, code}` so the apps' `ApiClient` keeps working. Apps use Sanctum bearer tokens; the web portals use Sanctum's cookie mode.
- **`GET /api/v2/me/context`**: the user, their schools and roles, the current school (branding, timezone, terms, modules on, notification rules, feature flags, sign-in methods), and for parents each child with that child's school. Apps call it on launch, after sign-in and after a school switch, and cache it. Phase 0 serves one school and an empty module list.
- **Resources are nouns** with the same list parameters everywhere: `page`, `per_page`, `sort`, `filter[...]`, `q`. Writes that create money, marks or records accept an `Idempotency-Key` header.
- **Rate limits**: `api` 60 a minute per token (as v1 today), `auth` 5 a minute per account, `money` 30 a minute per token, stricter on OTP; every group limits per token and per IP.
- **The contract**: `admin-panel/openapi.yaml` (OpenAPI 3.1). A CI job validates the file; `ApiContractTest` (extended to v2) validates every v2 response against it in `tests/Feature/Contracts/`; `packages/eschool_api` is generated from it. A pull request that changes a v2 response without changing the spec fails.
- **v1 is frozen, add-only**: no field or route is renamed or removed; fields may be added. When a phase touches a v1 controller, its rules move into a `Services/<Domain>` service that v1 and v2 both call. The v1 snapshots stay unchanged unless only fields were added.
- **Retirement**: a v1 endpoint is removed only when the structured request logs (the `route` field, see `engineering/runbooks/incident.md` and `wiki/Configuration`) show no callers for a full school term, and then only in a later phase.

## Consequences

- Two API generations run side by side for a long time. Shared domain services keep their behaviour identical; tests cover both.
- Spec-first adds writing to every endpoint, but the generated client removes the hand-kept endpoint strings and error-code tables for new work, and reviewers can read the change in one file.
- The envelope is a compromise kept for the apps: standard OpenAPI tooling sees every response as the same wrapper, so the spec defines the wrapper once and each operation's `data` schema.
- Retiring v1 depends on per-route request logs. The structured JSON logs added in Phase 0 carry the route pattern on every request line, so the "no callers for a term" measure is a Cloud Logging query, not new code.
- Cookie mode for the web portals ties the portals and the API to one parent domain (see [ADR 0003](0003-app-packages-and-web.md)) and needs CORS limited to known origins.
