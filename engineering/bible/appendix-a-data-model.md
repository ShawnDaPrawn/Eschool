# Appendix A: data model reference

The tables and columns NavigateEd has added to the seed's schema, phase by phase. It holds only what exists in the code; tables still planned are tracked in the private Build Bible (Appendix A) and are added here when a phase builds them. Engineers extend this page at the end of each phase.

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
