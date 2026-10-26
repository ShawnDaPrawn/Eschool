# Supervised peer tutoring

The retained Phase 4 feature matches a learner's current mastery root gaps with
an approved peer who has evidenced level 3 or 4 in that topic. A currently
authorised teacher/academic lead supervises the session within their existing
academic group scope. An outside facilitator does not gain Teacher mastery
access. Matching data is staff-only; learners and current guardians see their
own supervised arrangements and service ledger, never a partner's mastery.

The `peer_tutoring` module is off by default and depends on `mastery`. Scheduling
rechecks published topic visibility, current group/year, dated allocations,
approved current participants and the selected match. School-local times use
the existing unambiguous wall-time parser. Sessions are bounded to three hours,
within the current year, and cannot overlap for a supervisor or participant.
Writes are school-locked, revision checked and exactly idempotent.

Only the recorded supervisor or authorised academic leadership can confirm
actual elapsed attendance, cancel or correct a session. Completion appends
integer service minutes for the tutor. Optional explicit house points use the
real HousePoints authority, categories, current house, daily cap, events and
notifications in the same transaction. A correction appends the opposite service
entry and reverses the actual point entry with a reason; it never rewrites
history or restores the staff allowance. Attendance never manufactures mastery.
Missing/disabled point configuration does not stop independent supervised
sessions or service minutes; it is displayed as no points awarded.

No chat, private notes, counselling data or provider inference is added. Events
and idempotency receipts contain references only. Every read/replay reauthorises
the current actor and participants. Family clients are read-only; staff clients
and the responsive panel provide matching, scheduling and attestation. Current
context changes/background erase private matches and input; unknown writes
retain only a bounded encrypted exact intent. Published-head CI, staging,
devices and production acceptance are tracked separately.
