# Wallets

Finance configures Wallets separately from fees, selecting currency, a school daily
maximum and whether guardians must configure child limits. Finance access requires
wallet-finance-manage. Currency is fixed after the first wallet is issued.

Parents, learners, teachers and facilitators open Wallets in their existing app.
Opening a wallet creates a zero balance. Current verified guardians can open linked
child statements and set a daily cap and permitted category restrictions. Learners
read their own balances and cannot raise controls. Zero stops purchases. Disabling
wallets retains private statements and Finance history in read-only mode.

Funding and spending arrive in the following Phase5 increments. No external payment
provider is enabled by these controls. Never enter bank-card information here.

## Desk grants

Desk wallet top-ups reserve school-owned master funds. Amounts above the school's
explicit threshold require another current desk operator. Recipients and approving
guardians see pending, credited and cancelled receipts in their wallets. Desk-only
permission does not grant bank funding or policy changes. See the Money desk guide
and engineering/specs/money/desk-wallet-topups.md for attribution and retention rules.

## Parent transfers and interrupted requests

Verified parents can move funds between their own and an approved child's wallet.
Child returns require the explicit school policy in Money → Wallets. An interrupted
request retains its original operation until recovery confirms the posted result
or safely closes it; a new money intent cannot silently replace an unknown outcome.
Parent deposits are a separate verified-receipt flow, not school master funding.

## Storefront purchases and original receipts

Wallets → Shop and orders exposes the selected school's active vendors. Review
the source wallet, quantities and exact quoted total before confirming. Checkout
rechecks current ownership/guardian approval, allowed categories, daily caps,
prices and stock atomically. Orders debit the original wallet and credit that
vendor; setup creates no money. Free orders still consume counted stock without
zero ledger postings.

Your orders retains private original seller/tax/item receipts and an uncollected
order QR. The counter accepts the QR or order number once. Uncollected
cancellation restores the original gross to its original wallet and counted
units; collected returns are a separate process. Lost responses retain only an
opaque operation UUID and require an outcome check before another money intent.
Scope or approval changes clear displayed private data. Paused commerce retains
history but prevents new financial and handover writes.

## Event admission tickets

When the school enables Ticketing, open **Shop and orders → Admission tickets**.
The current holder sees private QR/typed admission credentials and original event,
named learner and redemption details. The school's explicit transfer rule and
current guardian checks apply. Ask the recipient for their short-lived receiving
code and review the named recipient before confirming. Transfers rotate both
credentials and move admission only; wallet balances do not move. The original
Vendor counter admits each ticket once. See the Events and admission tickets
Finance guide for configuration and paused-history behavior.
