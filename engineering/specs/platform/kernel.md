# Kernel scaffolding: outbox, audit log, money in cents, settings.school_id (Phase 0, feature 0.6)

Phase 0, feature 0.6. Owner role: Backend (work stream S5). Source: Build Bible section 6, build step 6; section 3.1 (Outbox, Audit, Ledger, Settings); section 1 (the money principle); section 5.4 (data rules). Queued delivery and mail are in [queue-and-delivery](queue-and-delivery.md).

No user-visible change. Underneath: domain events can be written in the same transaction as a change and dispatched once by the worker; people, marks, fee payments and settings are audited; every fee amount has an exact integer-cents twin that the code reads; settings can be scoped per school in Phase 1 without another migration.

## User stories

- **School admin / finance:** fee totals, receipts and reports show the same amounts as before (exactly, where the float column had garbled cents above six significant digits).
- **SaaS admin / auditor:** I can see who created, changed or deleted a user, learner, mark, fee payment or setting, when, from which address and request, with the values before and after, and never a password, token or secret value.
- **Engineer:** I record a domain event with `Outbox::record()` inside my transaction and register listeners in `config/platform.php`; I add `Auditable` to a model; I compute money with `Money` and store it in `*_cents` columns cast with `MoneyCast`.
- **Parent, learner, teacher:** the apps receive the same fee fields and types as before.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| A transaction that records an event and then fails | it rolls back | no event remains | `OutboxTest::test_a_rolled_back_transaction_leaves_no_event` |
| No open transaction (the RefreshDatabase wrapper does not count) | `Outbox::record()` is called | `LogicException` | `test_recording_outside_a_transaction_is_refused` |
| Two undispatched events | `DispatchOutbox` runs twice | each listener call happens once; both marked dispatched | `test_each_event_goes_to_its_listeners_once` |
| A listener that throws once | the dispatcher runs | its writes roll back, `attempts` 1, `last_error`, `available_at` in the future, `outbox_listener_failed` logged; after the backoff it is dispatched | `test_a_failing_listener_is_recorded_rolled_back_and_retried_after_its_backoff` |
| A listener that keeps failing | `max_attempts` is reached | the event is parked and logged | `test_an_event_that_keeps_failing_is_parked` |
| The schedule | inspected; `php artisan outbox:dispatch` | every minute; dispatches now | `test_the_dispatcher_is_scheduled_every_minute_and_runs_on_demand` |
| A user created, updated, soft-deleted, restored, force-deleted | — | rows `created`, `updated` (changed attributes only), `deleted`, `restored`, `force_deleted` with actor; no password, remember token or device token anywhere | `AuditLogTest::test_users_are_audited_without_passwords_or_tokens` |
| A learner, a mark, a fee payment | created, updated, deleted | audited | `test_learners_are_audited`, `test_marks_are_audited`, `test_fee_payments_are_audited` |
| A setting, including `mail_password` | created, updated, deleted | audited; the secret's value is `[redacted]` | `test_settings_are_audited_and_secret_values_are_not_kept` |
| An explicit action in a request | `Audit::record('settings.mail_verified', ...)` | actor, ip, request id (from `X-Request-Id` / S4's `AssignRequestId`) recorded | `test_explicit_actions_record_the_actor_address_and_request` |
| Only hidden attributes change | saved | no audit row | `test_an_update_of_hidden_attributes_only_writes_nothing` |
| An audit row | updated or deleted through the model | refused | `test_audit_rows_cannot_be_changed_or_deleted` |
| Decimal strings, arithmetic, percentages, splits | `Money` | integer cents, half up, same currency only, no floats in or out | `MoneyTest` (first four tests) |
| A fee row written through the model (float, string, Money or cents) | saved | float and cents columns equal; a raw-inserted row gets its cents on the next save | `test_writes_keep_the_float_and_cents_columns_equal` |
| A fee model serialised for v1 | `toArray()` | the same keys as before (cents hidden), amounts read from the cents | `test_the_v1_payload_is_unchanged_and_derived_from_the_cents`, `ApiContractTest` snapshots unchanged |
| Float rows before the cents migration | it runs | each float rounded half up from its exact decimal (0.125 → 13, 2.675 → 268); per table the float and cents totals agree; re-running changes nothing | `KernelMigrationsTest::test_the_back_fill_gives_the_same_totals_as_the_floats` |
| Every kernel migration | `down()` then `up()` | tables and columns go and come back | `test_every_kernel_migration_can_be_rolled_back_and_run_again` |
| `settings` | after the migration | `school_id` null on every row; `getSettings()` unchanged | `test_settings_school_id_is_nullable_and_get_settings_still_works` |
| Installments of 1000.01 in thirds with 5 % due charges; a 7 % compulsory charge; an optional fee | priced and paid | the same totals the float code charged (683.34, 1070.01), rows in cents, 2-cent slack against the app's amount | `FeeTotalsInCentsTest`, `PaymentWebhookTest` |

## Data changes

| Table | Change |
| --- | --- |
| `domain_events` (new) | `id`, `school_id` (nullable), `type`, `payload` JSON, `occurred_at` (µs), `available_at` (backoff), `dispatched_at`, `attempts`, `last_error`; index (`dispatched_at`, `available_at`, `id`) |
| `audit_log` (new) | `id`, `school_id` (nullable), `actor_id`, `actor_type` (`user`, `guest`, `system`), `action`, `subject_type`, `subject_id`, `before` JSON, `after` JSON, `ip`, `request_id` (128), `created_at` (µs); indexes on subject, actor, action and time. Append-only. |
| `settings` | `school_id` nullable (null = platform key); index (`school_id`, `type(191)`). There was no unique index on the setting name to widen (`type` is TEXT and installs may hold duplicates). |
| Cents columns (BIGINT, nullable, after their float) | `fees_classes.amount_cents`, `fees_paids.total_amount_cents`, `fees_paids.due_charges_cents`, `fees_choiceables.total_amount_cents` (optional fees and due-charge rows), `paid_installment_fees.amount_cents`, `paid_installment_fees.due_charges_cents`, `payment_transactions.total_amount_cents` |
| Not money | `installment_fees.due_charges` and `session_years.fee_due_charges` are percentages (column comments "in percentage (%)") and get no cents column. |

Back-fill (in the migration, and again by `php artisan money:backfill-cents`, scheduled daily at 02:10): `ROUND(CAST(float AS DECIMAL(20,6)) * 100)` in id ranges of 1000, only where cents are empty. The floats stay until the ledger work in a later phase. Migrations: `2026_10_22_100100_create_domain_events_table`, `..._100200_create_audit_log_table`, `..._100300_add_school_id_to_settings_table`, `..._100400_add_cents_columns_to_money_tables`; every `down()` is tested.

## Code

| Part | Where |
| --- | --- |
| Outbox | `App\Services\Platform\Events\{DomainEvent, Outbox, OutboxListener}`, `App\Models\Platform\OutboxEvent`, `App\Jobs\Platform\DispatchOutbox`, `outbox:dispatch`, listeners in `config/platform.php` (`outbox.listeners`) |
| Audit | `App\Services\Platform\Audit\{Audit, Auditable}`, `App\Models\Platform\AuditLog`, `App\Services\Platform\RequestContext`; trait on `User`, `Students`, `ExamMarks`, `FeesPaid`, `Settings` |
| Money | `App\Services\Platform\Ledger\{Money, MoneyCast, HasMoneyColumns, LegacyAmount, CentsColumns}`; `HasMoneyColumns` on `FeesClass`, `FeesPaid`, `FeesChoiceable`, `PaidInstallmentFee`, `PaymentTransaction` |
| Reads in cents | `FeesPaymentService`, `StripePayment::amountInSubunits()`, `WebhookController` (amount check), app fee totals (`Api/{Student,Parent}/FeesController`), `Fees/{Payment,Paid,Class}Controller`, `Student/ReportController`, `FeesCollectionService`, `FeesReportService`, `TransferClassFeeType`; receipts and reports print model attributes, which come from the cents |

`HasMoneyColumns` keeps each float and cents pair in step on every model write and serves the float attribute (and `toArray()`) from the cents; query-builder writes in the code base set both columns. A row written by some other raw insert is summed through `CentsColumns::sql()` (its float, rounded the same way) until the daily back-fill fills it.

## Endpoints

None. v1 responses keep every field and type; amounts are derived from cents. The only values that differ are amounts the FLOAT column had already garbled (MySQL returns six significant digits, so a stored 12345.67 read back as 12345.7, and sums of floats carried binary noise): those now come back exact.

## Screens

None.

## Notification types

None.

## Module switch

None: kernel, always on.

## Security notes

- Audit rows never contain `$hidden` attributes (passwords, remember tokens), `platform.audit.never_store` names (API and device tokens) or the values of secret settings. They are append-only through the model; a retention job (later phase) is the only thing that may delete them.
- An audit row that cannot be written is logged (`audit_record_failed`, ids only) and never blocks the change; before the table exists (earlier migrations) nothing is written.
- Query-builder writes are not audited by the trait; use `Audit::record()` where they matter.
- Money: no floats in `Money`'s API; `MoneyCast` refuses floats. Legacy panel money writes still lack idempotency keys (they arrive with the ledger work in a later phase); the two fee removal actions now run in a transaction.
- Outbox listeners run inside the dispatch transaction; listeners with external effects must queue a job.

## Self-test steps

1. Edit a learner's roll number on staging: `audit_log` has an `updated` row with before/after, your user id, IP and the request id from the response's `X-Request-Id`.
2. Save the Email Configuration page: the `mail_password` row's audit shows `[redacted]`.
3. Record a cash payment of 1234.56 for a learner: `fees_paids.total_amount_cents` is 123456 and the receipt shows 1234.56.
4. Open the parent app's fees screen: the totals match the panel.
5. `php artisan money:backfill-cents` on staging: "Filled 0 cents values", no mismatches.
6. `php artisan outbox:dispatch`: "Dispatched 0, failed 0, skipped 0."
