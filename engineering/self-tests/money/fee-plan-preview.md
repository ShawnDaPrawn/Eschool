# Fee-plan catalogue and enrolment preview verification

Scope: first Phase 5 billing increment. Provider-neutral; no selected gateway or
Peach investment. Existing v1 payment/history contracts remain intact.

- Native PHP 8.3/MySQL: 16 tests / 88 assertions passed. Exact cents and uplift,
  durable replay/conflict/stale revision, canonical-grade/class/date matching,
  imported/partial/overlapping enrolment exceptions, future/ended exclusions,
  separate currencies, foreign plans/classes/years/homerooms, wrong profiles,
  revoked membership/permission, password reset/support exclusion, retained-money
  read-only mode, browser account/school binding, validation and zero money writes.
- Synthetic 1,200-learner preview: 27 queries, 0.084 seconds locally; exact
  1,201,200-cent total for annual rates of 1,001 cents. This is a calculation
  measurement, not a timed user acceptance of the complete bill-run workflow.
- Final selected regression cohort: 40 tests / 1,781 assertions passed, including
  all 17 money cases, table tenancy inventory, navigation/locales, year switching,
  permissions and routes. Future-year plan preparation works from that header year.
- Hosted PHPUnit found Bootstrap utility classes in the new views. Reproduced
  the actual standards-test failure locally, replaced them with existing Material
  3 card/table/type classes, and passed the unchanged standards check plus all
  money cases: 18 tests / 94 assertions. The Bootstrap baseline was not expanded.
  All four real Chromium checks passed again on the final Material 3 views
  (43.2 seconds), with the same accessibility and error assertions.
- New money paths passed baseline-free Larastan level 5; selected Pint, browser
  TypeScript checking, whitespace and Docusaurus production build passed.
- Real Chromium finance create/copy/preview at 390/820/1280px and ordinary Teacher
  refusal, including serious accessibility violations, page overflow, duplicate IDs
  and console/HTTP errors: all four checks passed (52.1 seconds). The actual
  first phone flow found caption-contrast and scroll-region keyboard issues;
  scoped product styling and a focusable labelled region fixed them, without
  adding an accessibility allowlist or suppressing errors.
- Hosted CI is tracked on the feature PR and must pass on the exact head before
  merge. Deployment and owner/device acceptance are not claimed.

New phrases have matching catalogue keys in all four panel languages, with
English fallback outside English pending language review. This is not fluent
Afrikaans/isiZulu/Arabic acceptance. No independent live-money security assessment
is claimed by these engineering checks.

This slice does not implement all fee rules, family payers, bill approval/posting,
pro-rata/refunds, gateways, wallets or commerce. It cannot take a payment or charge
a family. No launch gate is claimed; independent development continues.

## Peer fixture integration

Reviewed and incorporated the published source of PR #309, the test-only
[peer clock correction](../peer-browser-clock.md). Its isolated school membership
dates cover the fixture's three-hour backdated session preparation. Application
role, approval and allocation checks remain enforced.

Independently reproduced the old fixture's 403 through `ProfilePolicy::actor` /
`PeerAccess` at controlled school time 2026-10-28 02:15 (Africa/Johannesburg),
with memberships beginning that day. The corrected fixture returned its prepared
session at the same time and preconditions. Each probe ran in an outer transaction
and rolled back. On a freshly migrated/seeded isolated database, the unchanged
finance and peer-tutoring browser suites then passed together: **7 checks in
1.8 minutes**, at 390/820/1280px, including the actual early-morning window.
Fixture Pint and PHP syntax checks passed. Both public PR source histories are
retained for integration; hosted CI must qualify their combined revision.

## Native teaching test input

The combined hosted run found one facilitator Android assertion failure after
22 passing cases: revision 2 still contained the original worksheet title.
The plain widget reproduction passed. Flutter's `TestTextInput.enterText`
documentation warns that injecting text with a real IME connection can leave
that keyboard confused about the editing state. A controlled input probe
confirmed the framework title changed while the last `TextInput.setEditingState`
sent to the platform remained the original title; explicitly mirroring the
editing value made both agree.

Mirroring the framework value to the real IME was insufficient: the facilitator
Android job passed 23 cases, but the classic Teacher job later failed the same
saved-title assertion after 25 passing cases. This was not treated as an
infrastructure failure or retried unchanged.

Both synthetic teaching integration tests now initialize a dedicated binding
with Flutter's registered test keyboard, before starting the app. All injected
text uses that one transport instead of sharing a live Android IME. The helper
requires the registered keyboard and checks the edited title after focus leaves
the field. The saved-title, request-body, revision, one-effect recovery and
withdrawal assertions remain intact. Application code is unchanged.

Teacher mobile/entry checks: 4 passed, including the narrow viewport/keyboard
insets regression; the earlier shared teaching screen/repository cohort passed
23 cases. Targeted analysis has no errors or warnings (one pre-existing brace
style info), and Dart format passed. The final combined revision requires all
hosted checks before merge. These synthetic API smoke tests do not claim real
device keyboard acceptance, which remains separate.

## Documentation runner compatibility

The next documentation run reached Ubuntu 26.04 through `ubuntu-latest` and
failed at browser installation: its pinned Playwright does not support Chromium
on that image. Pin this documentation job to supported Ubuntu 24.04. Browser,
accessibility and documentation checks remain enabled with the same dependency
versions; no product code or deployment credentials are changed. Hosted
qualification is required on the corrected revision.

## Shared finance browser fixture

The complete hosted browser suite passed 388 cases but found a 403 at the new
finance flow. The earlier AI-controls fixture borrows the same Finance account
as a platform operator and leaves its global support identity active. Fee
planning correctly refuses that identity. A controlled transaction reproduced
the 403 after AI-controls setup; restoring the school-only Finance identity
made the same authorization succeed. Both probes rolled back.

The isolated fee-plan fixture now deactivates that account's temporary platform
identity and AI-cost grant before its finance flows. Application policies,
support exclusions and all browser assertions remain unchanged. This reset
is restricted by the fixture's existing isolated-test-database guard.

The unchanged AI-controls and finance browser suites passed together in order:
**10 checks in 1.3 minutes**, at 390/820/1280px, with no retries. This includes
the platform-cost operator's access, school Finance create/copy/preview and
ordinary Teacher refusal, retaining accessibility, overflow and error checks.
Fixture Pint, PHP syntax and whitespace checks passed. The complete hosted
suite must qualify the final revision before merge.
