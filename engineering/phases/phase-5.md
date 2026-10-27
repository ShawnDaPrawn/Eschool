# Phase 5 — money and commerce

Status: ledger foundation, fee plans, family accounts/annual billing, manual fee
receipts/allocation, private parent statements, credit notes and fee items/one-off charges are merged.
Bank imports and reviewed EFT matching are the current implementation increment;
qualification and integration are tracked on its feature PR.
The full phase is not complete; hosted qualification is tracked on each PR.
The owner accepted the bounded handover pass on 26 October 2026. Phase checkpoints
remain progress records: unrelated development continues while external evidence
or a business-policy decision is pending. No cancelled pilot campaign is
reintroduced as a development gate.

## Preparation scope

- Remove fee-dependent learner/child navigation while retaining guardian privacy,
  approval and tenant guards.
- Establish immutable, balanced integer-cents postings with permanent receipt
  identity, safe concurrent spending, retained history and reconciliation.
- Test joined admissions/guardian/enrolment/transfer journeys and define receipt
  cutover without historical reposts or duplicate collections.
- Correct shared notification retry/UTC recovery, document exact unknown-outcome
  recovery and keep receipt history outside generic retry-cache retention.
- Cache Gradle/Maven dependencies, bound transport retries, and analyze the new
  money kernel at Larastan level 5 without a baseline. Behavioral checks remain
  required; no automatic test retries or gate waivers.

[Ledger and integration contract](../specs/money/ledger-foundation.md) ·
[Learning access](../specs/money/learning-access.md) ·
[Preparation evidence](../self-tests/money/preparation.md)

## Subsequent implementation

The first billing increment implements annual plans for all learners, canonical
grades or school classes, revision-safe editing/archiving, draft new-year copies
with exact cents uplift, and a read-only dated-enrolment preview. See the
[fee-plan specification](../specs/money/fee-plan-preview.md). This preview does
not create invoices, choose payers, calculate policy-dependent pro-rata or post money.

The next increment adds explicit year-specific family holders/shares, learner
payer assignments, immutable bill previews, approval and atomic annual invoice
posting against receivable/income accounts. It excludes already invoiced annual
charges and exposes missing/invalid payers and unsupported policies as exceptions.
See [family billing](../specs/money/family-billing.md). Policy-dependent charges remain separate work.

The previous increment records verified manual cash/EFT receipts, holds credit for
an explicit holder/optional learner, reviews and posts allocation, and exposes
private parent statements in API v2 and the parent app. No provider is selected.
See [payments and statements](../specs/money/payments-statements.md) and its
[verification record](../self-tests/money/payments-statements.md).

The previous increment adds reasoned credit notes against recorded invoice shares,
reduces unpaid debt and restores paid money to original holder receipts with learner
pins retained. Original documents remain immutable; Finance reviews the exact
correction before confirming. See [credit notes](../specs/money/credit-notes.md)
and [verification](../self-tests/money/credit-notes.md). No automatic school refund
formula or payout is selected.

The previous increment adds a revisioned additional-item catalogue and reviewed
one-off learner/family invoices. Family charges are billed once and apportioned
using exact holder totals and real learner records. Annual tuition uniqueness,
credit-note corrections and learner pins remain intact; tuition-first allocation
now distinguishes tuition from additional items. See [fee items](../specs/money/fee-items.md)
and [verification](../self-tests/money/fee-items.md).

The current increment retains reviewed bank CSV evidence, suggests family matches,
links existing EFT receipts or records one verified receipt, and keeps unresolved
rows in a private queue. See [bank matching](../specs/money/bank-matching.md) and
[verification](../self-tests/money/bank-matching.md). Importing does not post money.

Remaining automatic fee rules/taxable items/discounts, refunds, payment adapters,
arrears, accounting exports, scheduled reminders, wallets/approvals/limits/withdrawals, vendors/orders/settlement,
ticketing, online card/QR spending and the finance/app surfaces remain future
Phase 5 slices. Clearing and due-to-school balances remain zero until Phase 6.

The owner authorized implementation on 26 October and superseded the Phase 0
Peach default: no gateway is selected and Peach is not preferred. Build
provider-neutral boundaries and deprioritize gateway adapters/onboarding. Decisions
about split holders, allocation, refund/late-charge/discount rules, approval
thresholds, exactly R250, ticket transfers and vendor liability block only their
specific implementation, not the foundations. No paid or production activation is
a consequence of this plan.

Stage/real-provider replays, physical-device acceptance, cash/bank/statement
reconciliation over the required observation window and the independent money-flow
security review remain honest release evidence, separate from source completion.
