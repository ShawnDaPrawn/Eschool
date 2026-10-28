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
- Money and panel PHPStan passed. Final formatting, contract/SDK repeat, upgraded
  migration rehearsal and compiled client checks are still being completed.

New UI keys retain all existing translations and use approved af/zu glossary
entries where available, with explicit English fallbacks pending fluent review.
No payment provider, bank execution, external message or production release is
activated. Hosted exact-source CI/review/main integration, real camera/device and
staging acceptance remain separate evidence. These do not freeze independent
payer-correction or online-spending development.
