# Money preparation: scoped source review

Reviewed preparation source against the Phase 3/4 main baseline `990ea2a7b`.
This is a manual diff review with automated regression evidence, not an
independent financial security test or a completed formal Codex Security scan.
The optional plugin's referenced configuration preflight resource and scan
helpers were unavailable in this environment. No formal report or scan identity
is claimed. The repository's [system threat model](threat-model.md) supplies the
existing actor/assets/trust boundaries; this note adds only the internal ledger
and changed retry/navigation behavior.

| Threat/boundary | Review and evidence |
| --- | --- |
| Cross-school posting or balance access | Every kernel query has an explicit current school. Composite school/entry/account foreign keys reject mixed-school posting rows. Tests refuse foreign accounts, owners and unset school context; keys are separately unique per school. |
| Counterfeit/duplicated money | Balanced integer cents, one currency, positive posting amounts, canonical request hash, permanent key and source uniqueness. New-key replay of the same source conflicts; exact key replay returns one sealed entry. |
| Concurrent overspend or stale transaction snapshot | Same-school write serialization, locked account reads and current history/module/package reads inside outer transactions. Independent-process spend/replay tests and four separate-connection stale-snapshot regressions exercise these paths. Cached balances never authorize extra spending. |
| History mutation or invalid finalization | Database finalization trigger checks the cross-row sum and count; immutable postings/entries, currency/sealing trigger and account-identity guard. Direct SQL negative tests include a case-only description edit. Drafts cannot be mistaken for posted money; orphaned drafts fail reconciliation. |
| Withdrawn entitlement or suspended school | Fresh locked school/package/module state refuses new effects. Exact prior receipt lookup remains distinct from a new write. Disabled/removed wallet modules preserve balances and history read-only, including a zero balance with postings. |
| Actor/owner/approval misuse in future integrations | This kernel has no public posting endpoint. Admission fee acknowledgement remains administrative; no historical repost, vendor fallback, card payment, automatic debt/wallet transfer or provider activation is introduced. Domain policies and financial limits are mandatory before a future caller exposes the kernel. |
| Retry storms, skipped parking or timezone-dependent delivery | The direct and periodic dispatch paths share the parked limit. UTC instants/backoff have focused RED→GREEN tests; existing outbox/notifier/channel tests remain green. Unknown provider outcomes need provider evidence and the original intent, not a new financial effect. |
| Debt restricting learner access | Fresh/returning learner and Parent child navigation no longer depend on fees. Approved unpaid API and saved-route tests retain academic access; guardian fee privacy, approval enforcement and school/owner refusal remain independent. |
| CI supply chain/false green | Bounded retries apply to dependency transport/wrapper download; tests are not retried automatically. GitHub ref-scoped Gradle caches hold dependencies, not deployment credentials. Money kernel analysis has level 5 and no suppression baseline. Existing required workflow/Android aggregates remain intact. |

The admitted community journey additionally verifies current approval, group,
card, inbox and queued-delivery authority after enrolment, school switching and
withdrawal. Its absent-mobile input reproduced an enrolment availability failure;
the legacy Parents insert now normalizes null to an empty value without altering
nullable User/contact data or requiring an invented phone number.

No actionable new vulnerability remained from this scoped review after the
snapshot/current-authority correction. This statement covers the inspected
changes and tests, not the future fee, wallet, vendor, gateway or settlement
implementations. Real-provider replay/hosted checkout, cash/statement observation,
physical card/device qualification, deployed alert routing and an independent
money-flow security test remain release evidence before real money activation.
