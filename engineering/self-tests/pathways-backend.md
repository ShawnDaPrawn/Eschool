# Pathway reference backend and SDK qualification

School-maintained human-reviewed admission sources, immutable programme rules
and Grade 9 guides, and private transient scenario/released-mark projections.
The `pathway` module is off by default. No institution seed, admission guarantee,
AI provider or academic mark write is introduced.

## Executed locally

- Domain/API/panel/score/migration: 19 tests, 169 assertions after correcting
  the module key. Additional tenancy/inventory 3 tests, 917 assertions passed
  against the same table layout.
- Selected PHPStan including panel navigation: zero errors. Changed PHP passes
  Pint; source-form JavaScript passes Node syntax checking.
- Strict OpenAPI 3.1 lint passed.
- Final SDK generation repeated with identical hashes for 2,273 Dart files
  (2,274 generated-source/manifest entries). All 156 SDK tests pass, including
  exact request binding, null evidence and date-only maintenance wire values.
  A real RED/GREEN calendar-date regression caught generated date-time values
  where the maintenance API requires source review/expiry calendar dates.

## Remaining qualification

Actual manager panel browser checks pass at 390/820/1280. The shared Student/
Parent planner, guarded entries, lifecycle/refusal erasure and complete actual
Student/Parent Chrome journeys also pass; see [client qualification](pathways-clients.md).
Published-head CI, staging, school source review, fluent wording, physical
devices and production acceptance remain separately recorded.
