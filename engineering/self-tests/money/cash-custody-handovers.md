# Cash custody handovers and payout availability — increment10B

Implementation extends daily cash-up10A with two immutable school-owned tables,
private Finance proposal/outcome screens and integer custody availability. A
recipient independently accepts actual cash; pending outgoing proposals reserve
availability, original UUID/body/actor receipts replay once and conflicting final
outcomes are refused. No handover posts a ledger entry or adjusts a physical count.

Fee refunds, adult wallet cash payouts and vendor settlements retain all original
payment-domain authority, original holder/merchant, asset funding and payout
identity checks. They additionally require the actual paying operator's available
custody. Recorded shortages reduce usable cash; surpluses create no funds.
Backdated payouts cannot borrow later receipts. Cash-up authority grants no refund,
withdrawal or merchant payout permission. Private handover screens show only the
signed-in sender's/recipient's rows and freshly eligible recipient choices.

Local backend qualification:

- Integrated cash-up, custody, fee refunds, wallet withdrawals and merchant
  settlements:56 tests /451 assertions passed.
- Final custody, independent-process races and school-owned retention inventory:
  16/1325 passed. Races cover competing reservations, original proposal replay,
  simultaneous confirmation replay, acceptance versus cancellation, and a real
  adult cash payout competing with a handover for the same operator's cash.
- Three actual financial payout journeys passed3/29 after extending them to first
  refuse a different unfunded cashier, then independently hand over real cash and
  complete the original fee/wallet/vendor payout from the receiving operator.
  Original receipts, payment identity, cash operator attribution and projections
  remain correct; fee payout replay remains once.
- Wrong-role, wrong-school (including a properly authorised second-school user),
  wrong-owner, ended membership, request/body collisions, encrypted evidence,
  immutable SQL history, retained rollback, shortage/surplus behavior, historical
  custody and private school/account form binding are covered.

`cash:reconcile` is scheduled after the nightly ledger check. Tests prove the
command succeeds for reconciled source/custody history and fails for a corrupted
cash projection. Missing/stale counts and explained physical shortages remain
separate review fields, with no fabricated adjustment or unrelated module freeze.

Four responsive browser cases passed without retries at390/820/1280px plus
Teacher refusal. They prove original proposal replay, zero usable recipient cash
before acceptance, original recipient confirmation/replay, unchanged school cash
and ledger entry counts, correct sender/recipient custody, accessibility, overflow,
duplicate IDs and no browser errors. The initial Teacher write test omitted CSRF;
adding the real session token proved403 from role checks and all four cases passed.
The shared browser inventory includes this new flow.

Final private-panel/language/design checks passed5/19566; the focused SQL actor
trigger test passed1/7, refusing a sender attempting recipient confirmation before
the legitimate outcome. Fresh money PHPStan level5 and configured panel analysis,
fresh Pint on22 PHP files, e2e TypeScript and documentation production build passed.
Twenty-seven new panel labels use approved glossary terms where available and
explicit English fallbacks pending language review.

Empty up/down/up and populated22-migration rehearsals passed. Actual fee/wallet
ledger rows, outside receipt/payout identities and an existing immutable physical
count stayed byte for byte identical through upgrade and handover confirmation.
Original proposal/outcome UUIDs replayed once, custody became800/1200 cents while
the school's actual cash remained2000, both ledger/cash reconciliation passed,
and retained custody rollback was refused. No API,
SDK or native app contract changes are introduced by10B. Exact-source hosted CI,
review, main integration and external physical/staging evidence remain separate.
Gateways and production activation remain deferred.

Review follow-up: backdated payouts validate the minimum custody on every affected
source/count date through today, including intervening confirmed handovers and
known shortages. Current pending proposals are reserved against current funds;
they are not falsely subtracted from earlier dates before those proposals existed.
Full-interval regressions and existing cash controls passed21 tests /155
assertions, with fresh money PHPStan and Pint checks before publication. No original financial or custody history is rewritten.

Historical-receipt review follow-up: available custody now rebases the latest
physical counted amount against current source history through that count date.
A late-entered historical receipt cannot appear as cash delivered after the count.
The same calculation applies at every count during a backdated payout interval.
Original immutable counts and their recorded variance remain unchanged. Thirteen
cash-custody tests passed90 assertions, including historical receipt/payout bounds,
subsequent actual receipts and pending handover acceptance refusal.
