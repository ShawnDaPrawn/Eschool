# Whole-app navigation and accessible Teacher actions

29 October2026. Findings F5–F7 implemented on the session-security patch.

- Actual Teacher action grid with bundled Poppins:9 tests passed at320/360/1280px
  and100/200/300% system text. No render exceptions; last-row scrolling, button
  semantics and destination activation remain available. No text-scale clamp.
- Teacher theme, locale parity/glossary/placeholders and shared-label controls:
  7 tests passed. Teacher analysis has no errors/warnings;58 existing infos remain.
- Backend checks exercise partial reader/Vendor permissions separately, both
  permissions together, wrong-school membership, deactivated accounts, unused
  disabled module and retained-history revocation. Existing reader bound-form
  and receipt/allocation journeys plus the navigation suite are included.
- Money uses five task groups; existing navigation label/route/search/visibility
  checks remain in place. Current allocation help replaces obsolete annual-only
  copy, including Afrikaans/isiZulu and panel language key coverage.

Final backend cohort:12 tests /832 assertions passed. PHPStan and changed-file
Pint passed. No native-device,
fluent-language or production acceptance is claimed. Hosted CI/review/main remain
separate until recorded. Phase6 and payment gateways remain excluded.
