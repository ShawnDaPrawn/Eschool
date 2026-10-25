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
