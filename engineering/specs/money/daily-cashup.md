# Daily cash-up and custody

Increment10 derives operator cash custody from actual posted cash fee receipts,
school wallet funding, independently verified parent deposits, fee refunds, adult
wallet payouts and vendor settlements. Internal wallet grants, transfers and
purchases are not physical cash. Every included root must match its original cash
asset posting. Unknown, duplicate or projection-different cash history is reported;
it cannot authorize a count closure or a payout.

10A provides private Finance daily/operator/currency reports, actual counted cash,
immutable revisioned count records, stale-source refusal, retained explanations
for differences and corrections, and CSV export. Counts never post a ledger
adjustment or invent opening cash. Opening custody carries actual earlier cash
forward. Late recorded receipts/payouts invalidate the old report digest and are
shown for review without rewriting the original count.

10B adds explicit, recipient-confirmed custody handovers and payout checks against
the actual operator's available custody. No outside bank execution is included.
The distinct money-cashup-manage permission grants Finance access; fresh active
school membership/permission checks exclude learners, Vendor sessions and platform
impersonation. Read-only financial modules retain cash control history and counts;
no paused module is reopened to move money.

## Confirmed custody and payout control (10B)

Only the sender may propose a handover from their own recorded cash. Both people
need fresh cash-up authority in the same active school. The proposal reserves
available custody; the recipient independently confirms actual cash received.
Sender cancellation or recipient rejection requires confirmation that no cash
changed hands. Final outcomes and original encrypted evidence are immutable.
Finish physical handovers before end-of-day counting. If a count finds a shortage,
review the actual cash and pending proposals before accepting a handover; never
claim a return or a cancellation unless the physical cash was actually returned.
No administrator can silently reassign another person's cash or invent opening
custody. Changes to staff access remain an explicit school management decision.

School locking serializes handover proposals/outcomes with existing fee, wallet
and vendor payout operations. Pending outgoing amounts reduce availability; a
recorded shortage caps usable custody and a surplus cannot create usable funds.
Fee refunds, adult wallet cash withdrawals and independent vendor cash settlements
require the paying actor's own available custody as well as the existing funded,
reconciled payout asset. Backdated wallet/vendor payouts also require custody on
the recorded actual payout date. EFT and internal sales recognition keep their
existing independent rules. Cash payout permission remains the affected financial
domain's permission; handover access never grants refund/withdrawal/settlement
permission. Exact original payout replays return the original outcome before
re-evaluating current available cash, with fresh role/module checks as before.

Accepted handovers add equal opposite custody events, no ledger entry. Existing
cash-up digests keep the same original-event representation; handovers correctly
mark affected counts for review. The private panel lists only handovers belonging
to the signed-in sender/recipient, requires school/account-bound forms, does not
flash private evidence, and remains usable when financial modules are paused.

`cash:reconcile` runs nightly after the ledger check. It verifies original cash
postings/projections, custody totals and negative custodians, and logs critical
financial failures. Missing/stale physical counts and recorded shortages are
separate human-review fields, not fabricated ledger adjustments or a blanket
freeze on unrelated work. No payment provider, outside transfer or Phase6
collection is activated.
