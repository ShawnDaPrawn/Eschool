# Reconciled arrears and accounting exports

Remaining Phase5 increment1, authorized with delivery through increment15.
Finance uses the existing fresh `fee-payments-manage` permission. The report
reads the immutable fee subledger only. Abandoned legacy seed money is neither
imported nor combined with these balances; the owner permits its later removal.

Current ageing is calculated on the school's current calendar date. Due today
and future charges are current; overdue buckets are1–29,30–59,60–89 and90+ days.
Each sealed invoice-holder share uses its net credit notes and effective payment
allocations. Family totals sum those shares exactly. Paid credits restored to
receipts remain held credit. Pinned/other-holder credits never silently offset
invoice debt. Currencies are filtered and totalled separately. Reports are current
snapshots, not falsely labelled historical balances. Archived accounts remain.

Queries load accounts/shares in bulk, validate domain/posting reconciliation and
calculate held balances from posted family-credit history. A school lock provides
one consistent snapshot across invoices, receipts, releases and settings. Limits
are20,000 accounts/50,000 invoice-holder shares per currency, with explicit refusal
before loading oversized detail. Totals use checked integer additions, never floats.

Private CSV downloads include aged family balances, invoice-holder detail and
posted fee journals. Journal periods use school-local dates converted to UTC
boundaries and contain complete balanced posted entries; incomplete or mixed-module
entries require reconciliation/consolidated export. Boundaries are at most366days
and50,000 posting rows. Stable LE journal references support tracing and avoiding
external duplicate imports. No export posts money or calls an accounting provider.

Xero and QuickBooks column presets use explicitly configured chart codes/names,
organisation base currency and date format. Xero additionally requires the exact
zero-tax label for the organisation. A mismatched currency or missing exported
account mapping refuses that package export; generic reports remain available.
These are CSV column presets, not a claimed live accounting integration. Check
headers/mapping against the accountant's actual import template/version and import
each journal once. Xero journals use signed amounts (debit positive); QuickBooks
columns separate debits/credits. Existing explicit no-VAT history stays no-VAT;
subsequent taxable billing will add its own reviewed VAT postings/mappings.

CSV escaping handles commas/quotes/newlines and prefixes formula-leading text
with an apostrophe; canonical numeric amount columns retain signed decimal cents.
Finance mapping revisions use permanent actor-bound operation receipts and audit.
Exports record layout/currency/count/period/mapping revision and a file digest,
without storing private CSV contents in audit, sessions, URLs or browser storage.
Settings are school-owned; private writes bind actor/school, CSRF and UUID. Disabled
fees retain read-only reports/exports but refuse configuration changes. Reports
and all error/download responses are private/no-store. Parent/learner/support roles
cannot read Finance reports; existing parent statements remain independently scoped.

Qualification covers exact boundaries, split holders, partial allocation, unpaid
and paid credits, currency and tenant isolation, formula injection, mapped CSV
columns, journal balance/periods, stale mapping/UUID replay, role/actor binding and
read-only history. Finance journeys run at390/820/1280px with accessibility,
overflow, download contents and unchanged ledger assertions. No gateway is needed.
