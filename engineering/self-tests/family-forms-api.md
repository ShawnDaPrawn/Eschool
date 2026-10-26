# Family forms API and typed client — 25 October 2026

Nine private v2 operations connect configuration, family/group consent lists,
publication, exact guardian response, managed roster/cancellation and encrypted
Health category read/update. This is the HTTP/SDK delivery; new panel/native screens
are the next increment. Meetings and early-collection passes remain pending.

## Security and protocol evidence

- Full contract/route/tenant/controller matrix: 1,078 tests, 6,745 assertions pass.
  Current school/account headers are enforced; unauthenticated, wrong-role and
  foreign-school calls cannot return private bodies. HTML-Accept validation and
  query-body substitution refuse privately without session flash.
- New typed/private/source tests include current configuration reader/school and
  learner/person bindings, actual publication/read/sign/managed roster/health flow,
  explicit nullable fields, fractional/Z timestamps from Flutter, read-only receipt
  recovery after an account-name change, stale guardian revision and key rotation.
- Existing immutable response receipts recover after a deadline or read-only change;
  creating a new positive response still requires current write/guardian authority.
  New family-list queries are indexed by school/learner/request and paginated.
- The route helper initially generated a numeric consent ID and reached 404 before
  auth. The new exact UUID dimension and finite-route regression now reach auth;
  no route or security expectation was weakened.
- Whole-app PHPStan and Pint pass. Strict Redocly lint passes without warnings.

## Generated SDK

All 357 inherited paths and 932 inherited schemas are preserved semantically,
except the additive optional/no-default FacilitatorFeatures.family_forms property.
That reserved property is not emitted as an implemented menu before its UI exists.
Nine operations and 23 schemas produce 1,977 Dart files with pinned generator 7.25.0.
The second generation has identical bytes for every source file and the manifest.

All 129 SDK tests pass, including five new protocol tests: explicit reader/person
binding, required nullable signing data, exact attestation/operation/revision,
one-member health values and omission of the optional family flag on old payloads.
Dart analysis has zero errors/warnings; 1,065 generated style infos are reported.
No generated file was hand-edited.

## Key and UI boundaries

Historical encrypted records and attestation verification support the configured
application-key ring (APP_PREVIOUS_KEYS, comma-separated prior APP_KEY values).
The actual rotation test retains an old key, replaces the current key, resets the
framework encrypter and verifies the original encrypted consent/attestation. No
live key or environment was changed. Do not retire a key still needed by retained
records; key migration/retirement is an explicit operational change.

Attestations are authenticated server records, not qualified legal digital
signatures. No new browser/native screen acceptance, delivery timing, real child
records, provider activation or whole Phase 4 completion is claimed by this PR.
