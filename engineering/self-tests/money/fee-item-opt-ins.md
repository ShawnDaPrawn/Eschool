# Optional fee approval qualification

Targeted backend:18 tests/195 assertions passed. Covers every-payer approval,
changed prices and shares, no-op saves/key rotation, archived withdrawal, permanent
replay, fresh authority before cached replay, current year/tenant/owner/role checks,
SQL immutability, explicit frequency, once-per-year deduplication, original VAT,
transaction rollback and safe retry. Additive OpenAPI contracts passed8 tests/28
assertions. Final broader qualification is recorded on the feature PR.

Shared fee package:55 tests passed and analysis reports no issues. Phone/tablet/
desktop review, approval and withdrawal, strict integer cents, wrong targets,
unknown future frequency, uncertain same-UUID retry, late responses and clearing
private history after revoked access are covered. Parent app targeted analysis has
22 existing style infos and no errors/warnings. Native family smoke now exercises
approval and withdrawal before checking unchanged statement cents; native execution
requires the hosted matrix and is not claimed by local analysis.

Finance/Teacher Chromium:4 cases passed, zero retries at390/820/1280px. Finance
configures explicit yearly billing, sees missing approvals, records real synthetic
parent approvals without posting money, reviews/posts once and cannot bill again
under a different occurrence. Includes disabled posting, replay, ledger totals,
Teacher denial, no-store, accessibility and overflow checks.

Initial development checks caught MariaDB CHECK syntax, OpenAPI3.1 nullability,
generated bool enum and wide-layout checkbox ink; all were corrected without
weakening assertions. Migration/static/regression/SDK/docs final evidence belongs
on the PR. Staging/devices, fluent translation and independent money-flow review
remain external release evidence. No live provider, gateway or financial transfer.
