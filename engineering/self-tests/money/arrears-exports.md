# Arrears and accounting export verification

Initial PHP8.3/MySQL reporting qualification passed **14 tests /127 assertions**.
It covers calendar boundaries (including leap dates), exact family/holder totals,
held credits and paid credit releases, partial allocations, genuine foreign-school
accounts, currency separation and accounting-organisation currency mismatch,
CSV formula handling, all five column layouts, journal date ranges/UTC-midnight,
settings revision/replay, fresh permissions, actor/school binding, disabled history
and incomplete-ledger refusal. No read/export creates financial postings.

Initial fixture expectations were corrected to retain all four actual learner/
holder shares of a two-learner split family, and to distinguish a paid credit
release from an unpaid credit reducing debt. Parent/learner panel denial follows
the existing sign-out/login redirect; domain authorization remains403. No history
or access assertion was weakened. A browser assertion was corrected because Xero
manual-journal columns use the explicitly configured organisation currency rather
than a per-row currency column; currency mismatch refusal is separately tested.

Final browser/static/regression and hosted source qualification are tracked on the
feature PR. Accountant/version-specific CSV import validation, fluent translation,
staging/device and independent live-money acceptance remain separate evidence.
Gateway integration and increments16–17 remain deferred.


Finance browser qualification passed **4 tests**, zero automatic retries: all
five CSV downloads and mapping save/replay at390/820/1280px, current/overdue/held
credit filters, exact ledger invariants and Teacher refusal. Serious/critical axe,
horizontal overflow, duplicate IDs, no-store and console/HTTP assertions passed.
