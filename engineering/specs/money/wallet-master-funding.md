# Verified school-owned master funding

Increment 8A records cash or EFT already received and verified as school-owned.
It credits the master school wallet against its declared cash or wallet-bank asset,
using balanced integer cents. This is the school funding flow; parent-owned money
must retain its original holder through the separate parent funding flow. It does
not issue a personal top-up, execute an outside transfer or activate a gateway.

Finance explicitly configures supported manual funding methods and wallet currency.
No method or currency is guessed. Receipts retain original date, canonical external
reference, school collector, zero fee, exact net, settlement reference, original
policy revision and encrypted private evidence. School/account form bindings and
fresh separate wallet Finance permission apply before permanent operation replay.
Currency is fixed after personal issuance or master funding. Disabled wallets retain
read-only Finance receipts and balances.

A shared immutable external receipt registry binds fee and wallet receipts to one
school/method/reference and one posted entry. Existing canonical fee receipts are
indexed without editing or reposting financial history. Both recorders check this
identity under the common school lock; SQL also registers receipts atomically and
refuses cross-domain reuse. Funding SQL requires exactly its two declared wallet
postings, asset type, master reference, currency and current funding policy. Cached
projection drift cannot authorize another receipt. The nightly ledger report also
checks funding attribution and registry consistency before rebuilding projections.

Finance uses School wallet funding in the Money navigation, chooses methods,
records verified funding and opens a private printable receipt. No private evidence
enters old-input flash or parent APIs. Retained funding/registry history prevents
migration rollback; forward corrections preserve the original receipts.

Desk grants with a second approval above an explicit school threshold, parent
funding/transfers and withdrawals remain subsequent parts of increments 8–9.
Do not mark increment 8 complete from the master funding work alone. Payer debt
corrections/late-charge policy in increment 6 remain outstanding as well.
