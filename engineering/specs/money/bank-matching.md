# Bank imports and reviewed EFT matching

This Phase 5 increment adds bank evidence and a reviewed matching queue to the
existing ledger receipts. Finance uses `fee-payments-manage`, freshly checked with
school, actor and fees-module locks. No gateway, payment initiation, automatic
allocation, bank connection, wallet or production activation is introduced.

## Import contract

Use **Money → Bank statement matching → Import bank CSV**. Download the empty
headings and convert the school's export explicitly to this bank-neutral format:

```csv
received_on,transaction_reference,family_reference,description,credit,debit,currency
2026-10-27,BANK-TX-001,NEFEXAMPLE,School fee receipt,123.45,,ZAR
2026-10-27,BANK-TX-002,,Bank charge,,1.00,ZAR
```

UTF-8 (optional BOM), ISO calendar dates, comma delimiters and quoted fields are
supported. Decimal amounts have at most two decimal places and no grouping or
exponents. Exactly one credit/debit must be positive. Limits: 256 KiB, 500 rows,
240 description characters, 96 transaction-reference characters, and the existing
money bound of 100,000,000,000 cents per row. Zero, invalid, future, duplicate-ID,
truncated/malformed and ambiguous files are refused in full. Bank dates are
compared with the school's calendar, not the server UTC date. No date, column,
sign or currency guessing is performed. Provider/bank-specific mappings remain
future adapters to this contract.

Use the bank's stable transaction ID, unique across this school's fee-bank
workflow. It must be the same original reference used for an existing manual EFT
receipt. The reusable family payment reference is a separate column and never
identifies a transaction. A bank without usable unique transaction IDs needs a
qualified mapping before import; invented row numbers would undermine deduplication.

Parsing/review does not write data. A private, actor/school-bound HMAC seals the
normalized preview. Confirmation atomically retains an immutable import, its row
links and encrypted descriptions/references, plus audit and permanent UUID receipt.
Original uploads/filenames are not retained or published. Stable SHA-256 business
identity is independent of application-key rotation. Exact repeats, file renames,
row reordering and overlapping exports reuse existing lines; any changed amount,
date, currency or details under a retained transaction ID rejects the entire
import. This conservative conflict requires investigation, not another receipt.
A repeated canonical file reuses its original sealed import.

## Reviewed reconciliation

The default queue contains unresolved rows; filters expose matched, excluded and
all history. Exact family references suggest a school-owned account. Descriptions,
amounts and partial/fuzzy references never silently choose a payer. Missing or
unknown references remain unresolved until Finance verifies the evidence and
selects an explicit family and holder, with an optional eligible learner pin.

Finance enters a verification note and reviews the exact bank row, account,
holder, pin and action. Confirmation recomputes that review under locks. A changed
account revision, selection or existing receipt requires another review. Fresh
permission/module/actor checks apply to confirmation and replay.

For a bank credit, an existing EFT receipt with the same canonical transaction
reference is linked only if date, cents, currency, account, holder and learner pin
all agree. This creates no second receipt, event or posting. If no receipt exists,
confirmation calls the existing verified EFT workflow inside the same transaction:
Dr school fee-bank asset, Cr family/holder held credit, zero fees, collector school.
Invoice debt is unchanged until the existing separate reviewed allocation. A
receipt created between preview and confirmation invalidates the new-receipt
preview. A mismatching receipt blocks matching. Source uniqueness and one receipt
per bank line protect competing/repeated confirmations with fresh UUIDs.

Bank debits never become receipts. Non-fee credits/debits may be reviewed and
excluded with a private reason; exclusion retains the original row and creates
no financial posting. A resolution and its reason are immutable. Leave uncertain
rows unresolved. Mistaken financial attribution needs the separate reasoned
correction/transfer workflow, rather than editing bank evidence or reposting it.
This increment does not add a reopening or refund action.

## Boundaries and verification

Composite tenant foreign keys, immutable-history triggers and a resolution guard
require a sealed import and an exactly corresponding EFT receipt. Empty schema
rollback is supported; populated rollback refuses history loss. Reconciliation
reports incomplete imports, orphaned rows and receipt mismatches; ordinary
unmatched/excluded evidence is not an error or a development gate. Projection
rebuild refuses inconsistent domain history. Receipt and resolution, ledger,
audit/outbox and both permanent replay records commit or roll back together.

All screens/files/errors are private/no-store, escaped and permission guarded.
Private data is not flashed into old input or browser storage, included in URLs,
parent DTOs, audit payloads or notifications. The original parent API/app reads
remain unchanged and see only existing authorized receipt/statement data. Disabled
fees retain read-only bank history. School-year browsing does not prevent settling
historical obligations through these year-independent money operations.

Test parsing boundaries, exact cents, overlapping imports and key rotation,
review tampering/staleness, receipt linking/duplicates, transaction rollback,
immutable database history, role/school/holder/pin boundaries, private forms,
revocation and disabled history. Exercise Finance at390/820/1280px with accessibility,
overflow and error assertions. Real-bank mapping/reconciliation, fluent language,
staging/devices and independent live-money assessment remain external release
evidence. Arrears, accounting exports, reminders, discounts and live providers
remain separate increments.
