# Admissions and leadership receipt handover

This is a preparation contract for the money foundation in
[ledger-foundation.md](ledger-foundation.md). It does not post, import, migrate or
restate historical money. The existing office acknowledgement and legacy receipt
paths keep their present meaning until a separately qualified adapter is enabled.

## Existing sources and their distinct meaning

| Source | Current authority | Meaning at this boundary |
| --- | --- | --- |
| `applications.office_fee_cents`, `office_fee_paid` and encrypted `application_events` | Fresh admissions office authority; exact operation UUID and application revision | Office acknowledgement that permits the accepted application to proceed. It is not a ledger posting or a payment-provider receipt. |
| `payment_transactions` | School-scoped settled, non-deleted receipt, exact cents, recognised fee kind and a defensible receipt date | Existing fee receipt and installed-client history. |
| `fees_paids` | Exactly one matching school/learner/class/year running balance | Reconciliation check against receipt totals. Never a second collection event. |
| Future ledger entry and source link | The qualified money posting service and its fresh source policy | A separate immutable accounting event, whose source identity must prevent duplicate import and retry. Not implemented by this preparation change. |

An admissions acknowledgement can precede creation of the learner and family
accounts. Its durable origin is therefore the school, application and
`office_fee_acknowledged` event, rather than a guessed learner or payer account.
The saved office reference is private audit information; it must not become an
unrestricted description or notification payload.

## Office-fee cutover

1. Keep previous acknowledgements and operation receipts as history. Enabling
   the ledger must not scan acknowledged applications and automatically create
   cash, revenue, invoices or opening balances.
2. A historical import requires an explicit, reviewed reconciliation decision:
   school, exact cents, currency, authoritative receipt/date/method, and an
   immutable source mapping. An acknowledgement alone does not establish those
   accounting facts. Missing evidence remains an exception for review.
3. Use a school-bound unique source reference for each accepted historical event
   or legacy transaction. Retrying the same source must find its existing mapping
   and entry. A different UUID must not bypass source uniqueness. Human matching
   of an acknowledgement to a receipt is explicit; equal amounts or free-text
   references are insufficient for automatic matching.
4. A prospective gateway collection must correlate to the exact application fee
   and create its accounting effect once, after verified provider confirmation.
   The office-paid status may then reference that receipt; it must not post the
   same payment again. Browser return, contact-email proof and offer acceptance
   are not provider confirmation or learner activation.
5. Unknown write outcomes retain the original operation/source reference and
   recheck current school, office authority and source revision before receipt
   recovery or retry. Suspension, school switching and revocation do not permit
   replay through an old cached actor. Never replace an unresolved UUID to make
   a repeated acknowledgement appear new.
6. Corrections append the qualified money adjustment with a reason and preserve
   the original source link. No historical repost or financial correction is
   performed by the admissions boolean, enrolment retry or data-retention job.

## Leadership projection cutover

The current `LeadershipFeeCollections` adapter continues to read only reconciled
legacy receipts. Admissions acknowledgements, running balances and the mere
presence of future ledger tables must not change that collection total.

Future transition needs a declared per-school/currency coverage boundary and
immutable origin links before combining sources. For each original receipt,
choose exactly one authoritative representation; never add a migrated receipt
to its ledger mirror. A partially mapped or ambiguous overlap remains unavailable
until reconciled. It must not silently become zero or an inflated total.

Preserve the current metric's basis: current recorded receipt amounts, recorded
receipt dates and one matching dated enrolment/grade. A later ledger cash-flow
or refund-date metric is a distinct labelled projection. Do not reinterpret
legacy corrections as dated bank movements or move old receipts to today's
grade, import time or gateway settlement time. Retain exact cents, source-module
read-only history and fresh principal/admin authority before and after reading.
No new per-family financial details are exposed through the grade aggregate.

## Joined verification

The preparation cohort exercises actual admissions enrolment, one-use guardian
invitation acceptance, explicit approval and withdrawal, future membership dates,
school switching, exact enrolment replay, and consented academic transfer with
fresh guardian membership. Receiving-school approval and fee rights remain
separate from the transferred identity and historical school records.

It also acknowledges a real synthetic admissions office fee, replays it under
revoked and foreign-school authority, and verifies that leadership first counts
zero receipts and then exactly one reconciled legacy receipt. Neither the
acknowledgement nor its retries nor the running balance is added to that receipt.
These tests establish the present boundary; they do not qualify a future ledger
importer, payment provider, financial opening balance or production migration.
