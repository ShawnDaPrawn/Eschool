# Vendor setup, catalogues and stock

Increment11A starts independently of withdrawal and cash-up qualification. Finance
configures canonical school vendor categories, school-run or independent vendors,
private bank instructions, settlement terms, and explicit refund/chargeback
responsibility. Draft vendors and products can be prepared before activation.
There is no default liability assignment and no gateway activation.

Each vendor owns a zero-balance vendor ledger account. Creating or activating a
vendor never creates money. A school-run vendor still has its own inventory and
sales attribution. Independent vendors' private bank instructions are encrypted
and never enter public storefront projections, logs or app catalogues.

Products retain vendor/category ownership, explicit currency and integer price,
draft/active/archived state and tracked or untracked stock choice. Tracked stock
changes use append-only reasoned receipts and a checked projection. Initial stock
is an explicit adjustment, never an invented opening balance. Concurrent writes
and replay cannot create duplicate stock or negative available quantities.
Archival preserves all identities, stock receipts and future order references.

The first Finance workbench covers category/vendor/product setup and stock
adjustments with fresh school/permission checks, explicit actor binding and
permanent operation receipts. Storefront projections expose only active public
details. Orders, reserved stock, wallet purchase accounting and fulfilment follow
in11B; dedicated vendor identities/panel/native scanner follow in12. Sale roots
will snapshot price, category, tax and liability policy rather than trusting a
changed catalogue after purchase.

This feature cannot debit a wallet, collect customer money, execute a settlement
or grant a vendor ordinary Teacher/staff authority. Existing wallet limits will
match canonical category keys when spending is introduced. All affected panel/API
surfaces receive tenant/role/owner, replay/concurrency and accessibility checks
before integration. Acceptance and production release remain separate.
