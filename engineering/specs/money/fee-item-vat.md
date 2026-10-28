# Fee item VAT

Finance explicitly declares registered country, VAT number and legal seller details,
then chooses each item's no-VAT, exempt, zero-rated, inclusive or exclusive treatment
and rate. There is no assumed statutory rate. Missing registration blocks registered
VAT charges only. Setting and catalogue changes invalidate an unposted review.

A charge freezes the original registration, rate, gross, net and VAT. The ledger
debits receivables gross and credits fee income net and fee VAT liability separately.
Largest-remainder apportionment first preserves each holder's total VAT, then their
learner slices. All arithmetic uses bounded integer cents and exact ratios.

Partial credits reverse cumulative original VAT, including after registration or
rates change. Full credits return the original VAT exactly. Paid credits restore
gross funds to original receipts with existing learner restrictions. Immutable SQL
seal guards validate tax accounts, balanced posting identities and cumulative VAT;
this does not change historical no-VAT invoices or create cash refunds.

Finance has registration settings, reviewed tax breakdowns, original tax invoices
and credit breakdowns. Parent API/app statements expose only their own original,
credited and current VAT; legal Finance snapshots and other holder amounts stay
private. Package accounting exports map the VAT liability explicitly and use a
zero-tax journal label because tax is already posted, preventing double taxation.
CSV charge exports append VAT columns without shifting existing columns.

Disabled fees retain readable history and deny writes. Existing role, school,
account-binding, guardian, permanent replay and reconciliation checks apply.
New schema rollback refuses retained tax records; unused/no-VAT history supports
migration rehearsal without changing posted financial records.

Automatic compulsory, opt-in and membership items are the next portion of
increment4. No gateway, refund payout, production activation or external tax advice
is implied. Schools must validate their registration, rates and legal invoice use
before real-money launch; that evidence does not freeze other development.
