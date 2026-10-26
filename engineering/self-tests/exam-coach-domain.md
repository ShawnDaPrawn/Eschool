# Exam coach domain verification

This increment implements encrypted, immutable school-reviewed question/memo
versions, explicit rights attestation/expiry/withdrawal, private generated PDFs,
and deterministic plans counted back from the owner's published exam timetable.
It contains no API, app entry, paid inference or photo-feedback implementation.

The final native MySQL cohort passes 17 tests and 1,216 assertions (16/916 plus the corrected rehearsal 1/300): thirteen
domain cases and four tenancy inventory/rollback/backfill cases. Domain coverage
includes student memo exclusion, encrypted storage, wrong role/school/owner,
guardian and teacher-allocation withdrawal, exact intent replay, stale revisions,
rights expiry/withdrawal with encrypted reason/actor records, real paper/memo PDF
rendering, repeated topic tags, bounded integer scheduling, open-ended enrolment,
exam rescheduling and mastery-visibility withdrawal. A basic plan still works
when the mastery module is off. No plan action changes official marks.

The updated union retains family forms and the qualified Tutor clients. Its
first migration rehearsal found the inherited consent lookup index was needed
by a MySQL foreign key; the Phase 4 owner supplied a reversible fallback-index
correction, then the full rollback/backfill rehearsal passed (1 test / 300 assertions).

Affected and whole-application static analysis pass. Owned PHP files are
formatted. The native runner uses the agent's isolated test database and copied
autoload map with shared unchanged dependencies; the sparse worktree needs an
empty public directory for the existing PDF provider, not production fixtures.

Next: private staff/Student HTTP contracts and clients, source-aware library
listing/configuration, then bounded answer-photo feedback and teacher review.
Real-model safety, legal/provider qualification and physical photo behavior are
unrun. No synthetic licence or protocol fixture certifies external acceptance.
