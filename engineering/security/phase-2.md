# Phase 2 security evidence

The academic reference foundation exposes only shared grade/phase and rating
metadata. Class mappings use current-school lookup, fresh active membership and
class permissions, no mass assignment, transactional row locking and an explicit
before/after audit. Requests cannot select school_id in their payload. Archived
classes remain inaccessible. No learner marks, family records or public file
links are introduced. Legacy class serialization hides the mapping column.

Automated refusal coverage includes teacher/parent/learner, foreign and deleted
classes, missing/expired membership, inactive accounts, school switching with
cached roles, invalid input and idempotency replay. Fresh permission/account checks also run
before a saved response is replayed, including after an admin role expires. The domain service repeats
authorization without depending only on HTTP middleware. The route-wide tenancy
suite additionally supplies a foreign class in the URL.

This is source review and automated evidence, not a completed independent
security assessment or staging penetration test. Those checks and the broader
Phase 2 pilot security/operational checks remain pending; independent development
continues under the recorded delivery policy.

## School terms

Term and template operations resolve the current school before lookup, repeat
fresh permission checks before retry replay and inside locked writes, and audit
changes. Year locks serialize calendar writers; grade/date overlaps are validated
together. Permission IDs/grants and academic links survive the in-place rename.
Referenced terms cannot be removed or narrowed to a different grade range; SQL
foreign keys restrict physical deletion. Template previews do not change calendar
records; retries skip identical entries without overwriting edits.

No independent staging/pilot security test is claimed. The semester compatibility
exception comes from the explicit owner decision, not a blanket API exemption.

Subject standards: global public references are immutable revisions with explicit
source/publication and no school HTTP write path. Imports validate before writes;
revision conflicts roll back the batch. School subject/medium reads use tenant
scopes, subject permissions are refreshed before idempotent replay, and mapping
IDs are hidden from existing raw v1 model payloads. No new person data is stored.

## Curriculum implementation

Fresh actor/permission/allocation checks gate reads and writes before idempotent
replay. Nested IDs resolve under school scopes and grade/year term bounds; copied
source IDs are server-generated. Root school serialization and draft revisions
protect concurrent saves. Published definitions and lock dates reject writes with
409. JSON editor output is escaped and rendered through DOM text nodes; decimal
marks reject floats/scientific notation. School publishers alone publish/carry all
curriculum history. No marks or reports are copied into a new year.

## Teaching groups and dated authority

All nested IDs and people resolve inside the current school. Target staff need
active membership and account status. Group/interval writes serialize on the
school row; stale revisions and overlapping duplicate intervals are refused.
Learner memberships need matching dated grade enrolments over the entire interval.
Fresh management checks precede idempotent replay. Canonical teacher allocation
controls group access; parent/learner and unallocated teacher refusal is covered.
Managed v1 projections have hidden source IDs, stable row IDs and read-time date
scopes; ending an assignment refuses access even before refresh. Projection
writes bypass deletion cascades and historical timetable display retains school
scoping. Existing legacy assignments are not treated as canonical group grants.

Allocation review: mixed groups do not broaden a teacher to an entire homeroom.
Only bound homeroom groups project class authority, with grade/homeroom checks
at refresh and read time. Grade/homeroom moves close incompatible memberships
in the enrolment transaction and retain history. Dated whole-year assignments
are recognized explicitly by term-filtered family teacher/chat consumers.
