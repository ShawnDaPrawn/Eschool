# Retained cash-count source boundaries

A same-day payout used to reduce the report used to recompute an earlier shortage.
For book cash R100, a physical R80 count and an R80 payout, that incorrectly made
R20 available again. The corrected bound preserves the observed loss and leaves
zero available. Physical counts remain evidence, never ledger adjustments.

Migration27 adds immutable school-owned count-source fingerprints and eight final
source-ID watermarks. Counts capture their boundary atomically under the school
lock. Cash reports preserve their existing event shape and source hash; sequence
metadata stays internal. Handovers use the final confirmation outcome sequence,
so a proposal created before counting but accepted afterwards is still later cash.
No timestamp ordering assumption is needed, including equal/frozen timestamps.

Later payouts cannot erase a shortage. Confirmed incoming handovers add actual
received cash. Genuinely prior-date corrections update the count's comparison
basis. A later cash receipt dated on the count day has no actual receipt time:
its amount stays within the observed cash bound until a fresh physical revision.
Surpluses never create funds; confirmed receipt evidence can justify already
counted cash only within the actual count. Backdated payouts retain the minimum
available-custody check across the affected dates.

Pre-upgrade counts are adopted only when the exact original HMAC source set can
still be reconstructed. Reads never retain new rows. A cash outflow retains proof
under the school lock before moving cash. Changed, unprovable historical source
sets require a fresh physical revision; original counts and money remain intact.
The affected handover screen states that action. Other school work continues.

Qualification on the corrected source:

- Cash custody, cash-up and school-owned access inventory:34 tests /1,458 assertions.
- Separate-process handover/second-proposal races:5 tests /57 assertions.
- Existing fee refunds, adult withdrawals and vendor settlements:37 tests /312 assertions.
- Eight responsive browser cases passed:Finance count/revision and independently
  confirmed handover at390/820/1280px, plus Teacher refusals for both surfaces.
- Panel language/navigation/design checks:18 tests /63,816 assertions.
- Both configured money/panel PHPStan analyses passed; changed PHP files passed Pint.
- Empty migration up/down/up and populated upgrade passed. Old count and financial
  fields remained byte identical, read-only adoption wrote nothing, proof was
  retained before payment, the original shortage survived a same-day handover,
  and sender/recipient bounds were correct. Global ledger/domain reconciliation
  remained clean; populated rollback was refused.

Regression tests also cover repeated actual adult cash payouts, a late-recorded
payout, same-day receipts and fresh revisions, old pending proposal acceptance,
changed old fingerprints, wrong-school source access, SQL update/delete refusal
and retained rollback. No financial history is removed or rewritten.

Published integration is tracked on [PR346](https://github.com/ShawnDaPrawn/Eschool/pull/346).
PR345 contains the older unsafe calculation and must not merge independently.
Exact-source hosted CI/review, main integration and external cash-process/staging
acceptance remain separate evidence. No payment gateway is introduced.
