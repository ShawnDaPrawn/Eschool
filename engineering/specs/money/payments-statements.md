# Manual fee receipts, allocation and private family statements

Third Phase 5 increment, built on the merged annual invoices. Gateways, refunds,
fee adjustments, legacy imports/cutover, bank-file matching, wallets and commerce
remain separate. No live provider, paid resource or production activation occurs.

## Finance workflow

Finance has a separate `fee-payments-manage` permission, checked freshly under
school locks. Record a verified cash or EFT receipt against an explicit family
account and holder, optionally pinned to one learner. The holder must be recorded
on that account's year configuration or sealed invoice history; it is never
inferred from legacy parent fields. Existing financial obligations remain payable
when a guardian membership is later withdrawn, without restoring parent access.
An explicit unique cash-receipt/bank-transaction reference, distinct from the
reusable family reference, prevents a new UUID from recording the same receipt.
Finance confirms the receipt belongs to this ledger workflow and has not already
been recorded in legacy payment history. Earlier receipt tables are not imported,
mirrored, combined or restated. The new statement is labelled as ledger history.

Amounts are positive integer cents in the family currency. Provider is manual,
collector is the school, fee cents are zero, net cents equal received cents, and
the bank/cash date cannot be in the future. Recording debits the fees cash-desk
asset or a dedicated fee-bank asset and credits a family/holder credit account.
Income is unaffected. No clearing or due-to-school posting is introduced.

Finance explicitly configures the school's allocation order (oldest first or
tuition first); both prioritize tuition in the current tuition-only catalogue.
A receipt may remain held credit before that decision or before charges exist.
Allocation has a retained review of the exact charge/share amounts and a
fingerprint. Confirmation rechecks it under locks and applies credit only to the
named holder's recorded invoice shares, respecting a learner pin. Part-payments
reduce individual charges, overpayments remain held, and siblings are ordered
stably by due date, issue date and line ID. Changed policy, credit or outstanding
shares invalidates a preview. Allocation debits held family credit and credits
the invoiced fee receivable, never revenue. Receipts, allocation batches/rows,
entries, audit, events and permanent actor-bound replay receipts commit atomically.
Posted invoices, receipts and allocations cannot be edited or deleted. Refunds,
reversals and payer/debt transfers require separate reasoned workflows.

## Statements and privacy

Finance sees account totals, each holder's charges/allocations, held credits,
receipts and allocation history. Parent v2 reads and the parent app expose only
the signed-in holder's financial shares/receipts for currently fee-visible
learners, using fresh active Parent membership, guardian links and school/module
checks. A different holder, family, school, staff or platform support identity
cannot use those reads. Revocation takes effect on each request. Parent data
never exposes another holder's names, payments, credit balance or account ledger.
Mixed receipts involving a learner the parent can no longer view are withheld
from the parent receipt list/held-credit total; visible invoice lines remain
available. An unallocated receipt cannot restore a family whose learners are all hidden,
even when the parent can view a learner on another account. Statements always
explain their permitted-record scope. Learner
access, marks and reports remain independent of debt.

## Data and interfaces

Add school-owned payment-policy, immutable payments, allocation batches and
payment-allocation rows, composite tenant keys, posted-source uniqueness and
finalization/invariant triggers. Extend ledger account rules only with a fees-only
debit-normal bank asset; preserve wallet nonnegative and Phase 6 zero rules.
Domain reads derive outstanding/held credit from sealed history; reconciliation
also reports incomplete allocation documents and mismatched receipt/credit totals.

Panel: Money payments list, policy choice, receipt form/detail, allocation preview
and confirmation, and account statement. Private forms bind actor/school, carry
UUIDs and CSRF, never flash financial inputs or write sessionStorage. Read-only
history remains when money is disabled. Historical invoice settlement is allowed
through the payment workflow without editing historical invoice/year records.

API: additive parent fee-account list and statement reads, private/no-store,
paginated and contract tested, with generated SDK models. Extend the parent app
with a responsive fee-statement screen and owned session boundary; preserve all
v1 fee/history screens and contracts. No parent payment or withdrawal write exists.

## Verification

Test exact partial/overpayment and split-share allocation, sibling/pinned-child
ordering, held credit, receipt and UUID uniqueness/replay, competing/stale
previews, full rollback, UTC/calendar dates, immutable database history, tenant
keys, read-only modules and fresh permission/guardian revocation. Test parent
ownership and hidden sibling/holder redaction, debt-independent learner access,
API contracts and generated SDK. Run Finance browser journeys at390/820/1280 and
parent app unit/widget/browser/native journeys; retain accessibility, overflow,
wrong-role and error assertions. Require green CI on the exact merged tree.
External cash/bank reconciliation, language/device/staging acceptance and the
independent live-money assessment remain separately recorded release evidence.

Unspent learner-pinned credit prevents removing that payer assignment; any held
credit prevents emptying the family roster. A holder with available credit also
cannot be removed from that year's payer configuration until the credit is
allocated or handled by a reasoned refund/transfer workflow. Fully allocated
receipts do not freeze future holder configuration. Currency freezes at the first receipt,
including a prepayment before invoices. Unbilled assignments without held money
remain correctable. Reasoned refunds/transfers must precede stranded-credit changes.

Statement/allocation reads verify domain history against posted credit/receivable
amounts, scoped in bulk to the affected accounts. A ledger projection rebuild
refuses incomplete/mismatched domain history. Parent pagination keeps independent
charge and receipt page numbers; totals cover all permitted rows, not just the page.
The app displays invoice debt and held credit separately, since reserved funds
cannot automatically settle every debt. Private reads bind actor, school, token
and current context, discard late responses, and clear financial details on errors,
logout, access changes or a school switch. They persist no financial response.

Internal `fees.payment_recorded` / `fees.payment_allocated` outbox events carry
identifiers and cents only; this increment adds no email, push or provider call.
The existing leadership fee adapter still reads legacy receipts. A separately
qualified cutover is required before restating those aggregates from this ledger.
