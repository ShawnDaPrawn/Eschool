# HQ billing: invoices, EFT payments and overdue accounts

Action Plan 1.17 (school billing in Super Admin, EFT) and the Build 1 part of
SA.8 (billing, payments and revenue). Owner role: Backend and Admin UX (HQ).

Navigate Ed bills each school monthly, per learner, from `/saas/billing`.
Schools pay by EFT; HQ records each payment. This is Navigate Ed's own billing
of its customers. It is **not** the school's fee ledger: nothing here posts to
`ledger_*`, `invoices` or `payments`, and a school never sees these tables in
this step.

## User stories

- As Finance, I create the month's draft invoices for every active school in one run, review them, and issue them.
- As Finance, I recompute a draft after a price or contract changes, or decide not to bill a school for a month.
- As Finance, I correct an issued invoice with a credit note that refers to it, knowing the invoice itself never changes.
- As Finance, I record an EFT payment with its amount, date and bank reference, say which invoices it settles, and keep anything over as credit on the school's account.
- As Finance, I reverse a payment recorded by mistake, with a reason, and the record stays.
- As Finance, I see what is overdue, remind the school, and see which schools are past their grace period.
- As an Owner or Operations, I decide whether to suspend a school that is past its grace period, using the existing status form with a reason.
- As Operations, I read billing (to answer a school's questions) but change nothing.
- As anyone, I print an invoice or a school's statement.

## Rules

### Who

| Ability | Roles | Allows |
| --- | --- | --- |
| `billing.view` | Owner, Finance, Operations | the billing page, statements and invoices |
| `billing.manage` | Owner, Finance | runs, recompute, issue, discard, credit notes, payments, reversals, applying credit, reminders |
| `schools.status` (existing) | Owner, Operations | suspending a school |

Operations reads billing because it supports schools day to day; it does not
manage money. Finance manages billing but cannot suspend a school; an Owner or
Operations decides that. Sales, Content and Support have neither ability. The
FormRequest (`BillingRequest`) checks `billing.manage` and the services check it
again (403). School accounts never reach `/saas`.

### Which price wins

**The school's contract wins.** If the school has a contract
(`school_contracts`) in force on the first day of the month (started on or
before it, and not ended before it; the latest-starting one if several), the
invoice has one line: learners × the contract's price per learner. The contract
is what the school signed, so no list discount or add-on is added to it.
Without a contract, the list price of the school's package on the month's first
day (`PlatformPackages::quote`):

- **Base:** learners × the package's base price.
- **Discount:** learners × the list discount, as its own negative line.
- **Add-ons:** for each product module of the school's applied package version
  (not Core) that has an add-on price in force and that the school has switched
  on (any of its technical modules is enabled at the school), learners × the
  add-on price. A product module switched off is not billed. (Following
  "monthly bill = students × (base + every module elected)".)

With neither a contract nor a list base price, the draft says so and cannot be
issued until a price or contract exists and the draft is recomputed.

### Learners

Counted on the day the draft is computed (the run date or a recompute), and
kept on the invoice with that date: active `Student` memberships of that school
that day (`SchoolMembership` status active, starts and ends around the day), on
enabled, undeleted accounts, less learners who have left (their latest join or
leave on or before the day is a leave dated before it, as
`LearnerAcademicAccess::departed()` decides). Each learner counts once. Another
school's memberships never count. Contracted learners are shown for comparison;
the actual count is billed.

### Money and VAT

- Integer cents throughout; rands are typed as text and parsed with `Money::fromDecimalString`, never a float.
- Prices are per learner per month **excluding VAT**.
- VAT is `config('platform_billing.vat_percent')` (15 by default; `PLATFORM_BILLING_VAT_PERCENT`), stored on each invoice in basis points (`vat_rate_bp`, 1500). A later change of rate never changes an invoice already computed.
- **Rounding: per line, then total.** Each line's VAT is its amount × the rate, rounded to the cent half up (away from zero) on the line. The invoice's VAT is the sum of the lines' VAT; its total is the sum of the lines plus that VAT. The printable invoice states this. (Example: two lines of R 10.10 each have R 1.52 VAT, R 3.04 in all, where 15 % of R 20.20 would be R 3.03.)

### Invoices

- **Run.** `Create drafts` for a month makes a draft for every active school that has no invoice for that month yet. A month's invoice has a unique slot per school (`school_id`, `"2026-11"`), so running again creates nothing new. Suspended and archived schools are skipped. Months from twelve back to next month can be run.
- **Draft.** Can be recomputed (counts and prices again) or discarded ("do not bill this month": kept, marked discarded, and a run does not make it again). A draft is not sent anywhere.
- **Issue.** One draft, or every ready draft of the month. A draft with no price or nothing to bill (total R 0.00) stays a draft. Issuing gives the next reference of the year under a row lock (`platform_counters`, `NE-2026-0001`; the prefix is config), the issue date (today, South African time) and the due date (issue date + `payment_terms_days`, 30 by default). References never repeat or skip: drafts have none.
- **Issued invoices never change.** Every route refuses, and the models refuse any change to an issued invoice or its lines (only the reminder columns are written later) and any delete.
- **Credit notes.** A correction is a credit note against an issued invoice, issued at once with a reason: either the whole invoice (every line negated exactly; only when nothing was credited before) or an amount excluding VAT (one line, VAT at the invoice's rate, rounded on the line). Never more than is left to credit (the invoice total less earlier credit notes). It takes the next reference and refers to the original. Its credit settles what is unpaid on the original; anything more is credit on the school's account. A credit note cannot itself be credited.

### Payments and credit

- **Record.** Amount (rands), date received (not in the future), the bank's reference, an optional note, and how much settles each of the school's issued invoices. Each amount is at most what is unpaid on that invoice, and together at most the payment. What is left over is **credit on the school's account**.
- **One bank line, one payment.** A bank reference may not repeat on another payment of the school that stands (a reversed one may be recorded again correctly).
- **Account credit** (over-payments and credit-note credit not yet used) is applied to an invoice with `Settle from account credit`: credit notes first, then payments, oldest first, up to what is unpaid.
- **Reversal.** Nothing is deleted. A mistaken payment is reversed with a reason (10 to 500 characters): it stays on the statement with the reversal, and everything it settled is unpaid again.
- **Unpaid** on an invoice = its total less the allocations that stand (payments and credit notes). **Balance** = issued invoices and credit notes less payments that stand; negative is credit.

### Statement

Per school: every issued invoice, credit note, payment and reversal in date
order with the running balance; unpaid invoices; account credit and where it
came from; drafts listed apart (not on the statement). Prints without the
menus and forms.

### Overdue, grace and suspension

- An issued invoice is **overdue** from the day after its due date while anything on it is unpaid. The billing page lists each school with an overdue invoice: the amount, the oldest due date and how long ago, and the last reminder.
- **Reminder** (`billing.manage`): a notice of the new type `billing.overdue` (school module, admin portal, in-app and email mandatory, push on by default, locked) through `Notifier` to the school's Super Admins with an active membership, and an email to each bursar contact on HQ's school record (`school_contacts`, role bursar, with an email). It names the overdue amount and invoice references, never anything else. Each overdue invoice records when it was last reminded and how many times; the audit names the invoices, how many Super Admins and which bursar contacts. Refused when nothing is overdue.
- **Grace.** After `grace_days` (14 by default) past the oldest unpaid due date, the school is listed as **due for suspension**.
- **Suspension is never automatic.** The list shows Owner and Operations the existing suspend form (`PUT /saas/schools/{id}/status`, reason required, audited as `platform_school_status_changed`); Finance sees who to ask. Restoring is the same existing form.

### Revenue summary (billing page, per month)

Invoiced (the month's issued invoices including VAT), credit notes issued that
month, collected (payments received that month that stand), overdue now (all
months) and a simple monthly recurring revenue: the month's issued invoices
excluding VAT less credit notes against them. The full revenue dashboard is
later.

### Audit

`platform_billing_run` (month and counts), `platform_invoice_recomputed`
(before and after totals), `platform_invoice_issued` (reference, totals, VAT
rate, dates), `platform_invoice_discarded`, `platform_credit_note_issued`
(reference, the invoice it credits, totals, what it settled, reason),
`platform_payment_recorded` (amount, date, bank reference, allocations, credit),
`platform_billing_credit_applied`, `platform_payment_reversed` (amount,
what it unsettled, reason), `platform_billing_reminder_sent`. Each carries the
school and who did it.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_invoices` | school-owned (required) | kind (invoice, credit note), status (draft, issued, discarded), reference, period, month slot (unique per school), the invoice it credits, package and version, price source (contract or list), contract and contracted learners, learners and the day counted, currency, VAT rate in basis points, subtotal, VAT and total in cents, why a draft is blocked, credit note reason, issue and due dates, who computed, issued or discarded it, reminders |
| `platform_invoice_lines` | school-owned (required) | position, kind (base, discount, module, credit), product module, description, learners, unit price, amount and VAT in cents |
| `platform_payments` | school-owned (required) | date received, amount in cents, currency, bank reference, note, who recorded it, reversal (when, who, why) |
| `platform_payment_allocations` | school-owned (required) | invoice, the payment or credit note settling it, amount in cents, who, reversed when and by whom |

Each row is about one school, so each carries `school_id` (not null, indexed,
a foreign key) and is written inside that school (`CurrentSchool::run`); the
models use `BelongsToSchool`, so another school's id is not found. Only HQ
reads them. Config: `config/platform_billing.php` (VAT, terms, grace, prefix,
the seller details printed on invoices). Migration
`2026_11_01_180000_create_platform_billing`; its `down` refuses once any row
exists.

## Endpoints (HQ panel)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/billing?month=YYYY-MM` | `saas.billing` | `billing.view` |
| GET | `/saas/billing/schools/{school}` | `saas.billing.statement` | `billing.view` |
| GET | `/saas/billing/schools/{school}/invoices/{invoice}` | `saas.billing.invoices.show` (printable) | `billing.view` |
| POST | `/saas/billing/runs` | `saas.billing.runs.store` | `billing.manage` |
| POST | `/saas/billing/runs/issue` | `saas.billing.runs.issue` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/invoices/{invoice}/recompute` | `saas.billing.invoices.recompute` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/invoices/{invoice}/issue` | `saas.billing.invoices.issue` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/invoices/{invoice}/discard` | `saas.billing.invoices.discard` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/invoices/{invoice}/credit-notes` | `saas.billing.invoices.credit` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/invoices/{invoice}/apply-credit` | `saas.billing.invoices.apply-credit` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/payments` | `saas.billing.payments.store` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/payments/{payment}/reverse` | `saas.billing.payments.reverse` | `billing.manage` |
| POST | `/saas/billing/schools/{school}/reminders` | `saas.billing.reminders.store` | `billing.manage` |

Writes are throttled (30 a minute). Every write uses `BillingRequest`;
`PlatformBilling` and `PlatformBillingAccounts` validate the same rules and
check `billing.manage` again. No API route or response changes.

## Acceptance tests (`Platform/HqBillingTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_a_run_drafts_every_active_school_once_with_the_contract_price_winning_over_the_list_price` | a list price with discount and add-on; School A has a contract | Finance runs the month, again, the next month with School B suspended; bad months; prices removed | A: one contract line, no discount or add-on; B: base, discount and add-on at list; the second run changes nothing; B skipped when suspended; bad months refused; without a price B is blocked and cannot be issued, and the page says why |
| `test_add_ons_and_the_discount_are_lines_and_vat_is_rounded_per_line_then_totalled` | School B with one learner, AI tutor on and Futures off | run; change the VAT rate and recompute | lines base, discount, AI tutor (not Futures); VAT 152, -1, 152 = 303 where the subtotal would round to 302; the rate is config and kept per invoice; the page shows the rate and the rounding rule |
| `test_learners_are_counted_on_the_run_date_without_leavers_or_other_schools_learners` | School A's learners | a leave today, then dated earlier, then a rejoin; an ended membership; a disabled account; a learner also at School B; a run and a recompute | still counted on the day they leave, not after, counted again after rejoining; ended and disabled not counted; School B's membership never counts for A; the draft keeps the count and its day; recompute counts again and is audited |
| `test_issued_invoices_get_sequential_references_and_never_change` | both schools issued | recompute, issue or discard an issued invoice; change or delete it or its lines through the models; discard B's next draft, run and issue again | NE-YYYY-0001, 0002, then 0003, none repeated; due in 30 days; audited per school; every change refused and nothing written; the discarded draft stays discarded with no reference; the printable invoice shows the reference and EFT instruction |
| `test_a_credit_note_corrects_an_issued_invoice_and_refers_to_it` | both schools issued | credit R 100 on A; too much, whole after part, a credit note's credit, zero, no reason; credit B in full | the next reference, -R 115 with VAT, settles A by R 115, audited, A unchanged; refusals write nothing; B's lines negated exactly and B paid; pages show it |
| `test_payments_settle_part_or_all_an_over_payment_is_credit_and_a_reversal_keeps_the_record` | A issued | part-pay; repeat the bank reference; over-allocate, future date, malformed amount; over-pay; apply credit to next month; reverse the first payment; record it again; edit a payment | unpaid falls by the part; refusals write nothing; R 50 credit, balance -R 50; next month settled by R 50; reversal kept with its reason and A unpaid by R 100 again, audited; the reference can be used again; editing refused; statement balance and the page's summary agree |
| `test_overdue_invoices_go_from_grace_to_due_for_suspension_and_suspending_stays_a_decision` | both issued, B part-paid | the day after the due date (A pays), then 15 days on | B overdue in grace by what is unpaid; then due for suspension and still active; Finance sees it with no form and cannot suspend; Operations sees the form and suspends through the existing flow with its reason |
| `test_a_reminder_goes_to_that_schools_super_admins_and_bursar_only` | Super Admins and bursar contacts at both schools | remind A before and after its due date | refused before; after, one `notification.requested` for School A's Super Admins only, a `billing.overdue` notice naming the reference, an email to A's bursar only, reminder recorded on A's invoice only, audited |
| `test_sales_content_and_support_cannot_manage_billing_and_nothing_is_written` | an invoice and a payment | Sales, Content, Support and Operations call every write endpoint and the services directly | 403 everywhere and nothing written; Operations reads the pages without forms; the others cannot read them |
| `test_school_accounts_are_refused` | a school Super Admin and a teacher | read or write billing | 403; nothing written |
| `test_another_schools_invoice_or_payment_is_not_found` | School B's invoice and payment | every action through School A's URLs; a School A payment naming B's invoice; an unknown school | 404, or a validation error for the allocation; nothing written; A's statement shows nothing of B |

Existing tests that cover changed code still pass: `Platform/SaasPanelTest`
(its /saas route count rises by the thirteen routes above, to 57),
`Platform/PlatformRolesTest`, `Tenancy/SchoolOwnedTablesTest`,
`Platform/NotificationRulesTest`, `Platform/NotifierTest`,
`Platform/PackageEditorTest`, `Platform/HqIncidentsTest`,
`Platform/HqSchoolAccountsTest`, `Platform/HqOnboardingTest`.

## Not in this step

- A school-facing "your invoices" page, and emailing the invoice PDF itself: schools do not read these tables yet.
- Automatic reminders on a schedule: a reminder is sent by HQ from the overdue list.
- Matching a pasted or imported bank statement line to a school (the school fee bank-matching pattern); the bank reference is recorded and refused when repeated.
- Proration for a school that starts or leaves mid-month, trial periods (`trial_days`) and minimum billing on contracted learners.
- Volume discounts, per-school add-ons outside the package, and add-ons on top of a contract price.
- Mastery subscription revenue per school and family (SA.8 Build 3), the full revenue dashboard (annual run rate, revenue per learner, churn) and an accounting export.
- Card or debit order payments: EFT only.

### Retry and concurrency safeguards

- The credit-note form sends a UUID idempotency key. It is unique per school and
  retained on the immutable credit note with a hash of the invoice, amount/full
  choice and reason. An identical retry returns that note; changed input with the
  same key is refused. Run migration `2026_11_01_181000` as well as the billing
  migration. Rollback refuses to discard used request keys.
- Account mutations lock the school before invoices and credit sources. Balance
  checks on writes read allocation rows with current locks, including under an
  already established MySQL REPEATABLE READ snapshot. Spent payments and credit
  notes cannot be reused after waiting for another request.
- `HqBillingTest` covers identical and changed replays, missing keys, and real
  two-connection snapshot visibility for both payment and credit-note sources.
