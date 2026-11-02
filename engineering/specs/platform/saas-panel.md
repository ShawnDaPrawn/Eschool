# Platform admin area (/saas), first version

Phase 1, stream T4. Owner role: Backend + Admin UX. Build Bible section 7 (Phase 1: "a new school goes from the platform admin ... end to end"); [phase-1.md](../../phases/phase-1.md) row T4; decision Q1.3 ([phase-1-questions.md](../../phases/phase-1-questions.md)).

The panel platform staff use to see the schools on the install, set one up with its first administrator, choose its package and modules, and suspend or restore it. It sits beside the school panel, not inside it: platform staff belong to no school.

## Scope of this version

Built: school list with counts, set up a school, package, suspend/archive/restore, module switches, audited and fenced.

Not built, and not implied by any screen: import toolkit (CSV, SA-SAMS), delivery views (the core usage and health figures are in [HQ school health](hq-school-health.md)), consent/retention/personal-data tooling, and a way to give an existing account a role at a new school. Each needs its own spec. Audited support access is now [support-access.md](support-access.md) (SA.9): consented, time-limited, read-only, never restricted records, every page logged.

## User stories

- **Platform staff:** I open `/saas`, see every school with its status, package and how many learners, parents and teachers it has, and open one.
- **Platform staff:** I set up a new school by name, time zone and package with its first administrator, who is emailed a private link to choose their own password.
- **Platform staff:** I move a school to another package, or switch one of its modules, and the school's apps refresh.
- **Platform staff:** I suspend a school that has not paid, with a reason, and restore it when it has.
- **School administrator:** I can never open `/saas`, and it never reveals whether a school exists.
- **Learner, parent, teacher:** nothing about me is shown on the platform pages.

## Who may open it

Both of these, checked on fresh rows on every request ([`PlatformStaffAccess`](../../../admin-panel/app/Services/Platform/Saas/PlatformStaffAccess.php), alias `platform.staff`):

1. an **active platform staff identity** (`platform_staff_identities`, `php artisan platform:staff-identity <user_id>`), the exclusion identity every operator has; and
2. an **unexpired platform admin grant** (`platform_admin_grants`, `php artisan platform:admin-access <user_id> [--hours=24] [--revoke]`, 1 to 8760 hours), set only from the operator console.

And, in each browser session, **two-factor sign-in** passed with an authenticator app ([two-factor sign-in](hq-two-factor.md), SA.2): without it every `/saas` page sends the staff member to set up the app or to the challenge.

The existing login accepts a role-free operator only after the same fresh grant checks, redirects them to `/saas`, and gives them no school membership. Default or flagged passwords do not open platform metadata; those operators can reach only the separately guarded `/saas/password` purpose to replace their own password. Both keys must still be fresh and unexpired; impersonation is refused.

The identity alone grants nothing (its spec: "not a grant of platform capabilities"), and neither does a school's Super Admin role. The account must be active and not owe a password change, and access is refused while acting inside a school (impersonation). Revoking either row, letting the grant expire or deactivating the account ends access on the next request. The middleware runs before any record lookup, so a refusal is the same for every school id.

## Acceptance tests

[`tests/Feature/Platform/SaasPanelTest.php`](../../../admin-panel/tests/Feature/Platform/SaasPanelTest.php).

| Given | When | Then |
| --- | --- | --- |
| Staff with identity and grant | they open the list, a school and the set-up page | each answers 200, private (`no-store`), with no email address anywhere |
| A school's Super Admin with no identity | they call any `/saas` route, with any method | 403, and nothing changes |
| Identity only, grant only, expired or revoked grant, deactivated or password-pending account | they open `/saas` | no platform metadata; 403, or the forced own-password redirect for an authenticated operator |
| Any user | a school id that does not exist | 403 for non-staff, 404 for staff |
| Staff | they set up a school | the school, its roles and profile defaults exist; the administrator has one membership, Super Admin of that school; they are sent a one-use invitation whose secret is in no audit row or session |
| An operator with both keys and no school role | they sign in on the normal form | they land on `/saas`; they hold no membership; a role-less account without both keys is still refused |
| The new school's administrator | they sign in and open `/home` | it opens (the school has its own current year, name, time zone and e-mail templates) and shows nothing of another school; the staff member's own session year is not changed |
| Staff | they use an email that already has an account, a taken or malformed short name, an unknown package or time zone | nothing is created |
| Staff | they change school 2's package, status or a module | school 1's rows are unchanged; the audit row names the staff member, the school and, for a status, the reason |
| Staff | they move a school with fee records to a package without fees | fees become read-only (readable, not writable), as when the school's own admin switches them off; a school with no fee records is left alone |
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
| GET, POST | `/saas/password` | fresh operator changes only their own password; pending/default password allowed for this purpose only, CSRF/throttle/no-store/no input flash; no school role or membership |
| GET | `/saas` | schools with status, package and active people by role |
| GET, POST | `/saas/schools/create`, `/saas/schools` | the set-up form; creates the school, emails the first administrator's invitation and shows its link once in that response (throttled 20 a minute) |
| GET | `/saas/schools/{id}` | one school: counts, package, status, modules as the school sees them |
| PUT | `/saas/schools/{id}/package` | an active package key ([package editor](package-editor.md)); the school gets the package's current module set version; a money module (fees) the new package leaves out stays read-only while the school has records; queues `context.changed` for that school. Moving back to a package that includes fees leaves it read-only until someone enables it (the school's own switch or `/saas`) |
| PUT | `/saas/schools/{id}/status` | `active`, `suspended` or `archived`, with a reason of 10 to 500 characters |
| PUT | `/saas/schools/{id}/modules/{module}` | `ModuleGate::change` inside that school, so the rules of the school's own switch apply |
| POST | `/saas/schools/{id}/administrator-invitation` | sends the first administrator's invitation again while nobody has accepted it; the old link stops working; names no person (throttled 20 a minute) |

## Screens

Five standalone Material 3 pages (`resources/views/saas/`, `public/assets/css/saas.css`) on the panel's tokens: list, set up, created (shows the invitation link once) and school and own-password change. They do not use the school layout, which needs a school. Loading and empty states are the table's; every form error shows in a `role="alert"` block.

## Security notes

- **No personal record is returned.** Aggregate counts filter current dated memberships, active schools and active/non-deleted accounts using each school's calendar; the platform staff member gets no role, membership or permission in any school, and no learner, guardian or staff row is listed or opened.

- **First-run defaults.** Set-up gives the school what its own panel needs before anyone signs in, as the installer does for school 1: `school_name` and `time_zone` settings, the e-mail templates, and an explicitly labelled, editable January–December calendar-year setup preset in the school's timezone. This is a bootstrap default, not a Bible-mandated calendar or a published grading policy; it creates no terms, marks or released academic plans. Roles and profile defaults come from the existing seeders. Anything else (grades, classes, terms) is for the school's administrator, through the Home checklist.
- **The first administrator's password** is random and known to nobody. They are emailed a one-use, seven-day link (`StaffInvitations`, [staff-invitations.md](staff-invitations.md)) to choose their own password. The link is also shown once in the response to the POST, never redirected, flashed or logged, as a fallback while mail is not live in an environment. The school page offers to send it again until it is accepted, without showing the person.
- **Existing accounts** cannot be made the first administrator: a person's email already having an account is refused, so this page can never be used to add oneself or anyone else to a school that has people.
- **The install's default school** cannot be suspended or archived: sign-in and public pages run in it.
- **Suspension** works through `MembershipService`: a school that is not active has no active memberships, so its people are refused on their next request, including tokens. Records stay.
- **Own password:** `/saas/password` reuses the existing old-password validation, common password policy, hash save, app-token revocation and current-session preservation. HTML-Accept validation still returns private JSON without flashing secrets. The ordinary school reset routes and role middleware are unchanged.
- **Grant expiry:** `expires_at` is a UTC instant, written and compared in UTC. Applying a school timezone cannot revive an expired grant or end a valid one early.
- **Package downgrade:** the existing module gate preserves retained money in the same transaction as the package change, through one `keepRetainedMoneyReadable` transition. Retained fee history stays readable and read-only; a new Core school without history keeps fees disabled.
- **First sign-in:** a new administrator chooses their password from the invitation and can reach Home/checklist and its entitled widgets; the no-year guard remains valid if no setup preset exists.
- **Audit:** `platform_school_created`, `platform_school_package_changed`, `platform_school_status_changed` (with the reason) and `platform_school_module_changed`, each with the school id and the actor, plus the model-level rows for `schools`, `school_modules` and `platform_admin_grants`.
- **Not done:** step-up re-authentication for destructive actions, and a rate limit beyond the creation form, are left for the support-access spec.

## Self-test steps

1. `php artisan platform:staff-identity <your user id>` then `php artisan platform:admin-access <your user id> --hours=8`; sign in, set up an authenticator app (or type its code) and open `/saas` (a bare `/saas` link is not in the school menu).
2. See the schools and counts; open one; change its package; switch a module; confirm the school's own School modules page shows the change.
3. Set up a school; open the invitation link in another browser; choose a password; sign in as the new administrator; confirm they see only their own school and `/saas` answers 403.
4. Suspend that school with a reason; confirm its administrator is refused; restore it.
5. `php artisan platform:admin-access <id> --revoke`; confirm `/saas` answers 403 on the next page.

HQ's own record of each customer school (stage, contacts, contracts,
timeline) is specified in [`hq-school-accounts.md`](hq-school-accounts.md).
