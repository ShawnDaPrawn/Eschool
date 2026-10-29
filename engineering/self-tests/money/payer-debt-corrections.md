# Remaining increment6D — unpaid payer corrections

Implementation uses an original sealed unpaid-only fee credit plus an equal
replacement invoice in the original family account, with explicit current
configured payer/guardian authority. Original price, VAT, period, due date,
paid receipts and allocations remain frozen. No paid credit crosses holders.
`fee_charges.kind` and bill-run sealing distinguish actual item charges from payer
corrections; no dummy item or current item price is used. Immutable school-owned
`fee_payer_corrections` binds the exact original credit and replacement debt.
Existing parent contracts remain compatible and show only each payer's own rows.

Executed local evidence:

- Eight service/private Finance/parent-contract tests and67 assertions passed:
  equal school totals, original invoice/paid history, stale payment, revoked payer,
  wrong role/school/holder, replay, SQL retention, original VAT after current price/
  rate changes, actor/school-bound forms, no private old input and paused history.
- Three independent-process races and34 assertions passed: competing unpaid
  corrections, same original intent, and payment allocation versus debt correction.
  One outcome applied, outstanding shares stayed non-negative and reconciliation
  passed without moving original paid credit.
- Four responsive Finance/Teacher browser cases passed at390/820/1280px, including
  accessibility, confirmation/replay and unchanged original account/paid totals.
- Three native Parent statement widget cases passed at390/820/1280px using the
  existing generated contract and repository. The new invoice description and
  corrected debt display without private Finance or other-payer information.
- Empty up/down/up and populated24-migration rehearsal passed with non-empty
  original fee-item history. Existing financial fields stayed byte-identical on
  upgrade; old immutable rows, actual account balances, paid receipts and
  allocations stayed byte-identical after correction/replay. Global ledger
  reconciliation passed and retained rollback was refused.
- Money/panel PHPStan and e2e TypeScript passed. New panel keys use approved
  glossary entries where available and explicit English fallbacks pending review.

Final existing-credit/VAT/tenancy integration passed42 tests /1,576 assertions.
Navigation and language regression passed16 /62,557; fresh Pint passed15 PHP files,
Money/panel PHPStan passed, the full fees package passed67 tests with no analyzer
issues, and the documentation production build passed. Hosted exact-source
review/CI and main integration remain pending. Test-only legacy seed cents are backfilled
for the global rehearsal; no old legacy money import is introduced into production.
Late charges and online wallet QR/card spending remain separate remaining work.
Payment gateway integration and production activation remain deferred.
