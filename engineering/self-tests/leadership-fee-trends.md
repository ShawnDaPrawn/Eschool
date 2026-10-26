# Reconciled leadership fee trends — 26 October 2026

The principal dashboard now projects the existing current recorded fee receipts
in integer cents by receipt week and dated grade. It reconciles settled,
non-deleted transactions against the existing per-learner/class/year running
balance without adding the balance twice. Unknown/missing/inconsistent source
state remains unavailable. Known empty sources are zero, and retained read-only
financial history remains readable. Authority remains school principal/admin only.

The optional office-payment path now stores the date already provided by its
form on the transaction. No amount, pricing, collection or ledger posting is
changed. The rendered amount uses the existing integer-based Money formatter.
The panel explains the recorded receipt/correction basis; this is not a bank
balance or refund-date ledger.

Executed locally with the qualified upgraded dependencies:

- Leadership and existing payment-webhook tests: 30 tests / 159 assertions pass.
  Cases cover exact cents despite contradictory legacy floats, weekly attribution,
  running-balance reconciliation/no double counting, pending/failed/void exclusion,
  missing cents, duplicate balances, bounds, known zero versus missing source,
  read-only history, same-class and different-grade overlap, unknown fee kinds,
  exact settled-component date recovery, wrong-school/staff/revoked-principal
  boundaries and the real office receipt POST.
- Actual Laravel/Chromium panel: 4/4 pass in 37.5 seconds, principal views at
  390/1024/1280 px plus ordinary Teacher refusal. The real 123.45 receipt and source
  note are visible; serious/critical accessibility, duplicate IDs, horizontal
  overflow and console checks pass. The fixture is restricted to named test DBs.
- Selected Larastan, Pint, Playwright TypeScript and public-delta secret scan are
  recorded with the publication checkpoint. Independent source review found the
  different-grade overlap and unknown-kind gaps; both have retained regressions.

CI/main merge and independent staging acceptance are separate. No real money,
provider account, production, private financial export or paid service is touched.
