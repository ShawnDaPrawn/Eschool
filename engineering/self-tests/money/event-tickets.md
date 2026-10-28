# Increment14 — event admission tickets

Implementation includes explicit school-time event windows and capacity/transfer
configuration, encrypted immutable catalogue revisions, original order/ledger
checkout and digital fulfilment, general/named tickets, fresh holder/guardian
reads, rotating credentials on transfer, original Vendor counter scope and
single-use admission. Ticket transfers and admissions never post money.

Executed local checks:

- Tickets, original orders, Vendor access, module controls and school-owned table
  inventory: 56 tests /1951 assertions. Additional actual receiving-code expiry
  check with the complete ticket suite: 13 tests /137 assertions.
- Five independent-process races /45 assertions: two wallets competing for the
  last seat; original purchase replay; competing admissions; transfer versus
  admission; resolving an interrupted unknown operation versus a late admission.
- New eight authenticated API operations plus global contract inventory: 9/35.
  Shared generated SDK includes the new paths and strict cents/identity DTOs.
- Vendor panel typed and QR admissions at390/820/1280px: three browser cases.
  Original ledger entry counts stayed unchanged by admission, original replay
  reported one outcome and revoked assignment was refused. Finance's capacity,
  event windows and paused read-only view passed its browser/accessibility case.
- Shared wallet package: 103 tests passed, including ticket views for Learner,
  Parent, Teacher and Facilitator, authority changes, original receiving/transfer
  confirmation, loss/recovery and opaque UUID persistence. Dedicated Vendor ticket
  repository/scanner confirmation: four tests passed.
- Money and panel PHPStan, fresh Pint, OpenAPI lint, e2e TypeScript and the
  documentation production build passed. The generated SDK repeat preserved all
  2,759 files byte for byte (2,758 Dart files plus its file inventory); all182 SDK
  tests passed. SDK analysis reported generated naming-style infos only.
- Compiled actual Learner, Parent, Teacher and Facilitator journeys passed: storefront
  entry, private ticket/receiving code, recipient review, one transfer, unchanged
  wallet balances and clearing after revoked access. Compiled dedicated Vendor
  typed and QR journeys passed one actual admission and revoked-session clearing.
  Native QR input uses a controlled scanner fixture; real camera/device checks
  remain external evidence.
- Empty up/down/up and populated23-migration rehearsal passed. Original ledger,
  wallet and merchant financial rows stayed byte for byte identical on upgrade;
  original immutable rows survived purchase, and all financial rows stayed identical
  through transfer/admission including original-operation replay. Global ledger and
  ticket reconciliation passed and retained rollback was refused.

New UI keys retain all existing translations and use approved af/zu glossary
entries where available, with explicit English fallbacks pending fluent review.
No payment provider, bank execution, external message or production release is
activated. Hosted exact-source CI/review/main integration, real camera/device and
staging acceptance remain separate evidence. These do not freeze independent
payer-correction or online-spending development.

The compiled Vendor harness scrolls back from settlement history and waits for the
admission input to mount before typing. Failed integration runs now retain their
response details. The cash-up navigation entry explicitly declares `module: null`,
retaining cross-domain cash controls during individual module pause while satisfying
the navigation registry contract. These are qualification fixes, not new authority.

Review follow-up: transfer preview refreshes the selected ticket page and verifies
that the original selected ticket/revision is still present before enabling a
transfer intent. A page-two transfer succeeds; a ticket moved off-page starts no
pending intent. Read-authorised historical learner owners retain their learner ID
after Student membership ends, while purchase/control authority remains refused.
The full wallet package passed105 tests with no analyzer issues; two focused
retained learner/history tests passed8 assertions. No API/SDK shape changes.

Live-authority review follow-up: Commerce requires the host to bind every pushed
private admission route to its live session boundary. Learner/Parent/Teacher use
the current pinned context; Facilitator uses its revocable authority epoch. School,
role, guardian-child and ticket-module changes immediately dispose cached ticket
credentials without waiting for an API call or app suspension. Four pushed-route
regressions and all109 shared wallet tests passed. Both actual apps analyzed with
no errors/warnings (existing style infos remain). Compiled actual Learner, Parent, Teacher and Facilitator profile requalification
all passed on the live-authority fix.
