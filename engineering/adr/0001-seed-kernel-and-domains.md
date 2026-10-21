# 0001. Grow the seed: one Laravel app, a platform kernel underneath, domains beside it

Status: Accepted (Phase 0). Source: Build Bible sections 2, 3 and 3.6.

## Context

NavigateEd starts from the WRTeam eSchool codebase we bought and have since hardened (the "seed"):

- `admin-panel/`: one Laravel 12 application (PHP 8.3, MySQL 8) serving the admin panel, the school's public pages and a REST API for the apps. 76 models, 127 controllers (45,551 lines), 28 services, 155 API routes, about 500 web routes.
- `e-school/` and `e-school-teacher/`: two Flutter apps (learner and parent; teacher) that duplicate 144 files, hard-code the API address and talk to about 160 endpoints each. `packages/eschool_core` holds the shared theme and API client.
- It runs **one school per install**. There is no `school_id` anywhere and Spatie permissions run with `teams => false`.
- Everything happens inside the web request: `QUEUE_CONNECTION=sync`, no jobs, events, policies or observers, and no scheduler in production. `ResponseService` ends API responses with `exit()`.
- Money is stored as floats, validation is mostly inline, and some controllers are well over a thousand lines.

What it does well stays: the daily academic loop (timetable, attendance, lessons, assignments, exams, fees, chat, notices), the Material 3 look of the apps, the security pass (upload filtering, webhook signatures, login throttling, the security test suites, 162 API shape snapshots), the session-year machinery, the Docker image and the Cloud Run deployment.

The product has to become a multi-school platform sold per school, with every module switchable, a ledger-grade money domain, AI behind one gateway, and apps that also run in a browser. Installed apps in schools' hands must keep working through every release.

## Options

1. **Rewrite** on a new stack (for example separate services per domain, or a new frontend and API). Clean boundaries, but months before parity, a data migration from the seed's schema, and two systems to run in the meantime. Installed apps would need a compatibility layer anyway.
2. **One install per school**, as the seed was designed. No tenancy work, but every school is its own deployment, database and upgrade; cross-school identity (a parent with children at two schools, a learner who moves school) is impossible, and operating cost grows with each school.
3. **Grow the seed**: keep the one Laravel application, add a **platform kernel** underneath it (tenancy, identity, modules, notifier, outbox, audit, ledger, AI gateway, files, settings) and add **domains** beside the existing code, each in Laravel's standard folders with a subfolder per domain. Legacy code keeps serving and moves into domain services as it is touched.
4. **Modular monolith as Composer packages**, one package per domain. Stronger compile-time boundaries, but it breaks Laravel's discovery and IDE conventions the existing code relies on, and adds packaging overhead for a single deployable.

## Decision

Option 3. The seed grows; nothing is rewritten or re-platformed.

**One application, one database.** `admin-panel/` stays the only backend: panel, public pages, API v1 (unchanged) and API v2 (new, see [ADR 0002](0002-api-v2-and-openapi.md)). One Cloud SQL MySQL 8 database holds every school, with `school_id` on every school-owned row (from Phase 1) and the `BelongsToSchool` global scope on every school-owned model.

**The kernel** lives in `app/Services/Platform/` with its tables and middleware, built in Phases 0 and 1:

| Part | Gives every domain |
| --- | --- |
| Tenancy | The current school from the user's membership (`CurrentSchool`, `school.context`); Spatie teams keyed on `school_id` |
| Identity | One `users` row per human across schools; memberships, guardian links, learner approvals, passkeys |
| Modules | `config/modules.php`, `ModuleGate`, `module:<key>` middleware, per-school switches |
| Notifier | `Notifier::send(type, recipients, payload)`; school rules, personal preferences, queued delivery, `notification_deliveries` |
| Outbox | `Outbox::record()` in the same transaction, dispatched by the worker (`domain_events`) |
| Audit | `Auditable` trait and `Audit::record()` (`audit_log`) |
| Ledger | Double-entry postings in integer cents with idempotency keys |
| AiGateway | Every model call in one place: routing, moderation, caps, metering |
| Files | One upload path on Cloud Storage, signed URLs, scanning |
| Settings | `getSettings()` kept, `settings.school_id` added, `SchoolSettings` facade |

**Domains**: People, Academics, Communication, Money, Activities, Wellbeing, Growth, Platform. Each follows the domain recipe in Bible section 3.2: `app/Models/<Domain>/`, `app/Services/<Domain>/` (the only place with business rules, transactions and idempotency), `app/Policies/<Domain>/`, `app/Http/Requests/<Domain>/`, thin controllers in `app/Http/Controllers/Api/V2/<Domain>/` and `Panel/<Domain>/`, Resources matching `openapi.yaml`, and `tests/Feature/<Domain>/` with four tests per endpoint plus tenancy and policy tests.

**Legacy code** under `app/Http/Controllers/Api/{Student,Parent,Teacher}` and the existing panel controllers keeps serving. When a phase touches one, its rules move into a `Services/<Domain>` service that v1 and v2 both call, so the two never drift.

**Runtime services**, all from the one image in `africa-south1`:

| Service | What it runs |
| --- | --- |
| `navigateed-admin` (Cloud Run service) | The Laravel app: panel, API, public pages (`CONTAINER_ROLE=web`, runs the migrations) |
| `navigateed-worker` (Cloud Run service) | `queue:work` on the database queue (`CONTAINER_ROLE=worker`); Redis from Phase 2 if needed |
| `navigateed-scheduler` (Cloud Run Job) | One `schedule:run` per minute, triggered by Cloud Scheduler (`CONTAINER_ROLE=scheduler`) |
| `navigateed-converter` (Phase 2) | Python, MarkItDown: documents and media to Markdown, called by a queued job |
| `navigateed-reverb` (later) | Websockets for live updates, minimum one instance |

**How the seed grows**: Phase 0 adds the kernel's scaffolding without changing behaviour (queue and scheduler, outbox, audit, cents, the API v2 skeleton with `/me/context`, app packages, web builds, the panel's navigation). Phase 1 adds `schools` and `school_id` to every table with a back-fill to one school, turns on teams mode, memberships, guardian links and approvals, module switches and notification rules. Each later phase adds domains beside existing code and replaces screens only when the new one is at least as good. A v1 endpoint is retired only when the structured logs show no callers for a full term.

## Consequences

- No rewrite and no data migration between systems: schools keep their data, and installed apps keep working because v1 keeps answering.
- One deployable and one database keep operations small (one image, three roles; one backup and restore path). Releases are all-or-nothing for the backend; the runbooks in `engineering/runbooks/` cover rollback.
- Domain boundaries are a convention enforced by review and tests, not by the runtime. The domain recipe, the Review role and the tenancy suite (`tests/Feature/Tenancy/`) carry that weight.
- School isolation depends on every school-owned model using `BelongsToSchool` and on routes resolving the school from the membership, never from a request parameter. A missing scope is a data leak, so the cross-school suite runs on every endpoint, every phase.
- One shared database means one school's heavy job can slow another's requests. Long work moves to the queue; per-school caps (AI, exports) and indexes on `school_id` are part of each feature's definition of done.
- Legacy controllers stay large until a phase touches them; the codebase carries two styles for a long time. New work never extends the legacy style.
- Background work, which the seed never had, becomes a dependency: the worker and scheduler need monitoring, alerting and runbooks (`engineering/runbooks/incident.md`).
