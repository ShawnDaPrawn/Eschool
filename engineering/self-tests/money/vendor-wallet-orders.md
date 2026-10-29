# Vendor wallet orders and collection qualification

Increment11B implements public active catalogues, reviewed wallet checkout,
original gross/net/VAT receipts, counted inventory consumption, buyer cancellation
and Finance collection by typed order number or private wallet QR. Purchase and
cancellation share the durable wallet operation/recovery boundary. Dedicated
vendor accounts and scanner access remain increment12; no gateway is enabled.

The backend contract/service suite passed14 cases/130 assertions. It exercises
price/VAT snapshots, quote expiry and committed replay, school/owner/current
guardian refusal, daily/category caps, free orders without zero postings, stock
atomicity, immutable SQL roots, fresh authority before cached success, original
wallet refunds and Finance verified uncollected returns for departed holders.
Four independent MariaDB process races passed40 assertions: competing wallet
spends, two buyers of the last unit, duplicate operation, collection/cancellation.

Four Finance browser cases passed without retries at390/820/1280px plus Teacher
refusal. They validate typed and protocol QR orders, retain original receipts,
refuse a new second collection, replay the same operation once, cancel original
uncollected goods and preserve read-only history. Actual wallet/vendor cents,
stock, domain reconciliation, accessibility, duplicate IDs, overflow and browser
errors are asserted. The initial browser run found a real one-character QR field
truncation; its maximum now fits the complete81-character protocol code.

The final combined vendor/catalogue/concurrency and Facilitator regression passed
44 cases/450 assertions. All95 shared wallet tests passed after the final scope
and QR-key additions. They cover strict integer-cents/seller/receipt binding,
asynchronous school/account refusal, opaque-key recovery of lost purchase and
cancellation replies, read-only behavior and checkout/QR/cancellation at390/820/
1280px. Actual compiled Learner, Parent, Teacher and dedicated Facilitator Chrome
journeys passed login, wallet, shop, reviewed purchase, private QR, cancellation
and revoked-access clearing. The private harness registered the mock keyboard in
profile mode and used a stable QR widget key because profile semantics cannot
serve as a widget finder; the QR image/data assertions remain in shared tests.
The initial disk-full compilation was rerun using isolated generated builds in
shared memory. It is not counted as a passing run.

Empty migration up/down/up passed. A populated migration indexed three retained
manual movements exactly once and preserved original financial/catalogue/stock
columns byte for byte. A new purchase, interleaved actual manual waste and original
cancellation produced sequence6 with the correct9 units and2,000 wallet cents;
ledger/domain reconciliation passed and a retained-order rollback was refused.

Money PHPStan level5, configured panel static analysis, e2e TypeScript, fresh
Pint and documentation production build passed. Canonical OpenAPI lint and all
182 SDK tests passed. The pinned generator produced2668 Dart files and a repeat
matched all2670 generated source/metadata files byte for byte. Shared package
analysis was clean; affected native app analysis passed with existing informational
style lints and no errors/warnings. Panel labels25 and app labels48 were added as
explicit English fallbacks across24 catalogues. Exact-source hosted CI/security/
review and main integration remain pending. Fluent translation, physical devices,
staging and independent financial acceptance remain separate release evidence.
