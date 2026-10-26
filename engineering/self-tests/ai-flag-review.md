# Private safeguarding review

The panel entry is **Wellbeing → Flagged learning content** (`/ai-flags`). A fresh active Safeguarding membership and `ai-flags-review` permission are both required. Neither a school administrator nor a platform exclusion identity grants access. Review remains available when inference is disabled so safety follow-up does not depend on a paid provider.

Retained input/output is read only after school, usage, snapshot and expiry checks. Prepared queries recheck membership and permission. Provider text and review notes are escaped. Review events are encrypted, versioned, bound to a UUID and immutable intent, and cascade when the flag expires. A review never changes the quarantined usage into a learner-visible success and never refunds compute.

Photographs use a server-registered `QuarantineMediaResolver` by exact feature. The feature adapter must reauthorize the existing approved crop against the exact usage and operation. The panel accepts only references already present in the encrypted snapshot, verifies bytes against their retained SHA-256/MIME, limits size and image dimensions, and rechecks scope and expiry after reading. It never accepts a client path or URL. An unregistered feature has no photograph reader. Tutor supplies its own adapter separately.

All responses carry private/no-store, no-referrer and nosniff headers. Invalid private notes are returned only after fresh form ownership checks and never flashed into session old-input. Background/pagehide erases content, image sources and drafts; resume reloads authority. The expiry timer uses bounded intervals for long retention windows.

2026-10-25 local qualification on PHP 8.3.36:

- AI regression cohort: 46 tests, 1,081 assertions, including 8 review tests / 55 assertions.
- School-owned table inventory: 3 tests, 826 assertions.
- Complete PHPStan: zero errors; Pint and JavaScript syntax checks passed.
- Actual Chromium panel journeys at 390, 820 and 1280 pixels: 3 passed in 35.8 seconds. Each reads escaped withheld text, records a private escalation, checks serious accessibility violations/overflow/console errors, and erases content on the hidden event.
- Test fixtures refuse production database names and use public synthetic demo credentials. No paid inference or production permission changes were performed.

Offered panel catalogues contain explicit English fallback keys. This does not constitute fluent approval of translated safeguarding wording. Review is a human follow-up record; it does not claim an external safeguarding service was contacted.
