# Weekly parent digests — implementation verification

Verified against synthetic isolated school data on 25 October 2026.

- Native PHP/MySQL: **18 tests, 176 assertions** for deterministic school evidence,
  encryption, reviewed language versions, exact retries, local Monday scheduling,
  retention, wrong role/owner/school/account, ended guardians and learner
  membership, suspended learner, stale prepared queries, actual Parent API reads,
  actual manager API author/review/list and private panel validation/year guards.
  Every v2 response in this suite validates against the canonical contract.
- The complete global contract run exercised **437 catalogue/route cases** after
  resolving literal-path priority in its test helper. All those cases passed;
  the combined run found one additional positive manager receipt bug (the model
  returned revision zero before refreshing its database default). That model
  default is corrected and the entire 18-test feature suite passes afterward.
- Generic notice scopes/delivery and school table inventory: **16 tests,
  883 assertions**. Configured whole-application PHPStan: zero errors; Pint passes.
- Pinned SDK generation: **1,801 Dart files**, **113 SDK tests pass**. Strict
  configured OpenAPI lint passes without warnings. Existing canonical paths and
  schemas are structurally unchanged; the serialization preserves prior text.
- Shared Digest/Gallery widget and repository regression cohort: **30 tests
  pass**, including 390/820/1280 layouts, exact material reference, missing pinned
  child, token/account replacement, late responses, background erasure, expiry,
  uncertain language revision retry and transient foreground refresh. Both
  transient retry failures were reproduced before fixing them. Shared and Parent
  affected-file analysis has no errors or warnings; informational style lints remain.
- Actual Parent Chrome integration: **PASS, exit 0**. Parent home opens the
  selected child’s update; one tap opens its exact published learning material.
  Returning through the existing reader/library navigation and opening the
  notification reaches the update again. Background clears the body before a
  refused foreground refresh. All private requests retain account/school headers;
  no sibling or Student practice endpoint is called.
- Actual manager Playwright at **390, 820 and 1280px: 3 pass, 42.3 seconds**.
  Creates an immutable draft, approves and withdraws it, checks horizontal
  overflow, serious/critical accessibility findings and own-origin console/HTTP
  errors. The initial local run found a missing test storage symlink; creating
  the standard Laravel storage link fixed the fixture avatar. No application
  assertion or error filter was weakened. TypeScript and actionlint pass.

The language rendering test covers all twelve configured language codes with
explicitly synthetic wording. It certifies deterministic template plumbing,
not translation fluency. Schools must approve their actual wording per language.
Physical devices, live school timing, production delivery and independent fluent
language acceptance were not run. The module defaults off; no provider or
production release was activated. Fresh combined-branch CI remains a merge check.
