# Vendor wallet orders and fulfilment

Increment 11B extends the prepared catalogues with a private school storefront,
quoted wallet purchases, original receipts and single-use counter collection.
Checkout rechecks current actor, owner/guardian approval, module availability,
wallet freeze, school/guardian daily caps, allowed canonical categories, vendor
status, exact quoted prices and counted stock inside the shared school lock.
There is no gateway or outside collection.

A reviewed quote binds school, buyer, wallet, permitted learner recipient and
current product/tax/vendor-policy revisions. Paid orders snapshot quantity,
integer gross/VAT/net cents and seller terms. One permanent UUID debits the
original buyer wallet and credits that vendor's ledger account exactly once.
Stock is consumed atomically; neither insufficient stock nor insufficient money
can leave a partial order. All purchases use this same boundary so later QR/card
spending cannot bypass caps or reconciliation.

Immutable inventory events bind counted manual stock receipts or an exact paid
order line and preserve a checked per-product sequence. Existing counted roots
are retained, indexed into the event history and verified before changing the
projection triggers. No initial stock or money is invented.

An order starts ready for collection. Counter collection accepts its school-local
number or private QR token, verifies the vendor and current authority, and records
one append-only outcome. Buyers see only their own/current guardian-authorised
receipts. An uncollected order may be cancelled under current authority; the
original gross returns only to its original wallet and counted units are restored
once. Collected returns and settlements require the separately implemented
liability process. Pending stock orders are excluded from settlement eligibility.

Finance counter access uses explicit commerce authority; dedicated vendor-owned
operator scope, private panel and native scanner are increment 12. Flavours never
borrow Teacher authority. Products sold, private instructions, original receipts,
counter actions and inventory remain readable when commerce is paused. Purchase,
collection, cancellation, tenant/role/owner refusal, recovery, stock and wallet
races, tax/price changes and all affected client journeys require verification
before main integration. Production acceptance remains separate.
