# Manual fee receipts and private statements verification

Current increment: verified manual cash/EFT, explicit holder and optional learner
pin, held credit, reviewed allocation and Finance/parent statements. No gateway
is selected or activated. This record separates local evidence from hosted and
external acceptance; Phase 5 as a whole remains incomplete.

Executed locally on PHP 8.3/MySQL:

- Existing Money/Bootstrap cohort plus the first18 payment cases: **56 tests /
  474 assertions**, passed. Two additional private panel workflow cases passed
  **43 assertions**. The final combined regression cohort is recorded below.
- Receipt source/UUID uniqueness, exact cents, partial/overpayment, split holders,
  sibling/learner pins, stale previews, atomic rollback, immutable history,
  school/owner/role isolation and fresh guardian/module revocation passed.
- Family currency freezes on prepayment; held money prevents stranding a pinned
  child or emptying the payer roster. Ordinary unbilled reassignment remains usable.
- Parent APIs pass OpenAPI response validation, independent charge/receipt pages,
  full permitted totals, hidden sibling/mixed receipt redaction, retained history
  and private error/cache checks. Another holder's identity, raw bank references
  and ledger IDs are excluded. Disabled modules permit retained reads only.
- Reconciliation detects incomplete allocations, batch totals and semantic
  credit/receivable mismatches independently of cached ledger projections;
  rebuilding projections refuses invalid domain history.

Parent package: **16 tests passed**, covering scoped private reads without URL
logging, school/holder/arithmetic checks, token replacement, late/disposed
responses, forward-compatible receipt methods and usable two-child statements at
390/820/1280px. Denied refresh clears financial data. Mounted-boundary tests also prove that
logout/account/school changes remove the view and invalidate outstanding work. Parent shell entry checks:
**6 passed**, including explicit Parent/module availability, learner exclusion,
disabled direct links, exact deep link and all four offered phrase catalogues.
SDK generation succeeded with pinned tooling. Non-English new keys use English
fallback pending fluent review. Hosted Android outcomes remain required; unrun checks are not claimed passed.

Actual bank/cash reconciliation, physical-device/staging/user acceptance,
independent live-money review and production approval remain separate. The legacy
leadership receipt adapter has not been switched to this ledger. No financial
history is imported or reposted, no paid service is provisioned, and debt never
grants or removes learning/guardian approval.

Finance browser qualification: **4 passed**, zero automatic retries. Actual
receipt → explicit school policy → reviewed allocation → statement journeys passed
at 390/820/1280px, with exact-form replay producing one receipt/batch, two allocation
rows and three balanced entries. A 2000-cent receipt allocated 1002 cents, left 998
held, retained the other holder's 1000-cent debt and 2002-cent income. Both synthetic
learners remained inactive. Teacher access was refused. Serious/critical axe,
overflow, duplicate IDs, private headers and console/HTTP errors were checked.
An earlier multi-process local run exhausted memory and crashed a browser tab;
qualification reran with resources controlled and all assertions unchanged.

Pinned OpenAPI generation/lint and generated SDK analysis passed with no errors
or warnings (generator-style informational lints remain); all **182 SDK tests**
passed. Baseline-free money level 5 and configured whole-panel static analysis
passed. New package analysis is clean. Browser TypeScript, JavaScript syntax,
selected Pint, whitespace and the documentation production build passed.

Final local regression cohort: **711 tests / 20,306 assertions passed**, including
the Money/Bootstrap suite, ledger service/concurrency/stale snapshots, school-owned
inventory, navigation, retained-year behavior, permissions, routes, translations
and OpenAPI authentication/response scenarios. All **41 affected app entry and
navigation tests** passed. The complete learner/parent Chrome smoke flow passed:
learner exclusion, real login and parent Home entry, both learner shares, receipt
history and reserved credit, scoped headers, and denial clearing private details.
The lazy list is scrolled using real gestures before inspecting later receipts.
No binding, input helper or assertion was weakened. Hosted Android/CI must still
qualify the final source; final run links and merge evidence are on the PR.
