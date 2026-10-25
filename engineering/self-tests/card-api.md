# Private card HTTP and typed client

Seventeen additive operations connect owner card lists, management choices and
issuance, sanitized private portraits, signed credentials, freeze/replacement/
restore, device registration/revocation and online QR/SUN scans. Reader credentials
choose context only after a constant-time secret check against credential metadata;
untrusted school headers cannot select another school. They authorize only the
three isolated reader endpoints and cannot authenticate normal v1/v2 user routes.
Normal user tokens cannot authorize a reader endpoint.

Every response, including unauthenticated and HTML-Accept validation refusals,
is private/no-store with no-referrer. Writes reject query-string fields and do
not flash private drafts. Portrait delivery is binary authenticated JPEG. Account
views retain the shared expected-account binding; device authority is separate.
Completed signing epochs erase retired private signing material while preserving
bounded public verification. Chip profiles consistently store encrypted hex keys
for both recovery and scan verification; raw key material never enters the API.

Validation:
- Final affected card/crypto/typed HTTP/contract/catalogue/tenancy guard cohort:
  81 tests, 463 assertions, all passing. The earlier full 1,047-case matrix found
  four numeric report-card sampler regressions caused by an overly broad UUID
  sample predicate; the predicate now matches only the three new route prefixes,
  and all affected report-card and new card routes pass the final cohort.
- After merging the current integration, all 38 card/crypto/table-inventory
  tests pass with 1,011 assertions; whole-application Larastan remains green.
- Pint and strict Redocly lint pass.
- All 120 generated SDK tests pass, including explicit nullable card history,
  isolated proof/device shapes and old facilitator payload compatibility.
- All 330 previous paths and 878 schemas from the MyGrade contract are preserved;
  the only existing-model addition is the optional/no-default cards capability.
  Pinned generation produces 1,881 Dart files; the second generation is checked
  byte-for-byte. SDK analysis has zero errors/warnings and 1,017 style infos.

The card screens, printer exports, physical encoding/device acceptance, offline
scanning and restricted classroom entry remain separate delivery work. The HTTP
upload scanner is synthetic in tests. No production/hardware/store/payment
acceptance is claimed.
