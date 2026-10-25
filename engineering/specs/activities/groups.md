# Activity groups, dated authority and sessions

A school manager creates seasonal activity trees with explicit school-year,
gender/age/grade eligibility. Managers assign dated group admins/facilitators and
learners. Facilitators see only their allocated groups, rosters, sessions and
materials; teachers who coach use the same allocation authority. Learners and
current guardians read only their own current groups and published materials.

Group metadata/membership/staff changes require the explicit activity-groups-manage
permission. Facilitators can create sessions, capture/amend that group's session
attendance and author Markdown materials within current dated allocations.
Historical capture additionally requires allocation on the session date. No
student becomes present or absent because a row is missing. Session attendance
is separate from daily academic attendance. Active-child hierarchy prevents
cycles, cross-school/year ancestry and out-of-parent-season dates. Parents' access
is freshly resolved through canonical GuardianService links; ended links override
legacy relationships. Eligibility is checked explicitly, never inferred by names.

Writes are school-locked transactions with operation UUID/hash receipts, fresh
authority before replay, optimistic revisions and audit. Existing rows are
retained on rollback; destructive down refuses records. Staff allocations and
membership dates are inclusive. Student enrolment must cover the membership.
No facilitator receives Teacher or Super Admin rights. The groups module is
registered disabled by default; enabling it does not enable unfinished features.

API: /api/v2/activities/groups lists scoped groups; group detail and roster;
POST/PUT groups; POST/PUT dated memberships/staff; POST/PUT sessions;
PUT session attendance; POST/PUT authored materials. Structured envelopes and
explicit v2 schemas/SDK accompany routes. Panel and shared phone/desktop clients
follow the same services with no implicit client authorization.

Communication gains activity_group audiences. A parent-group post cascades to
active descendants, but an author must have explicit authority over every group
included (manager, group admin subtree grant, or direct staff assignment).
Publication checks fresh membership/enrolment/guardian/account state. No whole-
school fallback exists for an unallocated facilitator. Separate audience tests
verify wrong school/group, revocation, descendant cascade and guardian changes.

Files are not accepted as an arbitrary URL or another module's private file ID;
this slice supports school-authored sanitized Markdown materials. Private binary
uploads require the existing scanning/encryption pathway in a subsequent slice.
No placeholder download endpoint claims this functionality.

Checks: happy-path manager and assigned coach, wrong role/school/group/learner;
expired allocations and idempotent replay refusal; dates/eligibility/cycles;
revision conflicts and rollback retention; parent roster refusal and relationship
revocation; session roster bounds; Markdown sanitization; v2 contracts; keyboard/
accessibility/overflow panel checks and both app profiles. Sensitive cases are a
separate module using the shared restricted pastoral policy, never this authority.

## Compatibility and cancellation

Activity audience composition is negotiated with `activity_groups=1`. Default
configuration omits new scope kinds and default draft lists omit activity drafts;
a legacy direct editor receives a clear upgrade instruction. Published family
readers remain unchanged. Only the updated native panel currently opts in; the
shared app editor will opt in after its activity-scope widget tests and labels.
The flag never grants membership or audience authority.

Dated membership/staff rows have an explicit active flag. Managers can cancel
future or current allocations without inventing invalid end dates; inactive rows
remain retained and audited. New inactive records are refused. Reactivation
rechecks current person/role/eligibility and overlap rules. Existing session
attendance remains available for authorized corrections after membership is
withdrawn; withdrawal never creates a new attendance entry. Optional write flags
preserve existing state when omitted. Full group PUT clears omitted optional
eligibility fields, matching the SDK's omitted-null serialization.

A parent who is also staff can read their child's group in parent mode without
roster/draft/capture rights. Parent visibility is never reused as staff contact or
message-audience authority. The staff-only contact query excludes it explicitly.


## Large receipt capacity

Encrypted operation receipts use LONGTEXT, including an upgrade for installations
that already created TEXT. A valid multi-learner register can exceed 64 KiB after
encryption. Writes and exact replay must retain the entire response. Narrowing
the column refuses when retained ciphertext exceeds TEXT capacity.
