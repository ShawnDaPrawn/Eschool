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


## Exact marks and result release

Fresh school membership, current subject allocations, dated grade leadership and
explicit marks permissions are checked before cached write replay and again in
transactions. School/task/question/learner/context IDs resolve under tenant scopes.
Canonical marks are per learner/task across supporting groups; only the chosen
group roster is exposed in a paged matrix. Decimal writes reject floats, exponent
notation and out-of-range totals. CAS revisions prevent lost edits; moderation
corrections need reasons and reset moderation. School-local lock dates return409.

Immutable snapshots/releases preserve evidence. Preview fingerprints refuse a
stale release; incomplete learners can progress independently through an explicit
provisional mode. Family reads use fresh own-child rights, including ended-link
overrides, and never consult fees. Released legacy projections reject older model
edits, deletes, bulk timetable inserts and publication bypasses. Outbox listeners
select their event's school explicitly. No independent security review is claimed.

## Attendance clients and panel

Account-owned encrypted offline queues retain original revisions and operation
UUIDs. Fresh server permission checks precede online restore/replay. Revoked
access clears visible data before best-effort deletion; late callbacks cannot
repopulate a disposed or replaced scope. Conflict review is explicit, never an
automatic overwrite. Queue persistence failure keeps the draft visible and never
claims successful saving. Logout and account replacement purge caches and keys.

Panel captures enforce bounded request size and row counts before atomic service
writes. Missing entries never become automatic absences. Role, school, allocation
and earlier-day checks are service-enforced on each request. Views escape roster
names and tokens; QR matching uses direct string comparison and DOM text output.
Family endpoints remain own-child only and return no staff roster/reasons. Private
no-store responses and app school/child boundaries protect scope changes.

## Report cards

Fresh scoped school/staff/leadership/allocation or own-child checks precede HTTP
writes and idempotent replay. Current school/grade leadership governs report
history across calendar rollover. Subject/homeroom teachers have only their own
comment scopes; administrative corrections need an encrypted audit reason.
Comment CAS, source fingerprints and ordered approvals prevent stale release.
Changing signed content resets signatures; approval fingerprints include the
signature cycle revision, so an old queued renderer cannot reuse a later approval.
Released batches/cards/comments reject mutation and moving source references.

PDF input is escaped text, with no remote/PHP/JavaScript execution; crest input is
bounded decoded raster data re-encoded as PNG. Evidence/comments/PDF bytes are
encrypted at rest on a private disk. Signed ten-minute downloads bind requester,
school and staff/family purpose and require fresh access. Family list/downloads
never query fees. Report notices deduplicate per recipient/card and recheck live
guardianship at both queued transports. This is implementation/self-review, not
independent staging/pilot security acceptance.

Report client checks: metadata cannot redirect an authenticated PDF fetch to a
foreign origin/card/school or duplicate signed query; private asset redirects are
disabled. Guardian revocation clears paged history/PDF refresh state; late child
responses are ignored. Panel retries reauthorize before encrypted, school-bound
replay; body and receipt commit atomically. Draft comparison never auto-overwrites
competing comments. Native panel and app checks remain tied to final CI evidence.


## Dated learner changes and private exit exports

Office authority precedes every read/write/replay; preview fingerprints and
actor/school-bound operation UUIDs prevent stale or duplicate changes. Dated
learner access is enforced before token issuance and on requests, independently
of legacy projection timing; another school and parent/staff roles remain usable.
Reasons/input/result and export payloads are encrypted. No financial posting or
unapproved learner activation occurs. Existing canonical marks remain unchanged.

Exit archives are requester owned, fresh-sensitive-export and document-field
checked, source-pinned, size/count bounded, signed Ed25519 and encrypted at rest.
Links bind reader/school/expiry. Jobs recheck source/authority before and after
building; stale attempts cannot publish. Scratch is private and cleared in finally.
Copy expiry deletes only validated owned paths/payloads and retains source records.
Public-key provenance requires comparison with authenticated metadata. Independent
security review, approved wider retention schedule and actual recipient hand-off
remain acceptance evidence; local tests do not establish those outcomes.
