# Daily operator cash-up qualification

Increment10A derives actual posted cash custody from immutable fee receipts,
school wallet funding, verified parent deposits, fee refunds, adult wallet cash
payouts and vendor settlements. Each root must match its original cash asset
posting once. Every original cash posting must be attributed, and cached cash
balances must match the raw postings. Currency/operator/day reports retain actual
receipt/payout dates or an explicit recorded-date basis for old fee refund roots.
Wallet grants/transfers/purchases and EFT are not physical cash.

Nine backend cases passed53 assertions: exact cash/EFT separation, one permanent
count/replay with no money posting, required private difference explanations,
encrypted evidence, late receipts/stale forms, immutable revisioned corrections,
wrong role/school/operator hash/ended membership, carried-forward cash, paused
modules, unknown postings, projection differences, SQL immutability/retained down,
and actual adult/vendor cash payouts. The vendor wallet purchase itself does not
change physical cash; only its verified outside cash settlement does.

Four browser cases passed without retries at390/820/1280px plus Teacher refusal.
They cover actual count/shortage, original replay, late receipt, correction while
wallet/fees are paused, retained versions and private CSV. Raw cash, count revisions
and ledger entry counts prove that counts never adjust money. Accessibility,
overflow, duplicate IDs and browser errors pass. The first run caught malformed
Blade foreach syntax; the template compiled and all four cases passed after fixing
it. The initial report request now asserts200 before waiting for form controls.

Empty migration up/down/up and populated upgrade passed. Existing ledger, outside
receipt and wallet rows stayed byte for byte identical through the upgrade and
recording/replaying a19.50 count against20.00 actual cash. The explained-50 cents
variance remains a count record; ledger reconciliation passed and retained count
rollback was refused. Initial migration testing caught premature full-role seeding
before installer permissions exist. The migration now registers only its own
permission and grants it to existing system School Admin roles; installer/default
role seeding handles new schools and leaves custom Finance grants explicit.

Fresh static/format/TypeScript/documentation and access inventory results are
recorded below once complete. No app/API contract or SDK changes are introduced
by10A. Recipient-confirmed handovers, physical custody availability and payout
checks remain10B; this record does not close all increment10 or Phase5.

Final access/retention run passed12/1256; an additional real panel/export case
passed1/19 for fresh school/account form binding, cross-school custodian refusal,
private no-old-input errors, replay and evidence-free CSV. Finance may review only
actual recorded custodians or its own initial zero count. Money PHPStan level5,
configured panel analysis, fresh Pint on18 PHP files, e2e TypeScript and the
documentation production build passed. Panel36 labels are explicit fallbacks in
four catalogues, using approved glossary terms where available. Exact-source hosted
CI/review/main and physical/staging acceptance remain separate;10B continues.
