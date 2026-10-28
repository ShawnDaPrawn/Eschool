# Appendix A: data model reference

## Phase5: verified parent wallet deposits

`wallet_deposit_policies` explicitly enables/pauses parent cash/EFT independently
of school master funding. Immutable `wallet_deposit_claims` binds original Parent,
personal wallet, requested cents, date/reference and encrypted proof.
`wallet_parent_deposits` records independently verified actual cents, exact entry,
collector/net/settlement, cash custodian and encrypted evidence.
`wallet_deposit_actions` permits one verified/rejected/cancelled outcome with
encrypted rejection notes. `financial_receipts` adds the parent domain to prevent
booking one incoming source through multiple modules.

The tables and columns NavigateEd has added to the seed's schema, phase by phase. It holds only what exists in the code; tables still planned are tracked in the private Build Bible (Appendix A) and are added here when a phase builds them. Engineers extend this page at the end of each phase.

## Phase 5: fee-plan configuration

Phase 5's first billing increment adds school-owned `fee_plans` (school year,
annual integer cents/currency, all/grade/class scope, draft/active/archived state,
revision and soft deletion) and `fee_plan_operations` (school-unique operation
UUID, actor, request hash and durable reference receipt). Plan configuration
does not create invoices, balances or payment transactions. See
[fee-plan preview](../specs/money/fee-plan-preview.md).

## Phase 5: family accounts and annual invoices

School-owned `fee_accounts` fixes a family reference/currency and its dedicated
receivable. `fee_account_holders` records explicit school-year shares in basis
points; `fee_account_learners` gives a learner one payer account per school year.
`bill_runs` stores immutable preview totals/exceptions and a fingerprint, then
recorded approval/posting. `invoices` finalizes only against matching learner
`invoice_lines` and a posted receivable entry. Sealed headers/lines reject edits,
deletes and appends. Annual learner/year uniqueness prevents rebilling through a
new run. `billing_operations` retains actor-bound UUID/hash receipts permanently.
New `fee_income` ledger accounts are credit-normal and belong only to fees;
invoice posting debits each family's receivable and credits fee income. No
wallet, payment, clearing or due-to-school collection is activated. See
[family billing](../specs/money/family-billing.md).

## Phase 0: what was built

Every table below is additive, with a tested `down()`. The new kernel tables carry a nullable `school_id` that Phase 1 fills when schools arrive; the seed's own tables get theirs in Phase 1.

| Table or column | Key columns | Purpose | Stream |
| --- | --- | --- | --- |
| `notification_deliveries` | school_id, delivery_key, type, channel, recipient_user_id, recipient_hash, status, provider, provider_message_id, attempt, error_summary, queued_at, sent_at, failed_at, feedback_at | One row per mail or push attempt, updated by the SES feedback webhook | S5 |
| `webhook_receipts` | provider, event_id (unique per provider), event_type, received_at | Lets each webhook ignore a repeat of an event it has already handled (SNS `MessageId` first) | S5 |
| `domain_events` | school_id, type, payload, occurred_at, available_at, dispatched_at, attempts, last_error | The outbox: written in the writer's transaction, dispatched every minute by `DispatchOutbox` | S5 |
| `audit_log` | school_id, actor_id, actor_type, action, subject_type, subject_id, before, after, ip, request_id, created_at | Who changed what on `users`, `students`, `exam_marks`, `fees_paids` and `settings`; rows cannot be changed or deleted; passwords, tokens and secret values never stored | S5 |
| `idempotency_keys` | user_id, key, key_hash, method, route, request_hash, response_status, response_headers, response_body, expires_at | Replays the first answer to an API v2 write sent again with the same `Idempotency-Key` | S7 |
| `learner_invitations` | school_id, learner_user_id, guardian_user_id, channel, token_hash, code_hash, expires_at, used_at, revoked_at, created_by | A guardian sets a new learner's password through an e-mailed link or a printed one-time code | S6 |

## Phase 0: seed tables changed

| Table or column | Key columns | Purpose | Stream |
| --- | --- | --- | --- |
| `fees_classes.amount_cents`; `fees_paids.total_amount_cents`, `due_charges_cents`; `fees_choiceables.total_amount_cents`; `paid_installment_fees.amount_cents`, `due_charges_cents`; `payment_transactions.total_amount_cents` | integer cents beside each float column | Money in cents (`Money`, currency from `platform.money.currency`, ZAR), back-filled by `money:backfill-cents`; the float columns stay for v1 until the ledger work in a later phase drops them after reconciliation. `installment_fees.due_charges` and `session_years.fee_due_charges` are percentages and stay as they are | S5 |
| `settings.school_id` | nullable, indexed | Lets Phase 1 scope settings per school without a second migration | S5 |
| `users.password_change_required` | boolean | Flags the date-of-birth passwords of existing learners for a change at the next sign-in | S6 |

Not built in Phase 0, by design: a `currency` column per money row (one currency, ZAR, until the ledger work in a later phase); `school_id` on the seed's tables (Phase 1).

## Phase 1: guardian access

| Table | Purpose |
| --- | --- |
| `guardian_links` | School-scoped learner/guardian relationship, approval and fee visibility rights, active/ended status |
| `learner_approvals` | Versioned consent evidence and withdrawal timestamps; no automatic approval during back-fill |
| `guardian_invitations` | School-scoped one-use invitation, hashed token, encrypted reminder secret, expiry and reminder claims |

All three have required indexed school foreign keys and timestamps. The
migration rolls back before the tenancy migrations in the upgrade rehearsal.

## Phase 1: enrolments and student numbers

| Table or column | Purpose |
| --- | --- |
| `enrolments` | School-scoped dated learner/homeroom/year history, reason, previous entry and legacy-date provenance |
| `learner_identifiers` | Global human-to-number allocation, unique user and student number, first enrolment date; retained after account archiving |
| `platform_counters` | Locked global sequence allocation; numbers are never reused |
| `students.student_number` | Nullable indexed compatibility projection; hidden from v1 model output |

## Phase 1: school module configuration

`school_modules` has a required school foreign key and unique (school, module)
pair, package entitlement, enabled/read-only state, changed-by user and time,
and ordinary timestamps. Model changes are audited. Module invalidations are
recorded in the existing transactional outbox.

## Phase 1: handles

`users.handle` is globally unique and nullable, and hidden from v1 model
serialization. Global `handle_claims` holds active names and expiring
reservations; global `handle_changes` records the human, old/new name,
actor/approving guardian and term boundary. These are accessed through the
authenticated or explicitly authorised human identity. School-scoped
`handle_proposals` links a learner to a seven-day pending name and its eventual
decision. All have timestamps; proposals carry a required school foreign key.

## Phase 2: academic references

| Table or column | Purpose |
| --- | --- |
| `grade_levels` | Global immutable API reference: R to 12, Foundation/Intermediate/Senior/FET phase, unique code and order, timestamps |
| `academic_rating_levels` | Global read-only seven-point reference: level, label and inclusive integer basis-point thresholds (0 to 10000), timestamps |
| `classes.grade_level_id` | Nullable indexed foreign key to the shared grade reference; explicitly mapped and audited within the class's school, hidden from v1 serialization |

These references contain no learner marks or school/person data. Existing
school-owned `grades`, exam results and semester consumers retain their current
behaviour. Mapping is opt-in; no automatic class-name inference or mark conversion.
The migration reverses the foreign key/column before dropping reference tables.

## Phase 2: school terms

`terms` replaces `semesters` in place, preserving IDs, dates, school/year ownership
and academic links. Referencing columns are `term_id`; per-year class allocation
configuration uses `include_terms`. Grade bounds (0 = R through 12) default to the
whole school; optional `marks_lock_on` and `reports_on` prepare future workflows.
`term_templates` is school-owned and holds a named, validated calendar snapshot
and base year for preview/seeding. No semester projection or dual writes remain.

Subject standards add a global immutable public reference catalogue, with framework,
code, phase/grade bounds, source/version/effective date, optional integer component
weights and explicit publication. subjects.standard_id is a nullable restricted
reference, exposed by v2 mapping resources and hidden from v1 raw serialization.

## Phase 2: curriculum and assessments

School-owned soft-deleted curriculum_plans record subject/grade/year versions,
source version, immutable reference snapshot, annual components, absent/rounding
policy and optimistic draft revision. curriculum_topics belong to a term with
stable keys and week ranges. assessment_plans hold per-term components and
publication. assessment_tasks hold stable keys, term and annual components and
basis-point weights, exact hundredth maximum units and optional same-year source
task provenance. assessment_questions hold question numbers, exact maxima and
topic links. Cross-year copies never carry task mark provenance or learner data.

## Phase 2: dated academic allocations

School-owned soft-deleted teaching_groups bind subject/grade/year and an optional
homeroom. teaching_group_memberships retain learner, effective dates, mark rule,
reason and revision. teaching_allocations and homeroom_allocations retain teacher,
context and effective dates. academic_leaderships record grade-head/principal
scope per year with dated appointments and audit history. subject_teachers and
class_teachers have hidden nullable source allocation foreign keys for stable v1
projections. Unmanaged legacy assignments remain independent; group capture
requires canonical authority. Used class grade mappings cannot be reinterpreted.

Mixed groups retain exact learner authority through the new group APIs. They do
not create whole-class v1 subject assignments. Bound homeroom assignments keep
stable v1 projections, and term-filtered teacher/chat consumers explicitly accept
current dated assignments. Enrolment changes end incompatible group memberships
with an audit; moving grade never projects access into the new grade's homeroom.


## Phase 2: exact assessment marks

assessment_marks store one year-specific canonical task/learner score, capture
group provenance, disposition, revision and moderation evidence; question_marks
store exact hundredth units against canonical question IDs. Immutable
mark_snapshots retain definition/input fingerprints, exact formula inputs,
coverage, provisional/official percentages and seven-point ratings per learner /
subject / grade / year / period. mark_releases and mark_release_entries pin
selected immutable snapshots. academic_exam_links identify protected v1 exam
projections. Existing exam marks/results have hidden exact-unit columns; their whole-number obtained_marks fields retain installed-client types.
Official projections use integer scores out of10,000, preserving exact percentages. Canonical projections
have no inferred promotion/pass decision (nullable passing fields).


### Timetable engine

School-owned rooms, bell_schedules, bell_periods and timetable_lessons add
physical room metadata, ordered section/year periods and dated recurring or
specific-date placements. The existing lessons table remains learning content.
Timetable lessons retain revisions and cancellation history; compatible v1
timetables receive protected source links and additive room labels. Foreign
keys retain schedule/room/group/teacher provenance.


Academic registers: attendance_registers bind school/year/date to a homeroom or
lesson; attendance_entries preserve learner state/source/revision and amendment
actor/date/reason. attendance_operations record durable actor-scoped UUID/hash
and replay metadata; attendance_notices record per-recipient revision claims.
attendances gains a private canonical-entry reference, amended_by/amended_at and
source while retaining existing response types. QR versions are private learner
metadata. All new tables have required indexed school ownership.

### Report cards

School-owned report_templates retain versioned section/crest designs;
report_comment_banks hold reusable generic text. report_batches pin selected
learners, period, design, source fingerprint, signatures and release metadata.
report_cards encrypt copied evidence and reference private encrypted PDF artifacts;
report_comments encrypt per-scope text with author/revision. report_recipients
retain notification claims and per-recipient read state. All tables require
indexed school ownership. Released batches/cards/comments reject model mutation;
new versions retain prior releases. Fees and learner activation are not gates.


## Phase 2: private content and local transcription

Nine school-owned tables are added by the private-content migration:
`content_items` (author/context/current draft and publication/revision),
`content_sources` (immutable encrypted private source and checksum/scan result),
`content_versions` (encrypted selection/Markdown, attempt/generation/lease/review,
immutable after publication), `content_item_groups` (published audience),
`content_operations` (actor/UUID/hash/encrypted minimal receipt),
`content_recipients` (version/learner/user read and notification claim),
`content_settings` (transcription switch and school daily seconds), `ai_usage`
(school-local usage date, reservation/used seconds, output hash, zero cents),
`ai_flags` (guard codes only). Private files use the existing encrypted
`academic_private` disk; no public storage URL is introduced. Rollback refuses
populated educational-content/usage history rather than silently dropping it.


### School audiences and private messages

`audiences` saves an encrypted name/contact definition and revision per school.
`communication_role_rules` binds author addressing/approval defaults to the
current school role ID; deleting/recreating a role cannot regrant an old override.
`communication_operations` retains encrypted UUID receipts and body hashes.
`messages` retains encrypted title/Markdown/audience/reasons, revisions, approval,
school-local schedule normalized to UTC, immutable publication identity and
review/delivery recovery cursors. `message_recipients` freezes a deduplicated
user/access-reason snapshot and processing state; `message_reads` stores one
acknowledgement per user/publication. All six use the school scope. Retained rows
prevent rollback. Published bodies/scopes/channels cannot be overwritten.


## Activity group backend

`activity_groups` stores school/year seasonal trees and explicit eligibility.
`activity_group_memberships` and `activity_group_staff` retain dated allocations,
active/cancelled state and encrypted reasons. Staff subtree authority is explicit.
`activity_sessions` and `activity_attendance` separate activity registers from
academic attendance; no missing row is inferred present or absent.
`activity_materials` stores encrypted authored Markdown and publication state.
`activity_operations` retains encrypted UUID/hash receipts. All seven tables carry
school_id with tenant scopes and foreign keys. Audit hashes sensitive text rather
than copying it into audit JSON; immutable encrypted receipts retain revisions.
Destructive rollback refuses any retained activity data. No sensitive counselling
case or medical record is stored in these tables.


## Early warning and owned interventions

`risk_settings` stores versioned academic coverage/threshold and attendance sample
rules. `risk_observations` retains immutable school/year/grade/day/source facts,
nullable scores, encrypted evidence and source fingerprints. `risk_caseloads`
contains independent dated, revocable professional grants. `interventions` pins
the reporting year, grade and original owner authority, with encrypted action and
outcome; `intervention_versions` retains immutable encrypted revision and owner
handoff records. All five are school scoped; populated rollback is refused.
Metadata projections explicitly exclude action/outcome before SQL retrieval.


## School discipline and detention

`discipline_frameworks` and immutable `discipline_framework_versions` retain
explicit year-specific levels, infraction/sanction dictionaries, counters and
reset rules. `discipline_records` pins the dated year/grade, framework revision
and infraction, with encrypted family description, staff note, decision text and
basis. Immutable `discipline_record_versions` retains every reviewed change.
`discipline_acknowledgements` is guardian- and revision-specific.
`detention_sessions` retains school-timezone scheduling and the assigned
supervisor; `detention_entries` keeps explicit unknown/present/absent/excused
states; immutable `detention_versions` audits captures and reasoned corrections.
All eight tables are school scoped and retained rows block destructive rollback.
No permanent learner-profile field or automatic enrolment removal is introduced.
`risk_operations` is the additive private HTTP operation ledger. Actor-scoped
UUIDs and keyed intent hashes protect retry identity; encrypted LONGTEXT receipts
retain metadata only. Its populated rollback is refused independently.

## Intake and admissions

`intakes` pins an existing school year, selected profile definition IDs, a stable
form fingerprint, sensitivity classification, opening/closing UTC instants,
integer-cent fee, requested document categories and bounded retention days.
`intake_grades` holds grade capacities. `applications` encrypts answers/contact,
stores only keyed email lookup and hashed ownership capabilities, and retains
revisioned state, fee acknowledgement and canonical learner reference.
`application_documents` contains opaque private encrypted file metadata and
scanner evidence. `offers` distinguishes an acceptance deadline from an accepted
reservation. `application_links` stores single-use, expiring resume-token hashes.
`application_events` encrypts private review/receipt evidence; global audit records
contain references/revisions only. `admission_operations` stores actor-scoped UUID
intent hashes and encrypted reference receipts. All eight tables are school owned;
populated rollback refuses erasure. Application retention never removes the
resulting learner, guardian link, invitation or canonical educational records.

## Private gallery

`gallery_settings` records the guardian-first school consent rule and revision.
`gallery_photos` holds school/year/source metadata, an encrypted caption and opaque
private ciphertext references. `photo_tags` holds learner-owned consent state,
revision and the explicit transfer-retention marker. `gallery_transfer_choices`
freezes a guardian's encrypted fingerprinted selection against the immutable
academic consent. `gallery_operations` stores encrypted idempotent references;
`gallery_events` holds minimal audit references without image or caption bodies.
All six are school owned. Withdrawn image bytes are erased without deleting the
minimal consent history; rollback refuses populated tables.

## Money preparation: immutable ledger

`ledger_accounts` belongs to a school and fixes type, module, owner (optional
user), reference, currency and normal debit/credit direction. Its integer-cent
balance is a verified projection. Shared cash/collection account types select
their immutable owning financial module at creation; wallet-only cash accounts
require no fees entitlement. `ledger_entries` fixes a school-unique permanent
idempotency key and a separate unique business source, canonical request hash,
description/currency and UTC creation/finalization instants. `ledger_postings`
stores positive integer cents and direction, with composite school/account and
school/entry foreign keys. Entry finalization requires at least two balanced
lines; posted entries and posting rows cannot be edited/deleted. Account identity
cannot be reassigned. Drafts are transaction-internal and any retained incomplete
entry fails reconciliation. Populated rollback refuses historical erasure.

No opening balances are imported. The legacy financial rows and float/cents twins
remain until a later explicit reviewed reconciliation/cutover. Clearing and
due-to-school account types exist but stay at zero until collection is built.

## Phase 5: manual fee receipts and allocations

School-owned `fee_payment_policies` retains a revisioned explicit allocation rule.
Immutable `payments` binds a verified manual cash/EFT receipt, family, beneficiary
holder, optional learner, unique transaction reference, received date and posted
asset/held-credit entry. `payment_allocation_runs` retains a reviewed fingerprint
and sealed batch; immutable `payment_allocations` attributes integer cents to
that holder's exact posted invoice line. Tenant composite keys, amount caps and
finalization triggers protect receipt/line/run relationships. Fee-bank accounts
are debit-normal, fees-only assets; held family/holder credit uses a family
liability without a personal-wallet owner. Allocation reduces credit and receivable,
leaving income unchanged. The shared permanent `billing_operations` protects UUID
replay. See [payments and statements](../specs/money/payments-statements.md).

## Fee credit notes

`fee_credit_notes` records school, family account, immutable invoice line and
original holder, currency, integer amount/unpaid/released cents, fixed reason
category, encrypted explanation, HMAC snapshot, ledger accounts/entry, actor and
issue/seal timestamps. `fee_credit_releases` links a note to original allocation
and payment with positive cents. Composite tenant foreign keys and finalization
triggers enforce original share caps, valid original payments and ledger sides.
Sealed notes/releases are immutable; a populated schema refuses rollback. Existing
allocations remain gross history; effective allocations subtract sealed releases.

## Fee items and one-off charges

`fee_items`: school, public invoice name, learner/family scope, integer price,
currency, explicit no-VAT treatment, state and revision. Scope/currency freeze
once charge history exists. `fee_charges`: school/item/account/year, optional
learner, scope/target, encrypted private occurrence reference with stable digest,
item revision, HMAC snapshot, typed bill run, amount/currency, actor and UTC
creation/seal times. Composite occurrence uniqueness is independent of request
UUIDs and application-key rotation.

Bill runs distinguish annual and item sources. Invoice lines optionally refer
to a fee charge, retaining real learner IDs and null plan references for items.
A generated annual-only learner key preserves the existing annual uniqueness
rule; item/learner uniqueness prevents repeated family lines. Source, reviewed
line/share data, sealed totals and invoice links are checked by database guards.
No financial history is rewritten by the migration. Populated item/charge history
refuses rollback; an empty down/up is rehearsed.


## Phase 5 bank evidence and matching

School-owned `bank_statement_imports` (canonical digest, count, actor, UTC seal),
`bank_statement_lines` (stable source/payload hashes, bank date, direction, cents,
currency, encrypted private details), `bank_statement_import_rows` (immutable
import/line lineage) and `bank_statement_resolutions` (one reviewed match/exclusion,
optional unique EFT payment, encrypted reason, fingerprint, actor and UTC time).
Composite tenant links and immutable SQL guards retain reconciliation history.
Imports do not create financial postings; matching reuses existing receipts.

School-owned `fee_accounting_settings` stores revisioned chart mappings, explicit
organisation currencies/date formats and Xero zero-tax label. It changes export
presentation, never ledger values. Arrears and accounting CSVs derive from sealed
invoices, net holder shares and posted ledger history; no duplicate balance table.

School-owned `fee_reminder_policies` holds Finance's revisioned cadence, minimum
age, school-local hour and explicit enabled flag. `fee_reminders` retains immutable
account/recipient/date/policy intent and expiry with a one-time school/type-checked
notification binding. Notification retention nulls its pointer without forgetting
cadence; no amounts, names or new balance projection are stored in reminders.

Expanded `fee_plans` add typed `applies_to`, relative `payment_options` and an
explicit default. School-owned revisioned `fee_payment_choices` selects an option
per learner/year. Immutable `invoice_lines` gain an installment number/date;
existing posted lines keep their original date fallback. Indexed reviewed learner
and account schedule maps support SQL line and complete-invoice seal validation.

### Fee item VAT

`fee_tax_settings` is school-owned and revisioned with declared registration and
legal seller identity. Items retain rate basis points; immutable charges retain
VAT, generated net and original income/VAT account identities. Invoice holder
shares retain own VAT; credit notes retain reversed VAT and its original account.
The `fee_vat` fees ledger liability is distinct from fee income. Existing no-VAT
history remains unchanged.

### Automatic fee items

`fee_item_eligibility_rules` is school/item-owned and revisioned, with bounded plan
rules and explicit canonical source FKs. `fee_item_batches` retains the original
review, exact sealed charge IDs and total; SQL checks source tenancy, exact child
identity and immutable history. Existing manual financial rows are unchanged.

### Parent fee item approvals

`fee_item_consents` is tenant-owned, append-only and revisioned by item/account/year/
target/actor. It retains active/withdrawn state, permanent UUID, material terms hash
and own frozen consent snapshot. Tenant FKs and SQL guards preserve targets, current
payer insertion and immutable history. Eligibility rules add explicit opt-in billing
frequency. No balance table or financial posting is created by an approval.

### Approved fee reductions

`fee_discounts` stores revisioned dated school rules and explicit amount, source,
overlap, staff-role and independent-approval choices. `fee_discount_runs` retains
immutable reviewed original-holder credit snapshots and encrypted private evidence.
SQL guards require exact sealed linked credit notes, learner/year/currency identity,
valid approval transitions and one posted rule/learner/year. No duplicate balance
projection is created; existing fee credits supply ledger/reconciliation history.

### Enrolment fee proration

`fee_lifecycle_policies` is school/year-owned and revisioned. Explicit join, leaver
and installment choices feed canonical rate/bill previews. Immutable bill snapshots
retain original annual cents, actual enrolment, exact policy/units, charged period
and installment budgets. Existing invoice line/seal checks bind those original
reviewed amounts and periods; no original financial row is rewritten.

### Later leaver credits

`fee_leaver_policies` stores explicit handling for prior policyless ledger invoices,
existing reductions and independent approval. `fee_leaver_runs` retains immutable
reviewed original contract/attendance/payer-credit snapshots and encrypted Finance
evidence. Tenant FKs and SQL guards bind exact sealed original tuition credits,
learner/year/currency, states and approval. Existing invoices remain unchanged;
prior leaver credits are counted separately from permanent own payer reductions.

### Reserved manual fee refunds

`fee_refund_policies` holds explicit school cash/EFT and approval choices. Immutable
`fee_refunds` reviews bind original payment, holder, learner pin, currency, amount,
policy snapshot and encrypted evidence. Append-only `fee_refund_actions` records
reservation, verified payout or cancellation with exact two-sided ledger entries
and unique payout-reference hashes. `fee_refund` is a nonnegative fees liability;
held/paid amounts reduce only the original receipt credit. No receipt, original
invoice or allocation is rewritten.

### Personal wallets

`wallet_settings` stores explicit revisioned school currency, daily maximum and
child-control policy. `wallet_accounts` binds immutable school/person/currency to
one existing wallet ledger account. `wallet_limits` stores revisioned daily/category
controls. Immutable `wallet_operations` retains actor-bound UUID receipts. No new
balance projection or money posting is created by issuance.

### School master funding and external receipt identity

`wallet_funding_policies` retains explicit revisioned manual-method choices.
`wallet_fundings` retains immutable school-owned receipts, exact posted master/asset
accounts, collection metadata and encrypted evidence. `financial_receipts` reserves
one canonical outside source across fee and wallet receipts without reposting old
entries. `wallet_bank` is a wallet-only debit asset. New history prevents rollback.

### Desk wallet grants and approval

`wallet_desk_policies` stores an explicit revisioned threshold. Immutable
`wallet_desk_topups` binds recipient, school-owned master, dedicated reserve, original
policy and exact reservation entry. `wallet_desk_actions` records one released or
cancelled outcome, current policy, independent actor and terminal entry. Private
reasons are encrypted; retained domain history prevents destructive rollback.

### Parent wallet transfers

`wallet_transfer_policies` retains the school's explicit revisioned child-return
choice. Immutable `wallet_transfers` binds actor/operation, both personal wallets
and owners, direction, cents/currency, exact source entry and policy revision.
`wallet_operations` also retains abandoned keys for durable interrupted-request
recovery without changing any earlier operation or posted money.

## Phase5: adult withdrawal reservations

School-owned wallet_withdrawal_policies, wallet_withdrawal_slots and immutable
slot closures configure explicit currency thresholds and capacity. wallet_withdrawals
binds original holder/wallet, pending liability, exact reservation, pickup or
encrypted bank instructions. Append-only wallet_withdrawal_actions records approval,
release or verified payout, actual date and cash custodian. financial_disbursements
shares immutable outside identity across original fee refunds and wallet payouts.
