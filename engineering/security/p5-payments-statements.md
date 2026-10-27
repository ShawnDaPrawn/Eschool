# Manual fee receipts and statements: scoped source review

Scope is the payment/statement increment against main `4c66f49a3f`, including
staff panel, parent API/app, schema/ledger changes, contracts and tests. The
existing [system threat model](threat-model.md) supplies the actors, assets and
trust boundaries. This is a manual source review supported by automated tests,
not an independent financial penetration test or a completed formal Codex scan.
The Security Diff Scan plugin's referenced preflight resource and scan tools are
unavailable in this environment; no scan identity, sealed report or SARIF is claimed.

| Boundary | Reviewed control/evidence |
| --- | --- |
| Staff write authority | Separate payment permission, fresh active school/user/membership/module checks under the shared school lock, expected actor/school binding, CSRF and private validation without old-input flash. Other finance permissions alone do not grant payments. |
| Parent disclosure | Fresh Parent role and membership, canonical fee-visible links, candidate account ownership and explicit DTO whitelists. Hidden siblings, another holder's identity, raw bank references and ledger IDs are excluded. Mixed receipts involving hidden learners are withheld. Private headers apply to denials as well as success. |
| Duplicate/counterfeit receipt | Positive integer cents, unique canonical cash/bank source and permanent actor-bound UUID/hash receipt. A fresh UUID cannot duplicate the same transaction. Staff explicitly verifies funds and absence from legacy history; this does not claim automatic bank verification. |
| Allocation and concurrent changes | School serialization, fresh policy/credit/share history, HMAC review fingerprint, permanent source and idempotency receipt. Only the named holder's immutable invoice share and optional pinned learner can receive credit. SQL amount caps and composite school keys reject mixed or excess rows. |
| Atomic immutable history | Asset/credit and credit/receivable entries balance. Finalization checks posted documents and row totals; receipts/rows/sealed batches reject edits and deletion. Failure rolls back entries, receipts, audit and outbox. Reconciliation refuses projection repair over incomplete/domain-mismatched history. |
| Private client lifecycle | Private requests avoid financial URL logging. Repository binds school, actor, token and current context before/after reads. Cubit generations discard late results; mounted boundaries remove data after logout/account/school changes; denial clears detail. No response persistence is added. |
| Financial retention | Credit is shown separately from debt. Currency freezes at first receipt and held credit prevents stranding relevant learner assignments. Historic obligations can be paid without restoring guardian visibility or activating learners. Disabled fees retain history read-only. |
| Other surfaces | Blade escaping and bound SQL are retained; generated SDK derives from the additive contract. Internal outbox events contain identifiers/cents only. No webhook, file upload, live provider, notification fanout, wallet withdrawal or production activation is added. |

Source review reproduced a revoked-family disclosure: an unallocated receipt
kept a family summary visible when all its learner links were hidden but the
parent retained access to another learner elsewhere. Receipt ownership no longer
restores that family; a currently visible charge or matched learner/holder
assignment is required before projecting its receipts. The regression failed
before the fix and now covers the service and actual API list/detail, retained
Finance credit and restored guardian access. No actionable finding remains after
this correction. This
statement applies to the reviewed changes and executed regression evidence;
external cash/bank verification, independent live-money testing and production
acceptance remain separate. Legacy receipts and the leadership receipt adapter
are not restated by this increment.
