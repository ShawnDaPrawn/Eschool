# Enrolment fee proration qualification

Initial backend matrix passed5 tests/38 assertions: absent explicit policy,
rate/bill/post cents and periods, exact holder allocation, calendar-month and
full-year/no-credit rules, retained/future installment weights and reviewed due-date
fallback, changed policy/enrolment stale reviews, permanent replay, immutable old
invoices, role/actual foreign year, private panel bindings and disabled read-only.
Initial arithmetic matrix passed10 cases/28 assertions for leap dates, crossing
January, inclusive one-day/max-cents amounts, mixed bases, partial year month edges,
invalid calendar dates and exact remaining installment weights. A later zero-price
case requires the final focused run; do not count an unrun check as passed.

One mixed-basis test expectation was corrected after independent calculation:
annual36500 minus31 daily join days(3100) minus10 calendar months(30416.666...) is
2983.333..., rounded once to2983. No financial implementation was changed for that
expectation. Final regression, Finance/Teacher browser at390/820/1280px, tenant/year/
route/translation, migration, static/Pint/TypeScript/discovery/docs and hosted
source review results are tracked on the feature PR. Later leaver credits/refunds/
payer corrections are not claimed by this slice. No provider or production action.

Final Money regression passed224 tests/1,818 assertions. Final focused proration,
tenancy, migration, year, routes and translations passed40 tests/18,579 assertions,
including continuous class transfers, attendance gaps/overlaps and the zero-rate
case. All4 Finance/Teacher Chromium journeys passed at390/820/1280px, zero retries;
actual CI flows discovery includes them. Empty and posted-history up/down/up
retained1 invoice and1 ledger entry unchanged. Baseline-free Money5, configured
panel analysis, fresh-cache Pint, TypeScript and documentation build passed.

The environment restart interrupted an earlier regression; it is not counted as
passed. Follow-up checks caught a missing test import and preserved the preview's
explicit distinction from an invoice. Final exact-source hosted qualification and
integration are tracked on the PR. Later leaver credits/refunds/corrections remain
separate features in increment6.
