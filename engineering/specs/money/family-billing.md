# Family accounts and annual bill runs

Second Phase 5 billing increment. Finance explicitly groups learners into a
school/year family account, names its fee-visible guardians and sets holder
shares totalling 100%. Shares are recorded choices, never inferred from legacy
parent fields. Each holder needs an active school membership and an active
fee-visible guardian link to every learner. Integer largest remainders, with
user ID as the tie breaker, make shares add back to each invoice line exactly.

Annual runs reuse the dated-enrolment fee calculator. Preview records a stable
snapshot and exceptions; approval and posting each recompute it under locks.
Missing payers, incompatible currencies, ambiguous plans/enrolments and unresolved
pro-rata/leaver rules prevent the affected run from posting. Previously invoiced
annual tuition is shown and excluded; new operation/run keys cannot rebill it.
Zero-rate tuition is shown without inventing zero-value ledger entries. Empty
runs cannot post. Finance explicitly confirms the supported tax treatment (no
VAT charged on this annual tuition), due date and shares. Taxable items, discounts,
refunds, credits and policy-dependent pro-rata follow as separate workflows.

Preparation may target another year; posting requires the school's current year.
Approval is an explicit recorded stage; no unrequested second-person threshold
is imposed. Posting creates sealed invoices and learner lines, debits the
family's dedicated fee-receivable asset and credits school fee income. The new
income type belongs only to fees. Family credit/wallet accounts are not invented
revenue. Clearing/due-to-school stay zero. Stable run/account ledger identities
and actor-bound permanent UUID receipts prevent replay. Run, invoices, lines,
postings, audit and receipt commit together or roll back.

Database constraints protect tenant references and annual charge uniqueness.
Finalization checks line totals and the posted receivable entry. Sealed invoice
headers/lines cannot be edited, deleted or appended. Future corrections require
a separately authorized credit/adjustment with a reason. Rollback never deletes
retained billing or ledger history.

Finance-only panel accounts, invoice history and preview/approve/post use separate
account-management and billing permissions, checked freshly under the school lock.
Support identities, impersonation, inactive memberships and overdue password
changes are refused. Browser writes bind displayed school/account, use CSRF and
UUID receipts, and stay private/no-store without old-input/sessionStorage copies.
Read-only history remains when fees are disabled. Parent statements/allocations
follow with fresh fee-visibility checks; no new parent API is exposed here.
Legacy v1 payment/history contracts remain intact. Gateway-specific work remains
deferred: no provider selection, Peach integration or paid/live activation.

Verification covers tenant/role/owner isolation, exact shares/cents, stale
approval, duplicate/retried runs, immutable invoices, atomic rollback, retained
history and 1,200 learners. Browser checks cover finance create/preview/approve/
post and wrong-role refusal at 390/820/1280px. Hosted CI qualifies the final source
before merge. External acceptance and independent money-flow security evidence
remain separate from implementation.

Finance may clear an existing account's final **unbilled** learner assignment in
the selected year, then assign that learner to the intended family. Empty rosters
retain the account and any history; new accounts still require a learner. Posting
refuses stale approvals after reassignment. Posted learner charges cannot be
removed this way and require the separate credit/payer-transfer workflow.
