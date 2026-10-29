# Consolidation formatting, readiness and security checks

Scope: shared financial amount/civil-date/instant formatting; clear Vendor currency
readiness; bounded Finance wallet reads; native session encryption; enforced CSP
on the wallet and Vendor catalogue workbenches. Existing pending-operation UUIDs,
financial histories and server authorization remain authoritative.

Local results before exact-head hosted qualification:

- Core full suite 146 passed; Fee feature 70 and Wallet feature 119 passed. Session
  full suite 120 passed before the extra clear/re-login regression; final native
  migration/fault suite 7 passed. No analyzer errors/warnings on these packages.
- Backend wallet/CSP cohort 21 passed, 244 assertions: current caps, raw/cached
  balance drift, non-Finance and another school, ordinary owner APIs and scoped
  CSP/nonces. Exact-head hosted checks also exercise the integration.
- PHPStan passed; Pint and TypeScript checks are recorded with final PR results.
- All 14 affected responsive-browser checks passed: receipts, wallet controls and
  Vendor catalogue/stock workflows at 390/820/1280px; Teacher permission denials;
  trusted nonced scripts execute and injected inline scripts are blocked on both
  enforced workbenches. The initial run exhausted local disk; the full rerun used
  temporary memory storage for generated browser files and passed in 4.3 minutes.


## Measurement before optimization

A synthetic isolated MySQL fixture used 30 real domain-issued wallets and 100 posted
ledger entries (3,100 postings). No production/staging records were used. One local
read measurement before/after:241 queries / 60 posting scans / 140.78 ms versus30 queries /
1 posting scan / 27.36 ms. Query counts demonstrate the batching change; single local
latencies are not a production performance guarantee. The bounded page reads
fresh owners, limits, school memberships and roles, verifies currency/owner/account
identity and raw postings against the cached account, and rechecks Finance. It
never substitutes a cached balance for the immutable posting sum.

A separate1,000-cash-posting synthetic inspection took9 queries / 10.25 ms, with no
additional allocated memory page observed. Deliberately unknown business sources
were still reported as1,000 unattributed postings and unhealthy. CashFlows' complete
source/balance inspection remains unchanged; this measurement does not establish
large-school load or lock-contention acceptance.

## Native upgrade and backup behavior

The original auth box is read as a whole; its encrypted copy is flushed, reopened
and deeply compared before plaintext files are removed. An interrupted copy retries
from the intact original. Keystore failures and malformed keys fail closed. A
restored encrypted box without its device-bound key cannot revive a stale plaintext
credential and starts signed out. Clearing a protected session and later signing
in does not cause a new login to be discarded as an incomplete migration.

Browser cookie sessions continue using IndexedDB and never persist a bearer token.
Physical iOS/Android backup/restore and locked-device testing remain external.
Fluent AF/ZU acceptance and independent security testing remain separate from code
and structural locale checks. See security/consolidation-threats-and-evidence.md.

## Final cumulative qualification

Final full client suites passed: Core146, Family124, Teacher185, Session121,
Fees70 and Wallet119. The cumulative backend homework/money/leadership/CSP cohort
passed86 tests (743 assertions). Final analyses have no errors/warnings; existing
informational findings remain Core1, Family60 and Teacher58.

Review of the included homework PR reproduced two file defects: copies shared
video thumbnails, and missing files could produce incomplete homework. Copies
now own their uploaded files and thumbnails, check storage-copy outcomes and
remove partial destinations on failure. Notification rows and durable outbox
intent commit in the same transaction as copies, so an intent failure cannot
report an already-created copy as failed. The final homework/read-only-year cohort
passed21 tests (109 assertions), including the three new file/rollback controls.
The original two diagnostic cases failed before the correction, as intended.
