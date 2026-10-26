# Ledger foundation

The internal ledger records school-scoped, balanced debit/credit entries in
integer cents. Posted entries and their lines are immutable; corrections are
new authorized entries. Balances derive from postings and have a checked cached
projection. Stable idempotency and business-source identities are permanent.

This foundation does not enable a wallet, vendor, payment method or new finance
screen. Existing v1 receipts remain available. Admissions office-fee
acknowledgements are not automatically reposted, and leadership keeps its
existing reconciled receipt basis until a reviewed cutover exists.

Operators can run `php artisan ledger:reconcile --school=ID --json` to check
incomplete/unbalanced entries, balance differences and legacy cents. The default
is read-only. A nightly run emits a critical structured reconciliation failure
for the configured operations alert route. `--rebuild` explicitly repairs cached
balances from valid postings; it does not import, erase or change receipt history.
`money:backfill-cents` is a separate command that writes missing legacy cents.

Before deployment, rehearse the additive schema/trigger migration and recovery
on isolated staging. Do not remove legacy financial fields until opening-balance
and source reconciliation has been reviewed.

See [the kernel/cutover contract](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/specs/money/ledger-foundation.md),
[preparation evidence](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/self-tests/money/preparation.md) and
[scoped security review](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/security/p5-preparation.md).
