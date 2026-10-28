# Vendor catalogue and stock qualification

Implemented increment 11A: school-scoped categories, school/independent vendors,
explicit activation terms and liability, products with integer-cents prices and
tax decisions, encrypted private instructions, retained operation receipts and
append-only stock adjustments with checked projections. It has a Finance workbench
and an internal active storefront projection. Wallet writes are independent.
Orders and their public API/app storefronts are 11B; vendor identities and the
scanner flavour remain 12. No catalogue operation posts money.

Thirteen MariaDB backend and real-process concurrency tests passed 95 assertions.
They exercise setup, explicit policy requirements, encrypted/private projection
boundaries, replay, fresh authority, wrong roles and schools, actor/school-bound
forms, revision and immutable ownership checks, tax matching, stock projection,
retained read-only history, concurrent over-removal, duplicate operation and
duplicate physical delivery prevention. Initial failures were a generated index
name exceeding the SQL identifier limit and a fixture using the wrong teacher
key; both were corrected without relaxing assertions.

Four Chromium cases passed without retries at 390/820/1280px plus Teacher refusal.
They create an independent vendor and product, count stock, replay the original
receipt and retain history after module disable. Accessibility, horizontal
overflow, duplicate-ID and browser-error assertions passed. An initial serious
link contrast distinction finding was fixed with underlined catalogue links.

Final combined vendor, concurrent stock and ledger-kernel regression passed 30
tests / 166 assertions. Money PHPStan level 5, configured panel static analysis,
TypeScript, fresh Pint and documentation build passed. Empty up/down/up and a
populated upgrade preserved existing invoice, ledger, refund, Parent deposit and
withdrawal rows byte for byte. New catalogue and counted-stock reconciliation
passed without adding a money entry; retained rollback was refused.
Exact-source hosted CI/security/review and main integration remain pending. Catalogue
labels use explicit English fallbacks; fluent translation and physical devices,
staging and independent financial acceptance remain separate release evidence.
