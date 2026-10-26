# Platform admin area (/saas), first version

Phase 1, stream T4. Owner role: Backend + Admin UX. Build Bible section 7 (Phase 1: "a new school goes from the platform admin ... end to end"); [phase-1.md](../../phases/phase-1.md) row T4; decision Q1.3 ([phase-1-questions.md](../../phases/phase-1-questions.md)).

The panel platform staff use to see the schools on the install, set one up with its first administrator, choose its package and modules, and suspend or restore it. It sits beside the school panel, not inside it: platform staff belong to no school.

## Scope of this version

Built: school list with counts, set up a school, package, suspend/archive/restore, module switches, audited and fenced.

Not built, and not implied by any screen: audited support access into a school (acting as a school user), import toolkit (CSV, SA-SAMS), usage, health and delivery views, consent/retention/personal-data tooling, and a way to give an existing account a role at a new school. Each needs its own spec; support access also needs the decision on what platform staff may see (below).

## User stories

- **Platform staff:** I open `/saas`, see every school with its status, package and how many learners, parents and teachers it has, and open one.
- **Platform staff:** I set up a new school by name, time zone and package with its first administrator, and hand them a one-time password.
- **Platform staff:** I move a school to another package, or switch one of its modules, and the school's apps refresh.
- **Platform staff:** I suspend a school that has not paid, with a reason, and restore it when it has.
- **School administrator:** I can never open `/saas`, and it never reveals whether a school exists.
- **Learner, parent, teacher:** nothing about me is shown on the platform pages.

## Who may open it

Both of these, checked on fresh rows on every request ([`PlatformStaffAccess`](../../../admin-panel/app/Services/Platform/Saas/PlatformStaffAccess.php), alias `platform.staff`):

1. an **active platform staff identity** (`platform_staff_identities`, `php artisan platform:staff-identity <user_id>`), the exclusion identity every operator has; and
2. an **unexpired platform admin grant** (`platform_admin_grants`, `php artisan platform:admin-access <user_id> [--hours=24] [--revoke]`, 1 to 8760 hours), set only from the operator console.

The existing login accepts a role-free operator only after the same fresh grant checks, redirects them to `/saas`, and gives them no school membership. Default or flagged passwords do not open the platform area.

The identity alone grants nothing (its spec: "not a grant of platform capabilities"), and neither does a school's Super Admin role. The account must be active and not owe a password change, and access is refused while acting inside a school (impersonation). Revoking either row, letting the grant expire or deactivating the account ends access on the next request. The middleware runs before any record lookup, so a refusal is the same for every school id.

## Acceptance tests

[`tests/Feature/Platform/SaasPanelTest.php`](../../../admin-panel/tests/Feature/Platform/SaasPanelTest.php).

| Given | When | Then |
| --- | --- | --- |
| Staff with identity and grant | they open the list, a school and the set-up page | each answers 200, private (`no-store`), with no email address anywhere |
| A school's Super Admin with no identity | they call any `/saas` route, with any method | 403, and nothing changes |
| Identity only, grant only, expired or revoked grant, deactivated or password-pending account | they open `/saas` | 403 |
| Any user | a school id that does not exist | 403 for non-staff, 404 for staff |
| Staff | they set up a school | the school, its roles and profile defaults exist; the administrator has one membership, Super Admin of that school; the password is shown once, is hashed, and is in no audit row or session |
| Staff | they use an email that already has an account, a taken or malformed short name, an unknown package or time zone | nothing is created |
| Staff | they change school 2's package, status or a module | school 1's rows are unchanged; the audit row names the staff member, the school and, for a status, the reason |
| Staff | they suspend a school | its people have no active membership (refused at the next request) and nothing is deleted; restoring undoes it |
| Staff | they suspend the install's default school | refused: public pages and sign-in run in it |
| Staff | they switch a required module off, or a module outside the package on | refused, as for the school's own admin |
| The routes | listed | every `/saas` route carries `auth` and `platform.staff` and none carries `school.context` |

## Data changes

`platform_admin_grants` (migration `2026_10_26_100000`): `user_id` unique (restrict on delete), `active` (default false), `expires_at`, timestamps. Global ([`SchoolOwnedTables::GLOBAL`](../../../admin-panel/app/Services/Platform/Tenancy/SchoolOwnedTables.php)), audited through `PlatformAdminGrant`. `down()` refuses to drop a table that has rows. No school-owned table changes.

## Endpoints

All web, session auth, `PrivateDomainResponse`, `platform.staff`; none under `school.context`; no API v1 or v2 route and no `openapi.yaml` change.

| Method | Path | Does |
| --- | --- | --- |
| GET | `/saas` | schools with status, package and active people by role |
| GET, POST | `/saas/schools/create`, `/saas/schools` | the set-up form; creates the school and shows the one-time password in that response (throttled 20 a minute) |
| GET | `/saas/schools/{id}` | one school: counts, package, status, modules as the school sees them |
| PUT | `/saas/schools/{id}/package` | package key from `config/school_packages.php`; queues `context.changed` for that school |
| PUT | `/saas/schools/{id}/status` | `active`, `suspended` or `archived`, with a reason of 10 to 500 characters |
| PUT | `/saas/schools/{id}/modules/{module}` | `ModuleGate::change` inside that school, so the rules of the school's own switch apply |

## Screens

Four standalone Material 3 pages (`resources/views/saas/`, `public/assets/css/saas.css`) on the panel's tokens: list, set up, created (shows the password once) and school. They do not use the school layout, which needs a school. Loading and empty states are the table's; every form error shows in a `role="alert"` block.

## Security notes

- **No personal record is returned.** Aggregate counts filter current dated memberships, active schools and active/non-deleted accounts using each school's calendar; the platform staff member gets no role, membership or permission in any school, and no learner, guardian or staff row is listed or opened.
- **The first administrator's password** is random (16 characters), flagged `password_change_required`, returned in the response to the POST and never redirected, flashed, stored or logged. Nothing is emailed from here. It is shown to the person who is about to hand it over; this is the weakest point of the flow and is replaced by an emailed set-up link once mail is live in an environment.
- **Existing accounts** cannot be made the first administrator: a person's email already having an account is refused, so this page can never be used to add oneself or anyone else to a school that has people.
- **The install's default school** cannot be suspended or archived: sign-in and public pages run in it.
- **Suspension** works through `MembershipService`: a school that is not active has no active memberships, so its people are refused on their next request, including tokens. Records stay.
- **Grant expiry:** `expires_at` is a UTC instant, written and compared in UTC. Applying a school timezone cannot revive an expired grant or end a valid one early.
- **Package downgrade:** the existing `ModuleGate` money transition runs before a package loses entitlement. Retained fee history stays readable and read-only; a new Core school without history keeps fees disabled.
- **First sign-in:** a new administrator must replace the generated password and can reach the empty-year Home/checklist and its entitled widgets without any fabricated academic year.
- **Audit:** `platform_school_created`, `platform_school_package_changed`, `platform_school_status_changed` (with the reason) and `platform_school_module_changed`, each with the school id and the actor, plus the model-level rows for `schools`, `school_modules` and `platform_admin_grants`.
- **Not done:** step-up re-authentication for destructive actions, and a rate limit beyond the creation form, are left for the support-access spec.

## Self-test steps

1. `php artisan platform:staff-identity <your user id>` then `php artisan platform:admin-access <your user id> --hours=8`; sign in and open `/saas` (a bare `/saas` link is not in the school menu).
2. See the schools and counts; open one; change its package; switch a module; confirm the school's own School modules page shows the change.
3. Set up a school; copy the password; sign in as the new administrator in another browser; change the password; confirm they see only their own school and `/saas` answers 403.
4. Suspend that school with a reason; confirm its administrator is refused; restore it.
5. `php artisan platform:admin-access <id> --revoke`; confirm `/saas` answers 403 on the next page.
