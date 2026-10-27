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
| 1 | Arrears ageing and accounting CSV exports | Published PR315; local qualification passed; hosted checks pending |
| 2 | Scheduled fee reminders through Notifier | Implementation and automated qualification in progress |
| 3 | Expanded fee-plan rules and payment schedules | Pending |
| 4 | Automatic/rule/membership fee items and VAT | Pending |
| 5 | Discounts, bursaries, exemptions and approval | Pending |
| 6 | Pro-rata joins/leavers, refunds and payer corrections | Pending |
| 7 | Personal wallets, statements and spending limits | Pending |
| 8 | Master funding, desk/parent top-ups, approvals and transfers | Pending |
| 9 | Adult withdrawals, Finance approval and cash/bank policy | Pending |
| 10 | Daily cash-up by operator and explained differences | Pending |
| 11 | Vendors, storefronts, products/stock, orders and fulfilment | Pending |
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
