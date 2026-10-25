# Activity management and shared group views

Extend the existing panel and Flutter shells without adding privileges to a
flavour or inferring authority from a selected menu. The API group service remains
the source of definitions, dated membership, staff scope and register rules.

## School management panel

Only the explicit activity-groups-manage permission opens the workbench or writes
through it. Resolve fresh school authority before loading names or submitted
records. Definitions, parent groups, eligibility, seasons, learner and staff
allocations, cancellations, sessions, attendance and authored materials use the
same services and operation receipts as API v2. Person selectors show names from
the current school and canonical enrolment, never an unscoped directory or raw-ID
input. Forged foreign-school and wrong-group record IDs are refused.

Calendar dates are inclusive. Attendance has an explicit unrecorded choice;
blank entries are omitted, and no learner is silently marked present. Future
sessions can be planned but attendance opens on their date. Past/future school
years remain read-only in this panel, even if a caller forges a write while the
header points at the current year.

Validation errors retain the submitted form in the same private response with
its UUID. No plain draft is flashed into a shared session. A conflict shows the
saved record and submitted text; saving again requires deliberate review and a
new operation against the current revision. It never silently overwrites a newer
record. All fields are escaped; only server-sanitized Markdown is rendered.

Use Material 3 components and tokens, unique field labels/IDs, keyboard controls,
responsive cards at 1024/1280px, private no-store responses and localized labels.
Afrikaans/isiZulu translations require fluent review before claiming linguistic
acceptance. The workbench is an actual administration surface; the reserved
native facilitator flavour remains unavailable until scoped sign-in is delivered.

## Shared apps (next increment)

Teachers who coach, parents and learners open the same group list/detail views.
Server flags decide whether sessions, materials and registers can be edited;
parents receive no peer roster or private materials. Pin repository state to the
account, token and school, clear private state on context changes, and revalidate
after resume. Keep attendance/material drafts on transient errors and retry an
uncertain save with the same UUID. Never persist private group state unencrypted.
The message editor opts in to activity scopes only after widget-level round-trip
coverage proves it renders and writes the exact new scope kind.
