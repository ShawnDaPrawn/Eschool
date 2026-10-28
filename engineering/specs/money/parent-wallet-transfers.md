# Parent wallet transfers and interrupted request recovery

Increment 8C1 moves available money between a verified parent's own wallet and a
currently approved child's wallet. A current learner cannot initiate a transfer.
Fresh school, person, role, guardian and module authority apply inside the common
school lock and before replay. Fee access is independent. Cross-school transfers,
third-party recipients, sibling-to-sibling moves and unsupported amounts are refused.

Parent-to-child transfers can proceed without a child-return policy. The school
explicitly chooses whether any currently approving guardian may return available
child money to that guardian's own wallet. The policy acknowledges the effect on
separated families and applies regardless of who originally funded the child wallet.
It is never enabled by default. Wallet Finance plus separate policy-management
permission is required to change it. A policy change cannot edit posted transfers.

Immutable transfer roots retain actor, operation, both personal wallets and owners,
integer cents, currency, exact entry, direction and policy revision. SQL requires
exactly the declared two postings and operation source. Reconciliation detects
unattributed or mismatched movements and prevents projection repair from bad history.
Cached balances must agree before posting. Retained policy/transfer history refuses
rollback. Receipts expose own-actor historical movements without another holder's
current balance or private school bank evidence.

Before a money request, the apps durably retain only its opaque UUID in a separate
school/actor/operation key. Another money intent is blocked until unknown outcomes
are resolved. App restart, refresh or access loss retains the pending key. The server
resolves under the same school lock: an existing operation returns committed; an
uncommitted key is permanently closed with an immutable abandonment receipt. A late
original then conflicts and cannot move money after an abandoned outcome. A failed
recovery keeps the same key. Storage failure prevents sending the money request.
No amount, child identifier, bank detail or private evidence is stored in this queue.

Family wallets provide reviewed transfers, policy-aware child returns, receipts and
recovery. Teacher, learner and dedicated facilitator wallets retain their own scope
and history without parent transfer controls. Parent deposits and their Finance
verification remain 8C2; this feature does not finish increment 8. Gateways remain
deferred, and no outside payment is executed by a transfer.
