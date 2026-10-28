# Personal wallets and private statements

Increment7 issues one zero-balance personal ledger wallet per school/person/currency.
Issuing does not create money. Wallets depend on people, independently of fees.
Explicit school settings select currency, maximum daily spend and whether child
controls are required. Currency cannot change once a wallet is issued. A zero
limit stops purchases; configured personal caps cannot exceed the school maximum.
Controls retain ordered revisions and permanent actor-bound operation UUIDs.

Parent, learner, teacher, facilitator and school-admin app profiles read their own
wallets. A verified current guardian with approval authority may issue/read/control
a linked child's wallet independently of fee visibility. Learners cannot raise
controls. Inactive children retain guardian-readable history. Fresh membership,
role, guardian, school, approval and module checks precede cached write replay.
Finance requires the separate wallet-finance-manage permission. Read-only module
history survives disabling, without permitting issuance or changed controls.

Additive v2 wallet index/open/statement/control routes use integer cents and private
responses. Statements calculate running balances before pagination and withhold
counterparty evidence. School-scoped SQL identities, operation receipts and ledger
history cannot be reassigned or deleted. Migration rollback refuses retained data.
The shared wallet package pins school, actor and token, validates raw integer cents,
keeps retry UUIDs, and clears private state on revoked access or context changes.
Both existing apps and the dedicated facilitator shell expose scoped wallets.

Funding, transfers, withdrawals and purchase enforcement follow in8–15. Category
slugs currently restrict future purchases; replace their editor with canonical
vendor category choices in11. No gateway, external payment or production release
is activated. Native/staging, language and independent financial acceptance remain
separate qualification records.
