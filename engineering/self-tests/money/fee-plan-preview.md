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

The shared teaching smoke helper now mirrors the current editing value to the
native IME before dismissing it, pumps the edited frame and asserts the edited
controller value before Save. The existing saved-title, review-body, one-effect
recovery and withdrawal assertions remain intact. Application code is unchanged.
Teacher mobile/entry checks: 4 passed; shared teaching screen/repository checks:
23 passed. Targeted analysis has no errors or warnings (one pre-existing brace
style info), and Dart format passed. Native qualification remains required on
the corrected hosted head; the earlier Android assertion is not waived or
classified as an infrastructure failure.
