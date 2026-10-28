# Item VAT qualification

Implemented: declared registration and per-item rates, exact integer charge/tax
splits, original legal snapshots, cumulative credit reversals, VAT reconciliation,
Finance tax/credit views, private parent API/app tax and accounting mappings.
Automatic eligibility is the next part of increment4; later increments remain open.

Targeted backend tests passed14 tests/138 assertions, including inclusive and
exclusive pricing, holder-first rounding, zero-net1c, paid/pinned credit, changed
registration/rate, huge SQL decimal products, private parent contract and fresh
guardian/role/school refusals, panel bindings/read-only, immutable originals,
rollback refusal and balanced VAT package journals. Initial pure math checks
passed16 tests/61 assertions. Final broad results belong in the feature PR.

Shared fee app tests passed35 tests, including old API compatibility, malformed
VAT responses, private-session invalidation and VAT/credit rendering at390/820/1280.
Generation used the pinned7.25.0 API generator, never hand edits. A fixture generic
map failure was fixed with an explicit dynamic map; no assertion was removed.
Final source app analysis and hosted native checks are tracked on the PR.

The first Finance browser run found a Blade include rendered as literal text on
the original invoice. The template was corrected; that failed run is retained,
not counted as a pass. Final full browser, migration, Money/platform/static/docs
and hosted results are recorded on the PR after execution.

No production/provider activation. Fluent translations, device/staging acceptance,
registered-school tax/invoice acceptance and independent money-flow security
review remain external release evidence and do not freeze subsequent development.

Broader Money regression passed184 tests/1,440 assertions. Baseline-free Money
level5 and configured panel static analysis passed; fresh-cache Pint passed.
Shared fee app final analysis has no issues; all35 tests passed. Generated API
analysis passed with nonfatal generated-code style infos and all182 API package
tests passed. TypeScript and documentation build passed. Generator repeatability
and remaining browser/platform/migration results are recorded on the feature PR.

Final Finance/Teacher browser run passed4 cases with zero automatic retries at
390/820/1280px: declared registration, inclusive rate, exact review, posting/replay,
original legal invoice, cumulative credit/replay, balanced receivable/income/VAT,
wrong-role refusal, no-store, serious axe, overflow, duplicate IDs and console/HTTP
checks. The real CI flows project discovers all4 cases. Empty and genuine old
no-VAT invoice/charge/credit up/down/up rehearsals passed without changes to2
invoices,4 lines,3 entries and1 credit note. API generation repeated identically
for all2,544 Dart files. Parent native integration source analysis passed; Chrome
and Android execution remains a hosted qualification requirement on this PR.
