# Phase 3 handovers into the money foundation

Base: merged main `990ea2a7bb22ca90091ad7bb32fd35b467831474`.
Scope: three joined backend journeys and the explicit
[admissions/receipt cutover contract](../specs/money/admissions-receipts-cutover.md).
Application, ledger, migration, client, API contract and CI source are unchanged.

The tests use real admissions enrolment, guardian invitation acceptance and
approval services, membership dates, transfer signing/archive/acceptance and the
existing reconciled leadership receipt adapter, with synthetic identities and
the isolated local MySQL database.

- Admission creates the child and guardian without approval. Invitation acceptance
  verifies the guardian and completes their password setup, but still grants no
  learner approval. Explicit approval preserves a future membership start date.
  At that date, the learner has school membership; another school is refused.
  Withdrawal revokes learner tokens, and replaying the exact enrolment operation
  neither restores approval nor duplicates the invitation or occupied place.
- An admitted, explicitly approved learner enters the real consented transfer
  pipeline. A previously loaded guardian cannot consent while their membership
  is suspended. Acceptance and exact replay preserve one receiving identity and
  source closure. Future receiving membership starts on its date; receiving
  approval remains pending and fee visibility is not copied. A fresh learner
  token is refused in that school until its own approval exists.
- An office acknowledgement retries exactly once and refuses a revoked admin
  or foreign-school replay. With no legacy receipt, the leadership collection is
  zero despite the acknowledgement. One settled, reconciled 5,000-cent receipt
  then projects exactly 5,000 cents on repeated reads; the 12,500-cent office
  acknowledgement and running balance are not added to it.

Executed: **35 Admissions tests / 270 assertions passed**, including the three
new joined cases. PHP 8.3.36 ran directly, without Docker or a UI compiler. Selected
PHP syntax, Pint, whitespace and staged secret scanning pass. The initial small
run found one incorrect test expectation: a foreign application is hidden with
404 rather than 403. Only that expectation changed; no production guard changed.

Evidence:

- `/workspace/scratch/p3-handover-admissions.log`
- `/workspace/scratch/p3-handover-pint-final.log`
- `/workspace/scratch/p3-handover-gitleaks.log`

The cutover document forbids automatic historical reposting and specifies source
identity, reviewed reconciliation and one authoritative receipt representation
before any future ledger projection is enabled. No ledger importer, opening
balance, gateway, real payment, historical repost, provider activation or
production migration was executed or qualified by this increment.
