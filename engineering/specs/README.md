# Feature specs

One file per feature, at `specs/<module>/<feature>.md`, written before the code. The spec is the contract the Test and Review roles check a pull request against (Build Bible section 4, "Spec before code").

Modules are the backend domains and kernel parts (`platform`, `people`, `academics`, `communication`, `money`, `activities`, `wellbeing`, `growth`), plus `apps` and `panel` for work that lives only in the Flutter apps or the admin panel.

## Template

```markdown
# <Feature name>

Phase N, feature N.M. Owner role: <Backend | App | Admin UX | Release | ...>.

## User stories
One or more per profile the feature touches (SaaS admin, school admin, finance,
teacher, grade head, facilitator, counsellor, learner, parent, vendor).

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
