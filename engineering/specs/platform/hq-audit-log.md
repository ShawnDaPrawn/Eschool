# HQ audit log

Action Plan item SA.3. Navigate Ed staff need to see who at HQ did what to which
school, when, and why, without opening any school's own history.

## What counts as a platform action

`audit_log` already records every audited change, in every school. The HQ log
(`App\Services\Platform\Saas\PlatformAuditLog`) reads only the rows that are
platform actions:

- an action named `platform_*` or `ai.platform_*` (school set-up, package,
  status and module changes, school accounts, contacts and contracts, the
  platform AI cost grant, and exports of this log);
- a change to a platform staff identity or platform admin grant (who was given
  or lost HQ access, including from the operator console);
- anything done by a platform staff account.

A school's own history (a Super Admin editing the school profile, a teacher
capturing marks) never appears.

## Page

`/saas/audit` ("Audit log" in the platform bar; "Audit log for this school" on
each school page). Newest first, 50 a page, in South African time. Filters:
school, action, staff member, and a date range. Unusable filter values are
ignored rather than failing the page.

Each entry shows who (name and email, or "Operator console" for the command
line), the school (or "Platform"), the action, the record it touched, the
reason where one was given (a status change requires one), each value before
and after, and the address it came from. Values whose names look like secrets
(password, token, secret, hash, key material, OTP) are shown as `••••`.

## Export

`/saas/audit/export` downloads the filtered entries as CSV, newest first, at
most 5,000 rows (narrow the filters for more). Cells that a spreadsheet would
run as a formula are prefixed with `'`. Each export is itself recorded as
`platform_audit_exported`, with the filters used and the row count, and shows
in the log. Limited to 10 exports a minute.

## Access

Platform staff only: the `platform.staff` middleware, checked again in the
controller against fresh rows. School staff, including a school's Super Admin,
are refused.

## Tests

`Platform/HqAuditLogTest`: platform actions with reasons and before/after
values are listed and a school's own history is not; each filter narrows the
list and bad values are ignored; the CSV export escapes formulas and is itself
audited; secret-looking values are hidden; school staff are refused.
`saas-panel.spec.ts` opens the log for a newly set-up school at 390, 820 and
1280 px with axe and an overflow check.

## Not yet

Requiring a reason for every sensitive action arrives with platform roles
(SA.1) and audited support access (SA.9), which also add their own actions to
this log.
