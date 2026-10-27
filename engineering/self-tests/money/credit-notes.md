# Fee credit-note verification

This increment implements reviewed corrections to recorded invoice shares, not
automatic refunds or payouts. Original invoices, receipts and allocations remain
immutable; restored funds retain their original holder and learner restriction.

Executed locally on PHP 8.3/MySQL: the initial credit/payment cohort passed
**32 tests / 314 assertions**. Additional actual panel and parent API cases passed
**13 credit tests / 123 assertions**. These overlapping cohorts are not summed.
They cover unpaid/paid/partial credits, latest-allocation release order, repeated
release/reallocation, gross historic allocations exceeding a reused receipt, exact
replay after days, stale reviews, amount/holder limits, transaction rollback after
a later write fails, database immutability and retained rollback refusal. Tenant,
role, actor binding, private errors, disabled-history reads, encrypted explanations,
parent ownership/visibility and OpenAPI response validation are exercised.

The parent package passed **24 tests**, including gross/net/released arithmetic,
malformed responses, private session changes, late replies, older-server defaults
and usable corrections at 390/820/1280px. The first run exposed inferred fixture
map types rejecting additional fields; explicit fixture map types corrected it.

Finance browser qualification passed **4 tests**, zero automatic retries. The full
review/confirm/detail/statement journey passed at 390/820/1280px, with no posting
before confirmation and exact-form replay retaining one note/release. A 300-cent
paid credit changed income from 2002 to 1702, restored 300 held cents, retained
1102 receivable cents, the original 900-cent bank receipt, both original allocation
rows and both inactive synthetic learners. Teacher access was refused. Serious/
critical axe findings, horizontal overflow, duplicate IDs, private headers and
console/HTTP errors were checked. The browser test caught a missing net-total
label on the Finance summary; that total was added before the successful run.

Pinned SDK generation, browser TypeScript and baseline-free money level 5 analysis
have passed. Final broader regression and complete app/hosted qualification are attached
to the feature PR. New labels have English fallback in all offered catalogues;
fluent translation review, physical-device/staging/user acceptance, real cash/bank
reconciliation and independent live-money review remain separate. Nothing here
authorizes a production release or activates a payment provider.
