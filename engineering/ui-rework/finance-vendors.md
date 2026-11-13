# Finance and Vendors — revision 3 implementation ledger

Scope: all **225** Finance/Vendors destinations in `school-admin-design-v3/design-data/tasks.json` (181 Finance, 44 Vendors). The row-by-row map is `finance-vendors-coverage.csv`. The 14 Community admission handoffs are additionally owned and mapped in `finance-vendors-trace.json`. Distinct retained outcomes in the PDF map to the actual state of their source record, rather than mock duplicate receipts or a second posting path.

## Delivered

### Navigation and separation

The root navigation work provides authorised Finance/Vendors workspace landings and configuration groups. Domain pages now use the shared Material 3 shell, contextual tabs and token-based responsive tables/forms. The following previously mixed pages have independent server-rendered sections:

- Family account/payer shares and learner payment choices.
- Fee plans and enrolment preview; plan editing and copying into another year.
- Fee receipts and allocation configuration; reminder history and reminder schedule; aged balances and accounting exports.
- Wallet balances, spending configuration and child-return configuration; parent deposit requests and methods; desk grant history/new grant/approval configuration; master funding history/new funding/methods; withdrawal queue/policy/pickup slots/original-holder return.
- Refund history and policy; leaver credit history and policy; retained cash handovers and new handover.
- Vendor directory, vendor trading/settlement configuration, categories, products and counted-stock adjustments/history.
- Dedicated operator creation, assignment register/new assignment and selected access review; reader register/registration/pairing and selected revocation review.
- Settlement history and CSV export share the same explicit vendor/period filters; leaving dates empty includes retained history. Settlement preparation from explicitly selected original collected orders.

Existing saved outcomes redirect to their real record or queue. Original POST routes, operation identifiers, revision checks, expected actor/school bindings and domain authority remain in use. No production money was submitted during implementation.

### Specific approved interaction corrections

- Payer shares use an actual guardian/share/fee-access grid with the existing exact 100% validation and guardian eligibility checks. The UI does not claim fee access has passed before the server checks every selected learner.
- Fee matching rules are repeatable rows. Payment options contain editable month/day/percentage installment grids, with add/remove controls and an explicit default option. Decimal percentages become integer basis points; legacy schedule input remains compatible. Removing all matching rows explicitly clears the previous rules. Invalid schedules never flash private input into session storage.
- Eligible settlement orders show real order numbers, collection times, gross amounts, VAT and collected/due state. Original batch lines show gross/net/VAT. Approval and verified payout/school-sales recognition remain separate source service steps. The retained batch shows the actual outcome and authorised private evidence.
- Receipt, bill-preview and holder-statement summaries use actual amount/state cards. Statements separate charges, retained credits and receipts. Recording a fee receipt still holds its full value as credit. Allocation remains a separate review/post operation. Bill preview exceptions, approval readiness, no-VAT tuition confirmation and posted invoice rows remain source-backed. Immutable financial history, refund reservations, independent approval, reversals, stock movements, original holder attribution and external reference deduplication are unchanged.

### Previously API-only school functions now have real browser screens

`routes/panel-finance-ui.php` is included in the existing authenticated school route group. Each new private form uses CSRF, expected actor/school bindings and disabled browser draft storage.

- **Bursaries**: filtered committee queue; restricted application/household/document review; independent note/award/decline forms; retained event/decision history; current-year draft fee-discount handoff. The domain still excludes the learner's own guardian from committee decisions and requires independent Finance discount approval/application. Restricted files use the original document-read service.
- **Giving**: paginated/searchable campaign register; create; campaign totals; donor-authorised pledge/receipt register; separate pledge and verified EFT receipt entry; immutable donation receipt; close confirmation. Pledges post no funds. Receipts post exact cents once, preserve original reference deduplication and use the original ledger service. Campaign-only staff never receive donor identities. Older records remain directly reachable beyond the bounded API recent list.
- **Sponsored learning places**: paginated current-school sponsorship history; current learners/capacity/state; bounded learner search and assignment; selected learner release confirmation/result. Requested, awaiting-payment places are supported by the source contract; assignment does not claim payment or an active entitlement. Capacity and current ownership are rechecked under the existing service locks. Releasing a place leaves other entitlements intact.
- **SE075 subscription invoices**: a new strictly read-only school contract shows only this school's issued invoices/credit notes, original lines, allocation/reversal facts and exact outstanding balance. Active local Super Admin authority is required; HQ identity, impersonation, foreign IDs, drafts, school-parameter injection and POST are refused. No platform billing permission is granted. See `school-subscription-invoice-read-contract.md` for the projection and authority.

### Admission catalogue and authorised gate operation

Admission events now have a separate register, create/edit form, saved event detail, admission type detail/edit, retained revision history and explicit cancellation confirmation. Issued types keep original event/product/kind read-only and show actual issued count; source checks prevent reducing capacity below sales. Failed validation redisplays bounded fields only in the current private response, without session/browser draft storage. Revision conflicts compare attempted and saved source values before returning to the current record. Cancellation retains ticket and financial history, cannot reopen the event and does not issue a refund.

The dedicated Vendor counter provides number/QR lookup, matched event/type/current state and school-time admission window, named learner where applicable, explicit one-person confirmation, original admitted result and refusal explanations. The standard ticket API projection is unchanged. UI-only refusal reasons explain matched-record status, event/type state, window, holder eligibility and paused modules; unknown/foreign/rotated credentials remain indistinguishable. Gate operators receive no unrelated Finance screens.

An interrupted confirmation retains only its original operation UUID in actor/school/vendor-scoped session storage. Returning to the counter blocks a new admission until the operator explicitly resolves that original operation. The existing service returns its committed receipt or atomically abandons an unseen operation, preventing a delayed original confirmation. No automatic resubmission, new money posting or override admission was added. Background/bfcache returns use the counter GET recovery screen instead of reloading a POST.

## Preserved source screens

All original Finance/Vendors form, preview, detail and retained-result views remain reachable through their existing routes, now in the shared workspace and Material shell. The coverage CSV points each task to its real view or new adapter. This includes fee-item catalogue/eligibility/automatic batches, lifecycle/enrolment calculations, discounts and credit-note posting, leaver credits, refunds, bank import/match/exclusion, physical cash counts, wallet receipts/withdrawals, vendor order verification/cancellation and accounting exports. A shared layout change is distinguished from a new adapter in the CSV; the map is not a claim that every money transition received a new end-to-end test.

## Validation

- New school workflow tests cover private authorities/context binding, exact decimal conversion, pledge-versus-receipt distinction, idempotent donation, sponsorship capacity/release, published-only subscription reads, percentage bursary awards into a separate draft discount, and structured installment parsing/clearing.
- Admission regressions cover separate catalogue screens, issued type identity/capacity, stale comparison, explicit cancellation, original-operation resolution, wrong context/owner/role and retained single-use behavior. Existing ticket API/ledger tests remain in the same suite.
- Browser specs navigate actual native forms, preserved original operations and the separated sections. They check ledger/domain row counts, serious accessibility findings, horizontal overflow and duplicate IDs at mobile and desktop widths.

## Boundaries

This implements school administration, not HQ administration or a new payment provider. All source paths that intentionally reserve/approve/record rather than send money keep those meanings. The proposed SE075 read has been implemented with a narrow documented authority; other unsupported payment-provider capabilities are not presented as working controls. No code was committed or deployed by this domain agent.

## Final verification results

- **Backend:** `FinanceUiWorkflowTest` and `TicketsTest`: **23 tests / 305 assertions pass** on the final protected route wiring. The suite checks exact donation and percentage-award conversion, immutable/idempotent money and tickets, wrong role/school/owner, current operation resolution, cancellation acknowledgement and revised type identity. The earlier 32-test Finance/Vendor batch's only failure was a missing submission timestamp in the bursary test fixture; all Vendor catalogue/operator tests passed and the corrected Finance suite now passes. App-only Parent/Student accounts follow the existing panel logout/login refusal; staff without the necessary authority receive403. A legacy ticket replay assertion now canonicalizes MySQL JSON object key order while comparing all typed values.
- **Final browser:** **8/8 pass** at390px and1280px: split-family payer configuration/approved billing/posted invoice; held receipt/separate allocation/statement; private giving/bursary/sponsorship/published subscription invoice; original collected orders/settlement approval/recognition/vendor-scoped retained batch. These final results supersede the earlier shared-runtime, receipt fixture and shared-header failures. All cases check actual domain outcomes, ledger invariants where applicable, serious accessibility findings, document overflow and duplicate IDs.
- **Earlier browser:** annual plan create/copy/preview at390/1280, structured fee matching/installment billing at390/1280, vendor product/counting at390/1280 passed. These source flows were unchanged by the final admission work.
- **Static/rendering:** money PHPStan level5 passes; scoped Pint, JavaScript syntax and E2E TypeScript pass; **117 Finance/Vendor Blade templates compile and lint clean** without changing the shared running view cache. Domain `git diff --check` is clean.
- **Admission browser:** final five-case serial run (390/1280 original-operation recovery and issued-type/cancellation; read-only policy) is in progress. This is the remaining domain qualification, not a gap hidden by the14-row trace.

Durable logs: `/workspace/runtime/admin-ui/finance-ticket-qualified.log`, `finance-final-qualified.log`, `phpstan-finance-ticket.log`, and `finance-tickets-browser-qualified.log`. Final Finance evidence is in `results-finance-final-qualified`, including actual posted invoice, holder statement, published subscription invoice and retained settlement screenshots at390/1280. The phone statement and desktop invoice screenshots were visually inspected. Ticket evidence goes to `results-finance-tickets-qualified`.

No production or deployed panel was mutated. No code was committed by this domain agent.
