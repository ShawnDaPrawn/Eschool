# 0001. Grow the seed: one Laravel app, a platform kernel underneath, domains beside it

Status: Accepted (Phase 0). Source: Build Bible sections 2, 3 and 3.6.

## Context

NavigateEd starts from the WRTeam eSchool codebase, bought and since hardened (the "seed"):

- `admin-panel/` is a single Laravel 12 application (PHP 8.3, MySQL 8). It serves the admin panel, each school's public pages and the REST API the apps use: roughly 75 models, 125 controllers, 155 API routes and 500 web routes.
- `e-school/` (learner and parent) and `e-school-teacher/` are Flutter apps that share a theme and API client through `packages/eschool_core` but still duplicate many files and hard-code the API address.
- An install serves one school. No table has a `school_id`, and Spatie permissions run with `teams => false`.
- All work happens inside the web request: the queue connection is `sync`, there are no jobs, events, policies or observers, production has no scheduler, and `ResponseService` ends API responses with `exit()`.
- Money is stored as floats, validation is mostly inline, and some controllers run past a thousand lines.

The seed's strengths are worth keeping: the day-to-day school features the apps already offer, the Material 3 look, the earlier security work (upload filtering, webhook signatures, login throttling, security tests and 162 API shape snapshots), the session-year model, and the Docker image with its Cloud Run deployment.

NavigateEd has to serve many schools from one installation and keep adding features school by school, while the apps already installed on phones keep working through every release.

## Options

1. **Rewrite** on a new stack. Clean boundaries, but months before it matches the seed, a data migration, two systems to run meanwhile, and a compatibility layer for the installed apps anyway.
2. **One install per school**, as the seed was built. No tenancy work, but a deployment, database and upgrade per school, running costs that grow with every school, and no way for one person (a parent with children at two schools, a learner who changes school) to have one account.
3. **Grow the seed**: keep the one Laravel application, put a shared platform layer (the "kernel") underneath it for the concerns every feature needs, and add new work in domain folders beside the existing code. Legacy code keeps serving and moves into domain services as it is touched.
4. **A modular monolith of Composer packages**, one per domain. Firmer boundaries, but it fights the Laravel discovery and IDE conventions the existing code relies on, and adds packaging work for what is still one deployable.

## Decision

Option 3. The seed grows; nothing is rewritten or re-platformed.

**One application, one database.** `admin-panel/` stays the only backend: panel, public pages, API v1 (unchanged) and API v2 (new, see [ADR 0002](0002-api-v2-and-openapi.md)). One Cloud SQL MySQL 8 database will hold every school, each school-owned row carrying `school_id` once Phase 1 adds it.

**The kernel** lives in `app/Services/Platform/`, with its models in `app/Models/Platform/` and its jobs in `app/Jobs/Platform/`. Phase 0 put these parts in place:

| Part | What exists |
| --- | --- |
| Notifications | `SendMail` and `SendPush` jobs on the queue, each attempt recorded in `notification_deliveries`; SES feedback through `SesNotifications` |
| Outbox | `Outbox::record()` writes `domain_events` in the caller's transaction; `DispatchOutbox` hands them to listeners every minute |
| Audit | the `Auditable` trait and `Audit::record()`, writing `audit_log` |
| Money | `Money` (integer cents and a currency), `MoneyCast` and the `*_cents` columns beside the seed's floats |
| Identity | one password rule (`PasswordPolicy`), per-app tokens (`AppTokens`), learner invitations |
| Files | stored names built by `FileNames`, uploads kept per school |
| Settings | `getSettings()` kept, `settings.school_id` added for school-scoped settings |
| Tenancy | `CurrentSchool`, which answers school 1 until Phase 1 adds schools and memberships |
| Request plumbing | `RequestContext` for structured logs, `IdempotencyService` for API v2 writes, `ContextService` for `GET /api/v2/me/context` |

Later phases add the rest of the kernel (school isolation, module switches, notification rules, the ledger) as they need it.

**Domains.** New backend code follows the domain recipe in the Build Bible, section 3.2: `app/Models/<Domain>/`, `app/Services/<Domain>/` (where business rules, transactions and idempotency live), `app/Policies/<Domain>/`, `app/Http/Requests/<Domain>/`, thin controllers in `app/Http/Controllers/Api/V2/<Domain>/` and `Panel/<Domain>/`, Resources that match `openapi.yaml`, and tests in `tests/Feature/<Domain>/` (happy path, wrong role, wrong owner, plus tenancy and policy tests). Phase 0's own code uses the `Platform` domain.

**Legacy code** in `app/Http/Controllers/Api/{Student,Parent,Teacher}` and the existing panel controllers keeps serving. When a phase changes one, its rules move into a service under `Services/<Domain>` that v1 and v2 both call, so the two cannot drift apart.

**Runtime services**, all from one image in `africa-south1`:

| Service | What it runs |
| --- | --- |
| `navigateed-admin` (Cloud Run service) | The Laravel app: panel, API, public pages (`CONTAINER_ROLE=web`, runs the migrations) |
| `navigateed-worker` (Cloud Run service) | `queue:work` on the database queue (`CONTAINER_ROLE=worker`); Redis later if the database queue is not enough |
| `navigateed-scheduler` (Cloud Run Job) | One `schedule:run` per minute, triggered by Cloud Scheduler (`CONTAINER_ROLE=scheduler`) |

**Compatibility.** v1 keeps answering exactly as before. A v1 endpoint is retired only after the structured logs show no callers for a full school term.

## Consequences

- No rewrite and no migration between systems: schools keep their data, and installed apps keep working because v1 is unchanged.
- One deployable and one database keep operations small (one image, three roles; one backup and restore path). A backend release is all or nothing; the runbooks in `engineering/runbooks/` cover rollback.
- Domain boundaries are a convention, held by review and tests rather than by the runtime.
- School isolation will depend on every school-owned model being scoped and on routes taking the school from the user's membership, never from a request parameter. A missing scope is a data leak, so the cross-school suite (`tests/Feature/Tenancy/`) covers every endpoint in every phase.
- A shared database means one school's heavy job can slow another's requests. Long work belongs on the queue, and indexes on `school_id` are part of a feature being done.
- Legacy controllers stay large until a phase touches them, so the codebase carries two styles for a long time. New work never extends the legacy style.
- The seed never had background work; now the worker and scheduler are dependencies that need monitoring, alerts and runbooks (`engineering/runbooks/incident.md`).
