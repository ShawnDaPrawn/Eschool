# Peer tutoring browser fixture and the hour of the day — 27 October 2026

Hosted CI run 40412382730 (main, the #307 merge), job 121886064095, passed 381
Playwright cases and failed the first 390px Peer tutoring case in under a
second, on both attempts, before the page opened. The two later widths were
skipped by the serial suite. The report only shows `Command failed: php artisan
tinker ...`, not the exception behind it.

The run started at 22:25 UTC and reached the case at 22:50 UTC, which is 00:50
in the school's time zone (Africa/Johannesburg). The earlier Peer isolation fix
(`peer-browser-isolation.md`) was a different cause.

## Cause

`peer-tutoring-fixture.php` creates the prepared "already happened" session with
the clock set three hours back, because a session cannot be created in the
past. Demo memberships begin on the day the demo school was seeded
(`school_memberships.starts_on`). Between 00:00 and 03:00 school time, "three
hours ago" is yesterday, so every demo account counts as having no active role
and the Peer access check correctly refuses the teacher with 403. Outside those
hours the same fixture passes. The product check is right; the fixture's
precondition depended on the hour.

## Reproduction

A freshly migrated database with `InstallationSeeder` and `DemoSchoolSeeder`,
run at 02:08 school time, with the fixture's exception printed:
`HttpException` 403 from `ProfilePolicy::actor` called by `PeerAccess::actor`
called by `PeerSessions::create` (fixture line 67).

## Fix

The fixture now starts the demo memberships a day before the backdated clock
(`school_memberships` of school 1 on the isolated test database, which the
fixture already insists on). No production code, policy or membership rule
changes, and the Peer match, approval and allocation checks still apply to the
backdated session.

## Evidence

- The unchanged fixture fails with the 403 above at 02:08 school time.
- With the change, the fixture returns its JSON at the same hour, and the real
  `peer-tutoring.spec.ts` passes all three widths (390, 820 and 1280px,
  3 passed in 1.8 minutes) at about 02:25 school time, on a fresh database.
- No other fixture under `admin-panel/e2e/support` moves the clock.

## Not run

- The rest of the Playwright suite and the hosted CI run: this change is one test
  fixture, and CI is the first full run.
- Local-only workarounds, not part of the change: `php artisan storage:link` (as
  CI does) and pointing the pinned Playwright at the pre-installed Chromium
  build, because the matching download is blocked from the sandbox.
