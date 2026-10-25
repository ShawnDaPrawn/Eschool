# Dated relief cover

A teacher requests cover for their own absence; an academic manager or current
principal can request for another teacher and allocate a substitute. The request
maps actual canonical timetable occurrences, including alternating weeks and
date-specific lessons. It never invents a class from an unmapped legacy row.
Ambiguous legacy placements must be mapped before requesting cover.

Requests, mapped occurrences and UUID receipts are tenant scoped. Reasons are
encrypted and visible only to the requester or managers, never substitutes or
families. No medical evidence upload is introduced. Writes use a shared school
lock, fresh roles/module state, UUID payload receipts and optimistic revisions.
Cancellation retains audited history and releases the assignment.

Substitutes must be current active teachers, available on the occurrence date,
with no overlapping own lesson, reserved other cover or known absence. Source
lesson/period/allocation changes mark a mapping as needing review. Refresh is
explicit, re-maps occurrences and clears stale assignments. Assignment itself
never updates permanent teaching allocations or grants marks/case access.

Only the missing teacher, an assigned substitute and authorized managers can read
a cover occurrence. Substitute output omits private reasons. Query-time source
and authority checks suppress stale/deactivated cover. Timetable integration
must show the dated substitute and prevent new placements that collide with a
valid cover; existing family/timetable visibility rules still apply.

First backend work owns models, authority, state transitions, audit and clash
checks. The API contract, phone/desktop workbench and notifications are required
follow-on integrations before claiming the complete relief-cover feature.

Test request/map/assign/cancel/refresh; wrong-role/school/owner; module withdrawal,
stale revisions and UUID conflicts; own-lesson/cover/absence clashes; changed
source evidence and no permanent allocation grants; encrypted reason and audit
redaction; and safe migration retention. Live classroom/device acceptance remains
separate from implementation and automated evidence.

API writes deliberately use durable domain UUID receipts instead of caching
private HTTP result bodies. A repeat call rechecks current access and returns the
current authorized record. Request windows and occurrences are separate typed
resources; substitutes cannot retrieve the absence resource or its reason.
Unmapped legacy checks respect the actual term dates. Mutation context changes
roll back all rows and restore the caller school.
