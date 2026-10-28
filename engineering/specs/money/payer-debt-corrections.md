# Remaining fee lifecycle: payer debt corrections and explicit late charges

Payer correction is an immutable unpaid-debt correction within the original
family account, directed to an explicitly selected current configured payer who
is a verified guardian for the original learner. It does not change original
invoices, learner identity, receipts, allocations, paid credit or payer rosters.
Finance can first update future payer shares through family-account setup; this
operation corrects the selected unpaid historical share. Preview includes old/new
payer, original invoice/share and current outstanding, exact preserved tax, due
date and reason. Atomic credit of unpaid old debt plus equal replacement invoice
preserves net school income/VAT/debt. A stale source/payment/payer preview refuses
posting. Further correction requires a fresh reviewed amount against actual debt.

Use the existing FeeCharge/invoice sealing and statement/allocation infrastructure
with explicit charge kinds (fee_item / payer_correction / late_charge); adjustment
kinds have no fake FeeItem or current-item price. FeeCharge's source, totals, tax,
immutable invoice and bill-run guards extend additively. New correction lineage
binds the original sealed unpaid-only credit and new exact debit/holder; original
financial rows stay immutable. Current statements and parent/teacher clients keep
existing generic fee-item compatibility and exact descriptions.


Late charges remain a separate remaining increment. Gateway integration stays deferred.
