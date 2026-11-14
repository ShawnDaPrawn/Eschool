# School subscription invoice read contract (SE075)

The revision-3 design previously identified an HQ-only invoice route. The new school panel adapter makes the issued school invoice review available without granting or reusing HQ billing permissions.

- `GET /finance/subscription-invoices` lists this school's issued invoices and credit notes in pages of 50.
- `GET /finance/subscription-invoices/{invoice}` shows the published invoice, original lines, issued credit notes and payment/credit allocations. Reversed allocations are retained and do not reduce the outstanding amount.
- There is no school selector, mutation endpoint, payment submission, correction, draft issue action, HQ link or platform permission grant. Submitted `school` or `school_id` parameters are rejected.
- Authority is a fresh active local user with an active **Super Admin** membership of the current active school. Password-change-required users, active platform staff, cross-school context and impersonation are refused. Finance permission alone does not authorize this new read.
- Every model is tenant scoped. The detail query also requires `status=issued`; foreign IDs and draft/discarded/grouped invoice IDs return 404. Group consolidation is not exposed as an individual school invoice.
- Projection is an explicit allowlist: invoice reference/kind/package/period/currency/issue and due dates/totals, description and quantities of lines, allocation date/reference/amount/reversal state, school name and current school access state. Private HQ notes, block diagnostics, contract/signatory details, platform audit events and operator IDs never reach the view.
- Outstanding balances use the existing integer-cent allocation calculation. The adapter posts nothing, changes no ledger entry, and never manufactures a payment status from browser input.
- All responses use `PrivateDomainResponse` (`private, no-store`); no browser draft storage applies to this read-only screen.

Implementation: `SchoolSubscriptionInvoices`, `SchoolSubscriptionInvoicePanelController`, `routes/panel-finance-ui.php`, `money/subscription-invoices/{index,show}.blade.php`.

Verification contract: `FinanceUiWorkflowTest::test_school_subscription_invoice_read_excludes_drafts_foreign_schools_private_notes_and_writes` exercises a partially paid issued invoice with real original lines/allocations, hidden private note/diagnostic fields, foreign and draft direct reads, wrong role, active HQ identity, school-parameter injection and absent POST support.
