# Academic attendance and offline registers

Homeroom and timetable-period registers use dated enrolment and allocation,
not a learner's current class field. Teachers capture/amend today's assigned
register in school time. Current grade heads for the register's grade/year can
correct past days, with a reason; academic managers may administer registers.
Family reads are fresh own-learner/guardian checks and contain no peer roster.
An ended enrolment cannot enter a later register. No future attendance is saved.

Capture is an atomic, revision-checked batch. An operation UUID and content hash
make exact retries harmless after checking current authority; changed reuse,
stale row/register revisions and out-of-order operations return409. No retry
silently overwrites another teacher. Audit records before/after and amendment
actor/date/source. Homeroom entries project to existing attendances with existing
type contracts; period attendance stays distinct. Old writers cannot overwrite
canonical dates and share the school lock with capture.

QR codes are signed school/learner references (no names or birth dates) with a rotatable
version. They identify a learner, never authorize a write. Only an allocated
staff member may scan into an eligible roster. No anonymous lookup is offered.
Offline rows preserve the original revisions and queue operation IDs in order,
scoped to account/school/register. Resolve conflicts explicitly. Never auto-mark
all absent or present without the teacher's action. Names and pending registers
are removed from view on context change and local data is cleared on sign-out.

School managers choose the absence cutoff in school time (initial editable
10:00 default) and teaching days. The scheduler sends one current unexcused
homeroom absence notice per learner/day to verified current guardians through
Notifier. Missing registers are flagged for follow-up, not guessed absences.
Amendments after capture are audited and notify current guardians through the
registered notification rule. Retried jobs do not duplicate notices. No live
email/push traffic is sent by automated tests.

Provide a shared adaptive tap/QR list, explicit queued/synced/conflict states,
family today's attendance, and a panel overview/settings. Exercise happy,
wrong-role/school/owner, departed rosters, timezone, revoked allocations,
late amendments, duplicate/order/conflict sync, QR tamper and alert cutoff tests.
Browser checks cover each panel page at1024/1280. Real35-learner tap/QR timing,
mobile offline/device rehearsal and delivered cutoff evidence stay separate.
