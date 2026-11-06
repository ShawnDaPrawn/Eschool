# Independent school business tools

Action Plan 5.10. Server side only: services, database rules and the API v2
routes. App and panel screens follow in a later step.

Three switchable modules, each off by default:

| Module | Product module | Depends on | Permissions |
| --- | --- | --- | --- |
| `reenrolment` Re-enrolment contracts | Access and admissions | people | manage: `reenrolment-manage` |
| `bursaries` Bursary applications | Finance | people, fees | ability `bursary_committee`: `bursaries-review` (sensitive) |
| `giving` Alumni giving (money module) | Finance | people | manage: `giving-campaigns-manage`; ability `giving_donors`: `giving-donors-manage` (sensitive) |

Every service checks, with fresh rows (locking reads on writes, after the
school lock): a selected, active school that is not across schools or
impersonated; no platform staff identity; no pending password change; the
module on (writable for writes); and either a current staff membership whose
role (or a direct grant) holds the permission, or a current Parent
membership with a verified email for writes and a current right to act for
the child (an active approving link, or a legacy parent with no link). The
child must be a current learner enrolled in the current school year today.
Every write takes an `operation_id`: an exact retry returns the first
receipt, a different request under the same id is 409. The operation table
stores the id, a keyed request hash and a receipt of identifiers only.

## Re-enrolment contracts

- Staff publish a contract for a school year that starts after the current
  one ends: title, body, an acceptance rule and a reply date. Each publish is
  a new version for that year; the open version is closed as superseded. The
  database refuses any change to a published version's wording, hash, rule or
  year, and at most one version per year is open.
- The text hash is SHA-256 of `title + "\n\n" + body`. A guardian accepts by
  sending the contract id, version and text hash they read, their account
  name and an explicit acknowledgement. The acceptance keeps the guardian,
  the instant (UTC), the version, the text hash, whether they were
  fee-responsible, the attested name and the request IP. Acceptances cannot be
  changed or deleted, and the database refuses an acceptance whose version or
  hash is not the open contract's.
- **Fee-responsible guardians** are the payers on the child's family fee
  account for the contract year, else for the current year; with no family
  account, the guardians whose active link carries fee visibility.
- **Rules.** `all_fee_payers`: every fee-responsible guardian has accepted.
  `fee_payer`: one has. `any_guardian`: any approving guardian has. Under a fee
  rule another guardian cannot accept alone: they may add their acceptance only
  after a fee-responsible guardian accepted (409 otherwise).
- When the rule is met the child is **re-enrolment confirmed** for that
  version (one row, kept). A new version needs new acceptances.
- Staff see outstanding learners (current learners without a confirmation)
  with the fee-responsible guardians the school still waits for. Reminders go
  through the existing notifier as `reenrolment.requested` (notice only, no
  child name): fee-responsible guardians first; under `any_guardian`, other
  approving guardians only when no fee-responsible guardian can act. At most
  one notice per guardian, contract and school day. Only outstanding learners
  are considered and one request sends at most `reminder_batch` notices;
  guardians already reminded today are skipped, so repeating the request moves
  on through the rest of the school, and `more` says whether anyone is still
  waiting for today's reminder.
- **Year-end rollover.** `GradePromotion::plan()` reads
  `ReenrolmentContracts::statuses()` and adds `reenrolment` (confirmed or
  awaiting) to each learner and a count to the plan. It is not part of the
  fingerprint, not an outcome and changes nothing about promotions; the page
  shows it as information.

## Bursary applications

- An approving guardian starts an application for a child for this school
  year or next: household size, dependants, monthly income and expenses in
  integer cents, other support and an explanation. One open application per
  child and year. Supporting documents (PDF or photo) are scanned, photos are
  re-encoded and scanned again, and bytes are stored encrypted on the private
  `business_private` disk under the school's folder; a failed scan stores
  nothing and an unavailable scanner is 503.
- **Visibility.** Only the applying guardian, while they may still act for
  the child, and staff holding `bursaries-review`. The child's other guardian
  and every other family get 404. Household details and committee reasons are
  encrypted at rest and never enter audit rows, notices or operation receipts.
- The committee adds notes and decides (award or decline) with reasons; every
  step is an append-only event. A committee member who is a guardian of the
  child cannot note or decide.
- **Outcome.** An award is integer cents (`fixed`) or basis points
  (`percent`, at most 10000). For the current school year it becomes a draft
  `bursary` fee discount for the child through `FeeDiscounts::draftBursary`
  (the same checks as a Finance draft), always requiring a different Finance
  approver. Finance then approves it and applies it with the existing discount
  review, credit notes and statements. An award for next year waits; once that
  year is current the committee drafts its discount. The fees module must be
  writable (423 otherwise).
- The guardian gets a generic `bursary.updated` notice; they see the outcome
  and award but not the committee's reasons.
- **Retention.** `bursaries:prune` (daily, every school, module on or off)
  erases household details, committee reasons and documents
  `bursary_keep_days` (730) after a decision or withdrawal and
  `bursary_draft_days` (90) after an unsent draft last changed. An erased
  draft becomes `expired`: it cannot be changed, sent or withdrawn, it never
  reopens, and the guardian can start a new application for the child and
  year. The row keeps its state, award and fee discount; the database refuses
  restoring erased details, reopening an expired or withdrawn application and
  deleting the row.

## Alumni giving

- A campaign has a currency, optional goal, dates and a `public_totals`
  choice, and its own `giving_income` ledger account. Totals are the ledger
  balance, never a cached sum.
- A **pledge** is a promise with a `P-000001` reference; it posts nothing.
- An **EFT donation** is a bank receipt the school has seen: it posts debit
  `giving_bank`, credit the campaign, in integer cents, and gets a `D-000001`
  receipt. The bank reference is normalised and registered in
  `financial_receipts` with every other outside receipt, so one bank line is
  never recorded twice (as fees and as a gift). The database checks the exact
  two postings and refuses edits and deletes.
- Donor names and email addresses are encrypted and readable only with
  `giving-donors-manage`. Campaign managers see totals; when a campaign has
  public totals any current member of the school sees its name, goal, amount
  raised and number of gifts.
- **No gateway.** Donations are `method: eft`, `provider: manual`,
  `collected_by: school` (database check). An online gateway is a separate
  provider through the existing clearing and due-to-school seam.
- Switching giving off keeps retained history readable, never writable.

## API

`/api/v2/reenrolment/*`, `/api/v2/bursaries/*`, `/api/v2/giving/*`, in
`openapi.yaml` under the Reenrolment, Bursaries and Giving tags.

## Tests

`tests/Feature/SchoolBusiness/ReenrolmentContractsTest.php`,
`BursaryApplicationsTest.php`, `AlumniGivingTest.php`: happy paths, wrong
role, wrong school, wrong guardian (another family and the co-guardian),
fee-responsible rule, idempotent retries, integer cents, retention, database
immutability and the API contract.
