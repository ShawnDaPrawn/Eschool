# Increment15 — online wallet payments at a Vendor counter

The school explicitly enables the optional wallet-payments module; existing wallet
and Vendor rules remain prerequisites. A personal wallet QR is a short-lived,
Vendor/cart/exact-amount instruction created from the holder's current reviewed
quote. Issuing it moves no money. The assigned Vendor sees only the intended
counter instruction and confirms payment plus physical handover once. Unknown
operation recovery permanently abandons the original UUID, preventing a late debit.

Card tap is a separate purpose-bound SUN channel: a registered Vendor reader must
be bound to that counter and retain fresh issuer authority. The Vendor selects
priced goods before scanning. SUN MAC, increasing chip counter, active card,
current holder/guardian approval, private portrait, wallet limits and fresh school
scope are mandatory. The confirmation shows the exact goods/amount and photo.
A frozen/replaced card, changed portrait, revoked reader/operator or expired intent
requires a new scan/review. School identity cards store no wallet balance and are
not payment gateways. Card authorization does not require a learner to have
installed the personal app or completed its first password change.

A typed internal authority object distinguishes the financial authorizer from the
actual counter operator. Neither arbitrary buyer IDs nor Vendor user-token
impersonation grants a debit. The original order pricing, integer-cents ledger,
stock, category/daily caps, ticket issuance and reconciliation are reused within
one school-locked transaction. Immutable intent/outcome lineage fixes the original
payment source and actual Vendor operator. QR/card intents expire without debit;
existing receipts remain readable after expiry/module pause under fresh authority.

Finance manages reader registration/revocation. Personal profiles show the exact
payment QR and outcome; Vendor panel and dedicated scanner provide distinct
payment mode, camera QR and card/photo confirmation. Sensitive QR/card/reader
proofs and portraits stay out of query strings, logs, old input and plaintext
preferences. App background or live session changes erase cached private views.
Real NFC/camera hardware and staging acceptance are recorded separately from
controlled cryptographic/native/browser tests. No gateway or production activation.
