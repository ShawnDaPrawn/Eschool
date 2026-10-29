# Canonical fee cutover verification

Scope: the accepted whole-app review's F1 and dependent callers, following the
session-security/navigation fixes. No Phase 6 or payment adapter is included.

Verified locally during implementation:

- New real-domain receipt/allocation coverage: held credit stays out of grade
  totals, allocation appears once, currency/date boundaries apply, another
  school's year is refused, paused-module history remains readable.
- Old collection writes are absent; bookmarks redirect; authenticated v1 writes
  return a private explicit update-required response and create no current money.
- Financial school years cannot disappear via academic cleanup.
- Leadership's non-financial signals and principal/role/school checks retained.
- Family full suite:124 tests passed; analysis has60 existing informational
  findings, no errors or warnings. Follow-up removal of unused school-year fee
  properties requires the final focused rerun recorded in the PR.

The broader contract/privacy/academic cohort ran736 tests:733 passed initially;
its three expected retirement assertions were corrected (13 reviewed snapshots,
the permanently neutral learning-lock hint, and disabled legacy amount handling).
Those three affected cases are rerun separately; do not claim that initial run
was fully green. PHPStan passed.

Final cohort, contract/snapshot, static, responsive-browser and hosted exact-head
results are recorded in the PR after completion. Initial runs correctly exposed
obsolete tests that still required retired collectors; those tests are adapted
to the explicit retirement contract, without weakening canonical financial or
privacy coverage. Do not treat an initial diagnostic failure as a passing check.

Production rollout is separately authorized by the owner. Require qualified main,
protected release, backup and live smoke evidence before calling it deployed.
Physical devices, native stores and fluent locale approval remain separate.

Final local responsive cohort:14 checks passed, including complete receipt/
allocation/statement flows at 390/820/1280px and Teacher denials. The final API
contract and three affected retirement controls passed (4 tests, 206 assertions);
the final historical-year fixture check passed (1 test, 4 assertions).
