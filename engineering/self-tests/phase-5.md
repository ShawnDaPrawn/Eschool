# Phase 5 verification

Phase 5 is not complete. The owner-accepted preparation scope and its executed
checks are recorded in [money preparation](money/preparation.md). This establishes
the internal ledger and Phase 3/4 handover; it does not qualify subsequent billing,
gateways, wallet/vendor/ticket/card payment features or live money release.

The first annual-plan catalogue and enrolment-preview implementation is recorded
in [fee-plan preview verification](money/fee-plan-preview.md). It is separate from
bill posting, payments and the remaining commerce capabilities.

[Family billing verification](money/family-billing.md) records the next Finance
account and annual preview/approve/post increment. It does not qualify payment
allocation, parent-facing statements, refunds, wallets or commerce.

Keep future feature evidence alongside that record, with implementation,
automated checks, staging/provider/device acceptance and production release
tracked separately. A pending external check blocks its affected release,
not independent development. The cancelled pilot campaign remains cancelled.


[Manual payments and private statements](money/payments-statements.md) records
verified cash/EFT, held credit, reviewed allocation and private parent API/app
reads. It does not qualify refunds, wallets, gateways or the rest of commerce.

Credit notes are a merged increment. See the [credit-note verification
record](money/credit-notes.md) for executed tests and the feature PR for final
hosted qualification. Refund policies/payouts and the rest of Phase 5 remain
separate work. No gateway or production release is activated.

Fee items and reviewed one-off charging are the next bounded slice. See
[verification](money/fee-items.md) and the feature PR for final qualification.
Automatic/taxable item rules and the rest of Phase 5 remain separate work.


Bank imports and reviewed EFT matching are the current increment. See
[verification](money/bank-matching.md) and its feature PR; importing evidence does
not post money, and unresolved bank rows are not a development freeze.

[Item VAT verification](money/fee-item-vat.md) records explicit registration,
original tax invoices and credits, private parent tax and accounting exports.
Automatic eligibility and subsequent increments remain separate work.

[Automatic item verification](money/automatic-fee-items.md) covers compulsory and
membership batch charging. Parent opt-in approvals remain the next portion.
