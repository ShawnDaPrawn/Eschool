# Fee items and one-off charges

Finance can maintain a school-owned, revisioned catalogue of positive-cent learner
or family fee items, then review and confirm a dated one-off charge to an explicit
family account. A separate `fee-items-manage` permission controls the catalogue
and charging workflow. Category is additional fees and tax treatment is explicitly
no VAT charged in this increment; taxable items, automated membership/rule billing,
discounts, pro-rata, gateways and payouts remain separate work.

An occurrence reference identifies the actual charge (for example an excursion),
independently of a request UUID. School/item/year/target/reference uniqueness prevents
a new UUID or reopening a form from billing it again. The occurrence digest is
stable across application-key rotation; private reference text is encrypted. Another occurrence may use
another reference. Catalogue edits/archiving never rewrite posted charges.

Learner items charge one assigned learner. Family items charge the family exactly
once, then allocate that total over the reviewed family learner roster. Calculate
the holder totals first using the existing explicit percentages/largest remainders.
Divide each holder total over sorted learners in integer cents, rotating the
remainder cursor across holders. This preserves exact overall holder totals and
equal learner totals (within one cent), including tiny amounts and split payers.
No fictitious learner, hidden-family line or changed legacy API identity is used.
The review and statements explain family apportionment; only positive learner
lines are posted. Parent visibility and learner-pinned receipt rules still apply.

Review shows item/revision, occurrence, date, due date, account, roster, each
learner/holder amount and total. HMAC binds the complete snapshot; final confirmation
recomputes it under the existing school/actor locks. Fresh permission, active
account, current school year, dated enrolments, guardian fee visibility, currency
and catalogue state are checked. A changed roster, payer, item or year requires
a fresh review. There is no additional second-person approval threshold.

Posting atomically creates an immutable fee-charge receipt, a distinctly typed
bill run with recorded preparation/approval, sealed invoice/lines, balanced
receivable/income entry, audit/outbox and permanent actor-bound operation receipt.
Annual tuition remains uniquely billed once per learner/year; fee-item invoices
do not suppress or duplicate that annual charge. Original invoices remain immutable
and the existing credit-note workflow can correct new item shares.

Oldest-first allocation orders all charges by due/issue date and line ID.
Tuition-first prioritizes annual tuition before additional items, then uses that
same stable order. Both retain holder identity, learner pins and net credit releases.
Finance and private parent statements show the new charges using additive optional
kind/scope fields; installed clients retain real learner IDs and correct amounts.
Fee/item/charge history remains readable when the module is disabled. No financial
input/private explanation is flashed to sessions or browser storage.

Database checks protect tenant links, one-off occurrence identity, annual tuition
uniqueness, immutable sealed history and invoice/charge totals. Populated schema
rollback refuses to discard item/charge history; empty down/up is rehearsed.
Reconciliation includes incomplete charges and exact charge/invoice/line linkage.

Verification covers catalogue revisions/archiving, learner/family exact cents and
rounding, stale reviews, duplicate occurrences/UUID replay, atomic rollback, annual
billing coexistence in both orders, tuition priority, pins, credit/reallocation,
wrong school/role/owner, fresh permission/guardian/module changes, private errors,
retained history and backwards-compatible parent contracts. Exercise Finance at
390/820/1280px and parent package/browser/native journeys. Qualify the final source
with relevant local checks, hosted CI and review before merge. External staging,
physical-device, fluent-language and independent live-money acceptance remain
separate from implementation and safe source integration.
