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
