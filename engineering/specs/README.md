# Feature specs

One file per feature, at `specs/<module>/<feature>.md`, written before the code. Whoever tests or reviews a pull request checks it against its spec (the Build Bible, section 4).

The module is the backend domain the feature belongs to (Phase 0's backend work is all `platform`), or `apps` and `panel` for work that lives only in the Flutter apps or the admin panel.

## Template

```markdown
# <Feature name>

Phase N, feature N.M. Owner role: <Backend | App | Admin UX | Release | ...>.

## User stories
At least one for each profile the feature touches (school admin, teacher,
learner, parent, and so on).

## Acceptance tests
Given ..., when ..., then ... One per story, plus the refusals: wrong role,
another school's record, another user's record.

## Data changes
New tables and columns (school_id, timestamps, soft deletes, cents), indexes,
back-fills, and the down() of each migration.

## Endpoints
Method, path, middleware (auth:sanctum, school.context, role:*, module:<key>),
request and response shape, and the openapi.yaml change.

## Screens
Panel pages and app screens, with their loading, empty, error and offline states.

## Notification types
Keys registered in config/notification_types.php, channels and defaults.

## Module switch
The config/modules.php key, and what disappears when it is off.

## Security notes
Authorisation, school isolation, input validation, secrets, audit, retention.

## Self-test steps
The rows this feature adds to engineering/self-tests/phase-N.md.
```

Keep the headings even when a section is "None", so a reviewer can see it was considered.

## Specs

| Spec | Phase, feature | Stream |
| --- | --- | --- |
| [platform/removals.md](platform/removals.md): removals and seed defects (System Update, GET maintenance URLs, Razorpay, Paystack, Flutterwave) | 0, 0.2 | S1 |
| [platform/response-service.md](platform/response-service.md): `ResponseService` returns instead of exiting | 0, 0.3 | S1 |
| [platform/queue-and-delivery.md](platform/queue-and-delivery.md): queue, scheduler, queued mail and push, Amazon SES and its feedback webhook | 0, 0.4 and 0.5 | S5 |
| [platform/kernel.md](platform/kernel.md): outbox, audit log, money in cents, `settings.school_id` | 0, 0.6 | S5 |
| [platform/security-fixes.md](platform/security-fixes.md): password rule, teacher status, app tokens, CORS, learner invitations, uploads, CSP, secrets out of settings | 0, 0.7 | S6 |
| [platform/api-v2.md](platform/api-v2.md): `/api/v2`, `GET /me/context`, `openapi.yaml` | 0, 0.8 | S7 |
| [apps/foundations.md](apps/foundations.md): build settings, shared packages, typed routes, responsive layout | 0, 0.9 | S2 |
| [apps/web-builds.md](apps/web-builds.md): web builds of both apps and their hosting | 0, 0.10 | S2 |
| [panel/redesign-part-one.md](panel/redesign-part-one.md): navigation by job, page components, Home checklist, search, year switcher, new-year wizard | 0, 0.11 | S3 |
| [platform/environments.md](platform/environments.md): staging and production, deploy workflows, worker and scheduler, backups, logs, runbooks | 0, 0.12 | S4 |
| [platform/test-infrastructure.md](platform/test-infrastructure.md): `DemoSchoolSeeder`, tenancy scaffold, role matrix, contract suite, Playwright, integration smoke, k6 | 0, 0.13 | S7, S2, S3 |
| [platform/profile-files.md](platform/profile-files.md): encrypted person attachments and authenticated panel/app previews | 1, profile fields | Identity |
| [academics/academic-references.md](academics/academic-references.md): shared grades/phases, seven-point reference and explicit class mapping | 2, foundation of 2.2 | Academics |
