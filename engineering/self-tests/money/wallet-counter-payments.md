# Increment15 — online wallet QR and SUN counter payments

Optional `wallet_payments` starts disabled. A reviewed personal QR binds one
wallet, counter, cart and exact integer-cents amount for two minutes without
moving money. A dedicated Vendor operator confirms one payment and fulfilment.
SUN requires a counter-bound reader, current issuer/operator authority, verified
MAC and increasing chip counter, active approved cardholder, current private
portrait and explicit photo/amount confirmation. No balance is stored on a card.
No bank-card gateway, offline debit or Phase6 collection is activated.

The financial authorizer and actual Vendor operator remain distinct. Existing
ledger/stock/ticket roots commit atomically with immutable intent/order/outcome
lineage. Permanent scoped UUID recovery either returns the original receipt or
abandons an unknown request before its delayed confirmation can debit. Replayed
receipts never instruct a second handover. Existing history stays private and
readable under fresh authority after expiry or module pause.

Executed local evidence:

- Integrated backend:48 tests /1,715 assertions, including private API response
  contracts, QR and SUN/photo confirmation, no first-app-login requirement for an
  approved learner, wrong role/school/holder/reader, stale/expired/cancelled/frozen
  authority, changed portrait, separate actual operator attribution, digital
  admission issuance, SQL retention, missing final-receipt reconciliation, Finance
  forms and existing orders/tickets/tenant ownership.
- Separate-process races:6 tests /59 assertions. Same UUID replays once; distinct
  UUIDs cannot pay one instruction twice; competing instructions cannot overdraw
  a wallet or bypass the daily cap; cancellation/payment has one terminal outcome;
  recovery either observes the committed payment or prevents a delayed debit.
- Browser:5 cases passed. Vendor QR and real backend SUN/private-photo review,
  exact confirmation, replay, paused history, axe and overflow checks ran at390,
  820 and1280px. Finance registration/revocation and Teacher refusals passed.
  The generated portrait and cryptographic vector are synthetic; NFC hardware
  itself was not used by these browser tests.
- Vendor native software:70 tests passed in the final Vendor/build-identity suite, including responsive QR and SUN/photo
  widgets at390/820/1280px, no debit before explicit confirmation, permanent lost-
  response recovery, malformed acknowledgment recovery, current scope, strict
  NDEF URI parsing and clearing of private views/reader pairing on background.
- Shared wallet:119 tests passed, including four live pushed-payment-route
  regressions. The combined20 targeted payment/boundary tests also passed;
  school, guardian, payment pause and disable changes erase an already-open QR.
- Compiled actual Learner, Parent, Teacher and Facilitator Chrome profile journeys
  passed using controlled transport: quote, create/check/cancel original payment
  QR without debit, existing purchase/cancellation, ticket transfer/revocation and
  original wallet flows. Actual NFC/camera device acceptance remains separate.
- Migration26 empty up/down/up and populated pre26 upgrade passed. Four original
  non-empty orders and all original financial fields remained byte identical.
  A new payment replayed its original receipt once, reconciliation passed, and
  rollback with retained evidence was refused. Rehearsal caught and fixed stale
  worker mass-assignment column caches; trusted channel metadata is set explicitly.
  Local MariaDB empty rollback passed; the migration selects the database-specific
  MySQL/MariaDB check-drop syntax.
- Money and panel PHPStan passed; fresh Pint passed38 changed/new PHP files.
  OpenAPI lint and e2e TypeScript passed; translations/navigation/design/Bootstrap
  checks passed18 tests /63,804 assertions; production documentation build passed.
  New locale keys use the approved glossary or English fallback pending fluent
  review. Full app analyzers reported no errors or warnings; existing style-only
  notices remain outside this feature and its own new notices were corrected.

Final API inventory passed663 tests /2,899 assertions. SDK generation repeated
byte identically across2,800 Dart files; all182 SDK tests passed and analysis
reported no errors or warnings (existing generated style notices remain).
The final119-test wallet suite and wallet analysis passed. The compiled actual
Vendor flavour passed both journeys with controlled QR/NDEF input: existing
order handover, settlement, ticket admission, QR or SUN/private-photo payment,
exactly one financial outcome and revoked-session clearing. These are five
actual app profiles with controlled transport, not physical NFC/camera proof. Exact-source hosted review/CI
and main integration remain separate. Staging, physical NFC/camera, iOS signing/
build/device, fluent-language and independent money/security acceptance are
external release records; none is represented as run by a controlled fixture.

Native configuration preflight now checks the Vendor camera/storage/NFC branch
separately from the Facilitator URL handler and Teacher-only generated plugins.
Signed identities, all eighteen named configurations, three schemes, nine Pod
mappings, Vendor-only NFC entitlement/permission and fourteen Crashlytics shell
cases passed. Five injected unsafe plugin/identity changes were refused. Dedicated
Vendor and Facilitator identity tests also passed9 cases each with their actual
Dart flavour definitions. This static check does not compile Android/iOS code.

Published delivery and current hosted/main status:
[PR346](https://github.com/ShawnDaPrawn/Eschool/pull/346).

App catalogue follow-up: every offered locale now has the new English keys with
explicit fallback text where translation is pending. Existing translations are
preserved. The complete Learner/Parent app suite passed126 tests; the complete
Teacher app suite passed176 tests. Payment application code, API and previously
compiled English-profile journeys are unchanged by this catalogue-only fix.

An inherited cash-custody review finding is corrected in this cumulative source.
See [cash-count boundary qualification](cash-count-boundaries.md) for the retained
source proof, repeated payout/confirmation races and populated upgrade checks.
This backend correction does not change the counter API or app implementation.

Hosted integration follow-up: a paused dependency used to make an unused optional
money module appear enabled through inherited read-only state. Only the module's
own retained history can now keep it readable; dependency pauses still prevent
writes. A regression covers default-off payments/ticketing, context omission,
other-school state and retained history after entitlement ends. Eight affected
route tenancy checks passed64 assertions using valid payment/operation UUIDs.
The Vendor session check permits the capability flag while still refusing wallet
identities, balances, private credentials and bank instructions. Nine responsive
catalogue/payment/reader browser cases passed, including retained catalogue
history and Teacher refusal. The new count-boundary migration is also included
in the configured money static-analysis inventory; both analyses and Pint passed.
The final combined module-controls, vendor catalogue, Vendor operator access and
wallet counter-payment regression run passed56 tests /742 assertions.
