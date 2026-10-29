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

Fresh review also identified grade-specific calendars in homework reuse. Target
terms now use the existing class-grade term scope before authorization or saving.
A reproduced regression covers both non-Teacher staff and a teacher with a stray
allocation to another grade's term: the wrong calendar is neither offered nor
accepted, while the matching term works. Final homework/read-only-year suite:
22 tests passed,127 assertions. No unrelated academic behavior is changed.

## Release upgrade and final static controls

An isolated MySQL rehearsal started from the production v0.1.0 migration set,
recorded a balanced synthetic journal, and applied all ten pending migrations.
Both accounts, the posted entry and both postings remained byte-for-byte equal;
ledger reconciliation returned no differences. This tests upgrade preservation,
not a production restore drill. The release still takes its real backup.

Both CI analyzer configurations now pass: phpstan.neon and the stricter
phpstan-money.neon. Receipt validation uses the integer filter's existing bounds
without a redundant second comparison. Inert seed fee permissions are hidden from
role creation/editing/display and cannot be posted as a substitute for current
financial authority. Database permission definitions remain for rollout history;
no new financial permission is implicitly granted. The final receipt, role/tenant
and read-only-year cohort passed 37 tests (351 assertions), including role-form
choices and a crafted legacy grant. Pint passed on the affected source.


## Final review: term-specific subject offerings

Homework copy targets must also have an active ClassSubject row for the exact
class, subject, session year and term. Grade compatibility and teacher allocation
remain additional requirements. The regression checks both non-Teacher staff and
a teacher with a stray allocation: another class, another subject, another year
or a deleted offering cannot make an untaught term selectable or writable; the
actual taught term remains usable. Existing term-calendar fixtures now explicitly
configure their subject offerings. This final correction is awaiting hosted
qualification; it was not executed locally because the workspace became unavailable.


## Final hosted integration corrections

The full hosted run on4c2dab73 exercised4,328 PHP cases and exposed13 failures
that the narrower local cohorts had not covered. The hosted browser partitions
also identified the following integration gaps. The correction commit addresses:

- Preserve saved VAT details in the owning school's read-only financial history,
  even before the first posting. A foreign school's VAT row cannot retain an
  unused local fee module.
- Derive dashboard fee-card visibility from the same fresh MoneyPolicy used by
  its endpoint, including revoked grants and platform-operator restrictions.
- Replace the header avatar's inline error attribute with a trusted capturing
  listener; retain strict CSP and add a real-browser fallback control.
- Seed demo tuition plans, optional items and an unassigned setup family account
  in the canonical catalogue. Retire demo legacy fee writes and old Finance grants.
  The setup account creates no charges, postings or payer assignments.
- Exercise canonical posted journals in module/package-retention tests. Retired
  v1 APIs keep their explicit private envelopes, never mutate abandoned records,
  and return HTTP410 for receipt PDFs. Old debt never activates a learning lock.
- Use an isolated school year and actual billing, receipt and allocation services
  for the responsive leadership browser fixture. Check explicit currency and
  the current explanatory copy without deleting financial history.
- Remove deleted legacy files from the shrinking Bootstrap baseline and use the
  shared Material card for Vendor currency readiness. Keep the module-route
  coverage assertion tied to every mapped route rather than a stale route count.

The correction is awaiting a fresh hosted run and review. The workspace is still
unavailable, so these last changes have not been executed locally. Earlier passing
local cohorts and hosted jobs are historical evidence, not a claim that the full
final suite passed. Production deployment has not started.


## Last fixture and runner correction

On041a3a5b the other three backend partitions passed and the corrected CSP,
financial workbench and dashboard-authority browser flows passed. The remaining
two PHP failures were new read-only tests that submitted incomplete forms, so
validation correctly returned422 before the write policy. They now submit valid
bound forms and still require403 and unchanged history.

The custom leadership setup failed before page interaction. It now reuses the
existing qualified fee-payment fixture in a fresh isolated synthetic school,
records and allocates a real ZAR4.00 receipt, and retains the teacher-denial and
responsive/currency checks. Setup failures expose their synthetic stdout/stderr.

The Teacher Android runner received a shutdown signal during compilation after
many successful flows, without a failed Dart assertion in its captured log.
Teacher targets now use the same verified disjoint two-part pattern as Family.
Discovery/exclusions and both original required aggregate check names are
preserved; every part must pass. These last fixture/CI edits require a new run.
No production application logic or migration changes accompany this correction.
