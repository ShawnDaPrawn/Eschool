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
| 4 | Automatic/rule/membership fee items and VAT | Merged through PR324; exact source CI112/112 and review passed |
| 5 | Discounts, bursaries, exemptions and approval | Merged through PR324; exact source CI112/112 and review passed |
| 6 | Pro-rata joins/leavers, refunds and payer corrections | Proration, leaver credits and reserved manual refunds merged through PR324; payer corrections/late charges remain |
| 7 | Personal wallets, statements and spending limits | Merged through PR337; exact source CI121/121 and review passed |
| 8 | Master funding, desk/parent top-ups, approvals and transfers | Master funding, approved desk grants, parent/child transfers and verified parent deposits merged through PR337 |
| 9 | Adult withdrawals, Finance approval and cash/bank policy | Merged through PR337; compiled Learner refusal and Parent/Teacher/Facilitator journeys, migration, exact source CI/review passed |
| 10 | Daily cash-up by operator and explained differences | 10A reports/counts published PR341; 10B confirmed handovers, actual payer custody and nightly cash reconciliation implemented and locally qualified; hosted/main pending |
| 11 | Vendors, storefronts, products/stock, orders and fulfilment | Catalogue/stock11A merged through PR337; storefront/orders11B PR338 in exact-source hosted qualification after global contract registry correction |
| 12 | Vendor-owned panel/API and native scanner flavour | PR339 published; local qualification passed; review Finance replay finding fixed, targeted regression passed and updated exact-source review has no findings; hosted/main pending |
| 13 | Vendor settlements, collection metadata and reports | PR340 published; local qualification passed; fee-only reconciliation review finding fixed, global/fee regressions passed; exact-source hosted CI/main pending |
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
