# Family account and annual bill-run verification

Scope: explicit year-specific guardian shares and learner payer assignments,
annual preview/approve/post, sealed invoice history and receivable/income ledger
posting. No provider is selected and Peach-specific work remains deprioritized.

Executed locally on PHP 8.3/MySQL:

- Money cohort and unchanged Bootstrap standards: **37 tests / 285 assertions**.
  This includes all 17 existing fee-plan cases plus 19 family-billing cases.
- Existing ledger/concurrency/snapshot, school-owned inventory, navigation,
  read-only year, permission, route and translation regressions: **47 tests /
  16,810 assertions**. Documentation production build passed.
- Exact cents/shares, actor/school binding, role/permission separation, current
  fee-visible guardians, support/password-reset exclusion, future-year post
  refusal, explicit tax treatment/post confirmation, wrong-school database keys,
  private validation without old-input flash, stale preview/approval and retained
  read-only invoice history passed.
- Real atomic posting produced balanced receivable/income entries and sealed
  learner invoices. Database edits/deletes/appends and incomplete finalization
  were refused. A synthetic failure on the second invoice rolled back invoices,
  entries, postings, projections and receipt; the same operation then succeeded.
- Three-day receipt replay, duplicate submission and competing approved runs
  posted once. Already billed tuition and zero rates were excluded without
  inventing charges. UTC instants and the school calendar date were checked near
  midnight with Africa/Johannesburg selected.
- Synthetic **1,200 learners / 600 family invoices**: **73 preview queries**,
  **15,245 total queries**, **14.6 seconds** for server preview, approval, posting
  and exact replay; **1,201,200 cents**, 600 invoices, 1,200 lines and balanced
  income/receivable projections. This is server evidence, not the complete timed
  Finance user acceptance task.
- Configured whole-app static analysis and baseline-free money Larastan level 5
  passed; browser TypeScript and JavaScript
  syntax passed. Selected Pint and whitespace checks passed.

Browser verification: **4 passed** with zero automatic retries. The Finance
journey passed at **390/820/1280px**: create two-holder/two-learner account,
prepare, approve, explicitly post, read invoice/history and replay the exact post
without another invoice or ledger entry. Ordinary Teacher access was refused.
Each page retained serious/critical accessibility, overflow, duplicate-ID and
console/HTTP error assertions. Each case used a fresh synthetic school and
retained its financial records, leaving shared School A/B and their year wizard
untouched. The first phone attempt found a date-field test locator issue; the
corrected accessible textbox locator passed the full workflow at all widths.
Hosted outcomes remain required and are recorded on the PR.

Non-English phrase keys use English fallback pending language review. Actual
parent statements, taxable/add-on/discount/pro-rata/refund policies, device
acceptance, independent money-flow security assessment and external financial
reconciliation remain separate work/evidence. No production or live-money
release is claimed, and no cancelled pilot campaign is reinstated.
