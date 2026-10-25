# Relief-cover backend verification

The backend implements school-scoped absence requests mapped to dated timetable
occurrences, atomic conflict-aware substitute assignment, encrypted reasons and
source snapshots, fresh authorization on UUID replay, explicit refresh and
cancellation, immutable audited history and narrow substitute reads. It creates
no permanent teaching allocations. The module defaults off; no live school or
production setting was changed.

- Broad authorization, contract, tenancy and migration cohort: **254 passed,
  2,033 assertions**.
- After additional native source/target checks and regressions: final focused
  cover cohort **14 passed, 52 assertions**.
- Changed-file Pint and full configured Larastan: pass.

Tests cover wrong role/school/owner, stale revisions and UUID conflicts, immediate
permission/module withdrawal, substitute timetable/cover/absence clashes, stale
period/year mappings and refresh, reason encryption/audit redaction, platform
operator exclusion, legacy ambiguity and retained-data rollback refusal.

This records a backend increment. API/SDK, panel/shared-app screens, notifications
and dated timetable presentation are follow-on work before the whole feature
is complete. No device, classroom, staging or external acceptance is claimed.

## API, timetable and delivery integration

Nine private v2 operations expose named choices, bounded date windows, paginated
request summaries, own assigned occurrences, request/review/cancel and manager
assignment. Writes use service UUID receipts; authorization is checked on every
retry, with no separate HTTP response cache containing private reasons. Explicit
private/no-store headers also cover unsafe methods. The owned OpenAPI fragment
and generated SDK preserve date-only values and nullable unassigned teachers.

Assigned cover now appears in the existing dated timetable with the effective
teacher and original teacher metadata. New canonical placements reserve cover
intervals; temporarily stale assignments keep their original interval until
explicit review. Notifications carry a generic body only and recheck assignment
revision, current recipient, source, role and module before fanout and transport.

Focused API/domain/integration cohort: **22 passed, 171 assertions**. Full
configured Larastan and strict OpenAPI lint passed. Two Dart regressions verify
calendar/UUID preservation and decoding an open occurrence with null substitute
and source identifiers. Panel/shared-app workbenches remain the next increment;
this record does not claim a finished phase or external acceptance.
