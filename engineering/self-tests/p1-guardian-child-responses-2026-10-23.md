# Legacy guardian child responses — 23 October 2026

Five regression cases failed before the fix: ended links still appeared in
profile/sign-in, explicit links were omitted, a father's child could bypass the
current-year filter, and a guardian without fee rights saw the fee-due flag.

After using the canonical child query and fee visibility boundary, 75 tests
passed with 799 assertions: new cases plus guardian approval, portal sign-in,
API regressions and all v1/v2 response snapshots. Wrong-school and wrong-role
requests were refused. Pint, Larastan and `git diff --check` passed. No snapshot
update was needed. All data was from local two-school fixtures.

PR CI must pass before merge. Real-device/staging verification and owner
acceptance remain separate; no production data/configuration changed.
