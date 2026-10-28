# Phase 5 delivery through increment 15

Owner authorization (27 October 2026): implement and safely merge all remaining
increments 1–15 from the agreed inventory. Gateway integration is deferred.
The owner permits discarding old legacy money records instead of migrating them.
That permission does not discard the new immutable ledger, invoices, receipts,
credits, bank evidence or later wallet/vendor history. Remove legacy data only
where its dependencies have been retired; do not spend this delivery on importing
or reconciling abandoned seed money into the new financial source.

| Increment | Scope | Implementation / integration |
| --- | --- | --- |
| 1 | Arrears ageing and accounting CSV exports | Merged PR315; exact source CI and review passed |
| 2 | Scheduled fee reminders through Notifier | Merged PR316 through the qualified PR317 tree; both sources verified on main |
| 3 | Expanded fee-plan rules and payment schedules | Merged PR317; final CI108/108 and review passed |
| 4 | Automatic/rule/membership fee items and VAT | VAT PR318 and compulsory/membership PR319 merged via108/108 qualified source; opt-ins PR320 and discounts PR321 in hosted qualification |
| 5 | Discounts, bursaries, exemptions and approval | Rule/calculator/Finance workbench locally qualified; PR321 in hosted qualification |
| 6 | Pro-rata joins/leavers, refunds and payer corrections | Proration PR322 in hosted qualification; later leaver credits PR323 in hosted qualification; reserved manual refunds implemented with qualification in progress; payer corrections/late charges remain |
| 7 | Personal wallets, statements and spending limits | Implemented; local and hosted qualification in progress |
| 8 | Master funding, desk/parent top-ups, approvals and transfers | Master funding PR327 and desk grants PR328 published; parent transfers/recovery implemented in qualification; parent deposits8C2 published as PR332 with local qualification passed; exact-source hosted CI/review/main remain pending |
| 9 | Adult withdrawals, Finance approval and cash/bank policy | Published as draft PR333; Learner/Parent/Teacher/Facilitator Chrome journeys passed; final migration/hosted/main pending |
| 10 | Daily cash-up by operator and explained differences | Pending |
| 11 | Vendors, storefronts, products/stock, orders and fulfilment | Vendor catalogue/stock11A implemented;13 backend/concurrency cases and four responsive browser cases passed; hosted/main pending. Orders11B remain |
| 12 | Vendor-owned panel/API and native scanner flavour | Pending |
| 13 | Vendor settlements, collection metadata and reports | Pending |
| 14 | Ticket sales, capacity, ownership, redemption and transfers | Pending |
| 15 | Online wallet QR/card spending, SUN, photo and freeze checks | Pending |

Every feature includes its applicable panel/app surfaces, contracts, fresh tenant/
role/owner/guardian checks, balanced cents postings, durable idempotency, immutable
history, reconciliation, tests and documentation. One feature per reviewable PR.
While hosted checks run, independent implementation can continue on a dependent
branch; source integration still requires a qualified tree and resolved findings.
No blanket staging/device/provider freeze is added.

School-specific business decisions are explicit configuration: refund/pro-rata
rules, late charges, approval thresholds, the R250 boundary, ticket transfers and
vendor refund/chargeback liability. Missing configuration limits the affected
operation, not unrelated development. No policy is silently invented by an agent.
Wallet functions remain independent of fee access and learner academic access.
QR/card spending is an online closed-loop wallet operation, not a bank-card gateway.

Parent home-screen Pay now changes and external payment-provider integration
(inventory16–17) remain outside this delivery. Applicable app wallet/commerce
interfaces are included in increments7–15. Phase6 collection remains disabled;
clearing/due-to-school balances remain zero. External real-money, bank-format,
language/device/staging evidence and independent money-flow review remain separate
release records. Do not reintroduce a cancelled pilot as a development gate.
