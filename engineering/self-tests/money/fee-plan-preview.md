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
