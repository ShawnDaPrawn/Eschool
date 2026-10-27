# Fee items and one-off charge verification

This increment adds a revisioned additional-item catalogue and reviewed, explicit
learner/family occurrence invoices. It does not choose automatic discount/refund
rules, taxable-item accounting, gateways, payouts or production activation.

Initial local PHP 8.3/MySQL qualification passed **15 tests / 117 assertions**,
including learner/family posting in both orders with annual tuition, durable
replay and occurrence uniqueness, stale reviews, catalogue revisions/archiving,
allocation priorities, learner pins, item credit/reallocation, transaction rollback,
parent contract/privacy/revocation, school/role/actor binding and retained disabled
history. Family allocation property checks passed **2 tests / 1,388 assertions**
over tiny/odd/large amounts and several roster sizes; extra invalid-identity and
incomplete-charge cases are included in the final regression qualification.

A targeted application-key rotation regression failed before duplicate identity
was changed from a key-dependent HMAC to a stable occurrence digest. It then
passed with the 15-case cohort. The private occurrence text remains encrypted,
excluded from parent DTOs, audit/outbox payloads and browser/session persistence.

The parent package passed **28 tests**, retaining older-server compatibility and
checking item labels/family apportionment at 390/820/1280px, unknown future item
categories, credit history and private session boundaries. Pinned SDK generation
completed. Baseline-free money level 5 analysis passed after modelling the typed
bill-run source and loading actual User records for learner names.

Finance browser checks caught an omitted item description in the statement; the
statement now displays the charge name and family apportionment. Final browser,
complete app/backend regression, contracts/SDK and hosted source qualification
are attached to the feature PR. No unrun check is claimed passed here. New labels
have English fallback in all offered catalogues; fluent review, physical devices,
staging/user acceptance, actual cash/bank reconciliation and independent live-money
review remain separate evidence.

Finance browser qualification passed **7 tests**, zero automatic retries: full
catalogue/create/review/confirm/replay/original-invoice/statement flows for learner
and family items at 390/820/1280px, plus Teacher refusal. Every case retained one
item, one occurrence, two total invoices/entries, 3003 receivable/income cents,
both annual learner lines and both inactive synthetic learners; family items
added two learner lines and learner items added one. Nothing posted during review.
Serious/critical axe, horizontal overflow, duplicate IDs, private headers and
console/HTTP errors were checked. Final hosted qualification remains attached
to the PR.

The combined Money/Bootstrap/share-invariant cohort passed **95 tests / 2,197
assertions**, including all 16 item cases and incomplete-charge read/rebuild
refusal. This overlaps the earlier focused cohorts and is not summed with them.
Whole-panel configured static analysis and baseline-free money level 5 passed.
Browser TypeScript and touched PHP formatting also passed.
