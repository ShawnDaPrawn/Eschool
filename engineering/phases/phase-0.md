# Phase 0: Groundwork

**Goal.** Get the seed ready for the work that follows without changing what users do, apart from a clearer admin panel. Phase 0 removes the unsafe and unused parts of the seed and adds the plumbing later work builds on: background jobs, an audit trail, an outbox, money held in cents, an API v2 skeleton, web builds of the apps, environment definitions and stricter CI.

Source: Build Bible section 6. Open decisions and the defaults taken are in [phase-0-questions.md](phase-0-questions.md).

## Work streams

The phase runs as parallel work streams, each on its own branch and merged into the phase branch. Streams that rewrite the same files run one after the other.

| Stream | Role | Depends on | Owns |
| --- | --- | --- | --- |
| S1 Foundation | Backend | none | Removals, seed defects, `ResponseService` returns |
| S2 Apps | App | none | `packages/`, `e-school/`, `e-school-teacher/` |
| S3 Panel redesign | Admin UX | none | `resources/views/`, `config/navigation.php`, search, year tools |
| S4 Ops and docs | Release, Lead | none | `engineering/`, deploy workflows, runbooks, Docker, Dependabot, gitleaks |
| S5 Kernel | Backend | S1 | Queue, scheduler, mail, outbox, audit, cents, `settings.school_id` |
| S6 Security | Backend | S1 | Password policy, teacher status, tokens, CORS, invitations, uploads, CSP |
| S7 API v2 and test infrastructure | Backend, Test | S1 | `routes/api_v2.php`, `openapi.yaml`, `packages/eschool_api`, `DemoSchoolSeeder`, tenancy scaffold, Playwright, k6 |
| S8 Close | Lead, Test, Review | all | Pint over the panel, Larastan level 1, wiki, Build log, self-test record |

## Features in build order

| # | Feature | Stream | Spec |
| --- | --- | --- | --- |
| 0.1 | Engineering scaffolding: `engineering/`, `CHANGELOG.md`, ADR 0001 to 0003, wiki sidebar links, PR template | S4 | this file |
| 0.2 | Remove System Update, the GET maintenance routes, the Razorpay, Paystack and Flutterwave gateways, the dead route block, `EnsureUserIsTeacher`, the duplicate route registration | S1 | [specs/platform/removals.md](../specs/platform/removals.md) |
| 0.3 | `ResponseService` returns its response instead of calling `exit()`; every call site returns it | S1 | [specs/platform/response-service.md](../specs/platform/response-service.md) |
| 0.4 | Queue and scheduler, with mail and push sent from jobs instead of inside the request: database queue, `SendMail` and `SendPush` jobs, `notification_deliveries` | S5 | [specs/platform/queue-and-delivery.md](../specs/platform/queue-and-delivery.md) |
| 0.5 | Transactional email through the chosen provider with bounce and complaint webhooks | S5 | [specs/platform/queue-and-delivery.md](../specs/platform/queue-and-delivery.md) |
| 0.6 | Kernel scaffolding: outbox, audit log, `Money` and `*_cents` columns, `settings.school_id` | S5 | [specs/platform/kernel.md](../specs/platform/kernel.md) |
| 0.7 | Security fixes in the seed | S6 | [specs/platform/security-fixes.md](../specs/platform/security-fixes.md) |
| 0.8 | API v2 skeleton with `GET /api/v2/me/context` and `openapi.yaml` | S7 | [specs/platform/api-v2.md](../specs/platform/api-v2.md) |
| 0.9 | App foundations: `AppConfig`, `eschool_session`, `eschool_notifications`, shared files in `eschool_core`, typed routes, responsive layout | S2 | [specs/apps/foundations.md](../specs/apps/foundations.md) |
| 0.10 | Web builds of both apps | S2 | [specs/apps/web-builds.md](../specs/apps/web-builds.md) |
| 0.11 | Admin panel redesign, part one: navigation by job, page components, Home checklist, global search, year switcher, new-year wizard | S3 | [specs/panel/redesign-part-one.md](../specs/panel/redesign-part-one.md) |
| 0.12 | Environments and operations: staging, deploy workflows, worker and scheduler, backups, logs, runbooks | S4 | [specs/platform/environments.md](../specs/platform/environments.md) |
| 0.13 | Test infrastructure: `DemoSchoolSeeder`, Pint, Larastan 1, tenancy scaffold, Playwright, integration smoke, k6 | S7, S2, S8 | [specs/platform/test-infrastructure.md](../specs/platform/test-infrastructure.md) |

## Exit gate

The gate is in the Build Bible, section 6. Our status against it is kept in the [build log](../bible/build-log.md), with the evidence in the [self-test record](../self-tests/phase-0.md) and the [security record](../security/phase-0.md). What needs the cloud project (staging, a week of the worker and scheduler, the restore drill) or people (UX scripts with new users, the product owner's acceptance) stays open there until it has happened.

## Delivery policy (product-owner decision, 23 October 2026)

Acceptance checkpoints do not freeze development. Independent features and
later-phase preparation continue while operational observations, staging,
devices or UX participants are unavailable. Each feature records implementation,
local/CI verification, staging verification, acceptance and release separately.
Known security or data-integrity failures block only the affected action.
Production releases still require the owner's staging acceptance.

The existing product defaults are confirmed. Additional staging and email
spending is capped at US$10/month, excluding the existing production bill.
No cloud resources may be provisioned above that cap; cloud evidence remains
pending when compliant infrastructure cannot be funded.
