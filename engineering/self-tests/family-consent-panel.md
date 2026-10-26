# Family consent workbench — 26 October 2026

The responsive staff workbench publishes immutable activity/trip consent and
shows a current, paginated participant permission roster. The real Parent API reads the exact wording and school dates and records
account-name attestations or withdrawals. The new native Parent reader follows
separately; the existing app-only Parent web sign-in restriction is preserved. This
increment uses the existing consent services and nine-operation API contract;
there is no SDK regeneration or new client authorization shortcut.

The eligible-participant picker resolves the complete actual event date span,
current Student membership, enrolment, approval and activity membership. A retained
ineligible classmate is excluded without blocking the valid roster. The publish
transaction rechecks every selected participant and staff allocation. School wall
times resolve to one UTC instant; invalid dates and DST gaps/folds are refused.

The managed screen distinguishes current effective permission from a retained
last decision. Cancellation retains the wording and signing history. Read-only forms retain staff cancellation and existing family API withdrawal;
positive permission still requires write authority.
Private validation reflects only bounded, escaped drafts after fresh school/account
and group checks. It never flashes fields or errors into the session.
Family-only accounts cannot enter the staff workbench.

## Automated evidence

- Consent/domain/panel and school-time cohort: 27 tests, 206 assertions pass.
  Includes publish → sign → withdraw, wrong school/role/owner, stale event
  enrolment and duplicate retained memberships, private draft escaping/no flash, read-only cancellation with retained attestations, and exact
  Johannesburg/London UTC conversion with DST gap/fold refusal.
- Existing Bootstrap-free, design-token, controller-response and navigation guards:
  14 tests, 647 assertions pass. TypeScript type checking passes.
- Whole-app PHPStan and Pint pass.
- Actual browser workflows at 390, 820 and 1280 px: all three pass (52.1 seconds).
  Staff publish exact escaped wording, the real account-bound Parent API reads and
  signs it, the staff roster shows current permission, and withdrawal removes
  effective permission. Axe serious/critical, horizontal overflow and own-origin
  browser errors all pass. Browser fixtures use only isolated synthetic records.

Native family/Teacher/Facilitator readers and the new restricted Health editor
remain the next increment. Meetings, early-collection passes, delivery notices,
real devices, independent acceptance and whole Phase 4 completion are not claimed.
