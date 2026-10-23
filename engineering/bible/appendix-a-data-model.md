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
