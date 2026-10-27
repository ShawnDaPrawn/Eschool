# Fee corrections and credit notes

A Finance user with `fee-credits-manage` can review and confirm a positive credit
against one recorded holder's share of a posted invoice line. A fixed reason
category and private Finance explanation are required. The operation reduces that
share; additional charges remain a separate invoice/item workflow. No automatic
refund/pro-rata formula, extra approval threshold, gateway or cash payout is
introduced. The original invoice, receipt, allocation and ledger history remain
immutable.

The review shows gross charges, earlier credits, effective paid amount, debt
reduction and money restored to held credit. Its HMAC fingerprint is recomputed
under school/actor locks at posting. A credit cannot exceed the uncredited original
share. It first reduces unpaid debt. Any remaining amount restores the latest
effective allocations of that same line/holder, in descending allocation ID order.
Append-only release rows identify the original payment and allocation; the receipt's
holder and learner pin remain authoritative when that money is used again.

Posting debits fee income by the full credit, credits the family's receivable by
the unpaid portion, and credits the original holder liability by the paid portion.
Bank/cash and Phase 6 clearing/due-to-school are untouched. Credit header, releases,
entry, audit, outbox and permanent actor-bound operation receipt commit atomically.
Sealing validates invoice share caps, released allocations and exact ledger sides;
sealed headers/releases reject edits/deletes/appends. Rollback refuses retained
credit history. Existing payment-allocation caps use net amounts after sealed
credits/releases, permitting later reuse without rewriting older allocations.

Finance has a credit list/form/review/detail and links from invoices/statements.
Forms bind school/actor, CSRF and UUID, use private/no-store responses, and never
flash or persist private reasons. Historic invoices may be corrected without
editing the selected academic year. Disabled fees retain history read-only.

Statements show original charges, credits, net charges, effective applied payments,
debt, held funds and credit-note history. Existing v2 `charged_cents` remains the
current net charge used in `charged = allocated + outstanding`; additive optional
`gross_charged_cents` and `credited_cents` expose the immutable original and its
credits. Old clients retain correct arithmetic and current balances. Add optional,
independently paginated credit history and released receipt amounts, regenerate
the SDK, and update the parent app with safe defaults for older responses.

Parent reads retain fresh canonical guardian/Parent/school/module policy and
whitelist only their currently fee-visible shares and fixed reason categories.
Private explanations, other holders and raw bank references are never returned.
Mixed/hidden-child receipts remain withheld even after a release. Guardian
withdrawal does not erase Finance obligations or restore parent access.

Reconciliation and projection rebuild validate draft/sealed credits, release caps,
net paid shares, held liabilities, receivables and fee income. Test partial/unpaid/
paid credits, repeated credits, exact cents, release/reallocation, original pins,
competing previews, permanent replay, atomic rollback, immutable database history,
tenant/role/holder refusal, fresh revocation, private reasons and retained history.
Run Finance browser checks at 390/820/1280, parent package/app and native/browser
flows, contracts/SDK, money level 5 and full relevant CI on the final source.
External bank/cash/device/language and independent live-money acceptance remain
separate from development and safe source integration.
