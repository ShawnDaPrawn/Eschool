# Parent optional fee approvals

Parents see only current own family/learner offers and their own exact payer share
and VAT. Finance explicitly chooses once per school year or per billed occurrence
until withdrawal. No frequency is guessed. Current fee visibility permits reading;
current guardian approval rights and every current payer's approval are required
before an optional charge. Membership alone never supplies that approval.

Approvals are immutable revisioned events for the item, current school year, family
account, target and actor. Material terms include price, tax/legal identity, purpose,
eligibility, affected learners and all payer allocations. A material change needs
fresh approval. No-op saves, date changes and signing-key rotation preserve approved
terms. SQL freezes consent identity, history and the optional approval requirement.

Every change has a durable actor-bound UUID and reviewed fingerprint. Fresh account,
year, guardian, role and module checks run before both normal and cached HTTP replay.
Approval never posts money. Withdrawal stops future approval-dependent billing and
can withdraw an unavailable/archived offer; it cannot edit an invoice or refund it.
Existing FeeCharges performs actual billing, rechecking all current payers under
the school lock. Once-per-year cannot be bypassed with a different occurrence UUID.

The additive v2 API, generated SDK and shared Material 3 parent screen support
explicit review, confirmation, safe same-UUID retries, paging, resume/refresh and
private-data clearing on changed access. Back from the offer screen refreshes fee
accounts before revealing prior financial history. Learners have no approval UI.
English fallbacks are supplied across catalogues; fluent review remains external.

Disabled fees retain read-only offers/history. Other-school, wrong-role, wrong-owner,
stale material terms and revoked guardian rights are refused. Gateways, home Pay now,
production activation and extra spend are outside this increment.
