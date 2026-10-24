# Academic register operations

Migrate before enabling the attendance capability in clients. Keep the scheduler,
outbox worker and notification transports running. The every-minute
`academics:register-absence-alerts` command uses each school's timezone, teaching
days and cutoff. The initial editable cutoff is10:00, Monday–Friday. Set the
school's actual policy through the manager settings API before pilot rehearsal.
Missing registers are follow-up work, never inferred absences. Period absences do
not overwrite a homeroom register or trigger its morning alert.

Teachers need a current dated homeroom or lesson allocation. A register's roster
comes from enrolments/memberships effective on its date. Teachers change the
school-local current day; grade heads with a current grade/year appointment can
correct earlier days. Corrections require a reason and preserve actor/date/source
and before/after audit. An original legacy attendance row is adopted into the
homeroom projection without changing its ID. Legacy writers share the school
lock and cannot edit a canonical register through old models/imports/endpoints.

Sync operations carry a UUID, original register and row revisions and a roster
fingerprint. Exact duplicates replay only after fresh authorization. Conflicting,
out-of-order or changed-UUID payloads fail409 atomically. Preserve pending work
and compare it with the current server register. Never automatically rebase a
conflict. A queued previous-day correction needs the grade head; an offline
client timestamp is not authority to bypass that rule.

A QR code is a signed, versioned school/learner identifier containing no names or
birth dates. It identifies a roster entry and cannot authorize a write. Family
reads/rotation require current own-learner or guardian rights. Staff receive
codes only for their authorized roster to support offline scanning. Rotating the
code invalidates cached copies; reload/review before syncing a rejected scan.
This is attendance identification, separate from Phase4 card sign-in/payments.

Absence notices are once per eligible learner/day/current guardian; approved leave
suppresses the morning alert. An amended entry has a distinct revision notice.
Notifier enforces school module/rules/preferences and channel idempotency. Jobs
recheck live guardianship before email/push transport; a removed link cannot
receive a queued child's notice. Transport delivery evidence is operational:
queued/synthetic notifications are not proof a parent received the alert.

Rollback refuses to discard canonical entries; restore a tested backup instead.
Rehearse timezone midnight, old-day queues, conflicting devices, moved/left
learners, rotated codes, revoked allocations and actual cutoff delivery on
staging. The backend slice does not certify mobile QR/tap timing, physical-device
offline use, independent review or full-term pilot acceptance.

Captured period dates and learner/teacher/period identity survive timetable edits.
Room moves and future range changes remain possible when captured occurrences
still exist. Split future placements when changing the dated identity; cancellation
preserves the historical register. Register labels retain the captured context.

Correction reasons are encrypted in storage; audit snapshots retain ciphertext,
not plaintext explanation text. App offline caches require account-scoped secure
storage in the client slice. Neither logs nor outbox events carry the reason.
