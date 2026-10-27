The [Ledger foundation](Ledger) supports the new billing and manual verified
cash/EFT workspace. Historical receipt tables remain separate, and no new payment
gateway is activated. Existing
academic access is independent of fee arrears: the reminder setting and its start
date never lock a learner out of Home, marks or released reports.

The new **Money → Family accounts / Bill runs** workspace adds explicit
school-year payer/guardian shares, annual previews with exceptions, approval and
atomic posting of sealed invoices. Charges debit family receivables and credit
school fee income; they do not collect payment or repost historical receipts.
Annual learner/year uniqueness and permanent operation receipts prevent duplicate
bills. Existing v1 fee/payment history remains its own unchanged contract.
Parent statements, allocations, taxable items/discounts/pro-rata/credits and
payment-provider work follow separately. No gateway has been selected and Peach
is not preferred; the legacy Stripe documentation below does not select it for
new Phase 5 billing. See [the family billing contract](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/specs/money/family-billing.md).

This page explains how school fees are modelled, how the student/parent app pays them online through Stripe, how a payment is confirmed (only by a signed gateway webhook), how staff record cash and cheque payments, and which settings drive all of it. It describes the code after the fee-forgery fixes in commit `839c9eb` and the Phase 0 removal of the Razorpay, Paystack and Flutterwave gateways (see [Removed gateways](#removed-gateways)). Stripe is the only gateway until the South African gateway arrives in Phase 5.

For the click-by-click admin steps see the Docusaurus pages [Fee Payment (admin panel)](https://shawndaprawn.github.io/Eschool/admin-panel/fee-payment/), [Payment Gateway's Webhook Configuration](https://shawndaprawn.github.io/Eschool/admin-panel/webhook-config/) and [Fee Payment (features)](https://shawndaprawn.github.io/Eschool/features/fee-payment/).

## Overview

| Piece | Where |
|---|---|
| Fee setup (types, class fees, installments) | `Fees\TypeController`, `Fees\ClassController`, `SessionYearController` / `SessionYearService` |
| Server-side pricing and recording of online payments | [`FeesPaymentService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/Payment/FeesPaymentService.php) |
| Gateway factory, currency, webhook secrets, amount checks | [`PaymentService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/Payment/PaymentService.php) |
| Gateway adapter | `StripePayment` (implements `PaymentInterface`) in [`app/Services/Payment`](https://github.com/ShawnDaPrawn/Eschool/tree/main/admin-panel/app/Services/Payment) |
| App endpoints | `Api/Student/FeesController`, `Api/Parent/FeesController` (`/api/student/*`, `/api/parent/*`) |
| Payment confirmation | [`WebhookController`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Http/Controllers/WebhookController.php) (`/webhook/*`) |
| Offline (cash/cheque) payments, receipts, logs | `Fees\PaymentController`, `Fees\PaidController`, `Fees\TransactionLogController` (admin panel) |
| Dashboard and app statistics | [`FeesCollectionService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/FeesCollectionService.php), [`FeesReportService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/FeesReportService.php) |
| Tests | [`tests/Feature/PaymentWebhookTest.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/tests/Feature/PaymentWebhookTest.php), fee cases in `StudentApiSecurityTest` and `ParentApiSecurityTest` |

Composer packages used: `stripe/stripe-php`, and `barryvdh/laravel-dompdf` for receipts. The student/parent Flutter app pays with `flutter_stripe`.

## Fee model

Fees are scoped to a session year. A fee **type** is a name; it becomes payable when it is attached to a **class** with an amount and a compulsory/optional flag.

| Model (table) | Purpose | Key columns |
|---|---|---|
| `FeesType` (`fees_types`) | Named fee (Tuition, Bus, ...) | `name`, `description`. Soft-deleting a type soft-deletes its `fees_classes` and `fees_choiceables` rows (model `deleting` hook). |
| `FeesClass` (`fees_classes`) | A fee type assigned to a class for a session year | `class_id`, `fees_type_id`, `amount` (float), `choiceable` (0 compulsory, 1 optional), `session_year_id`. Deleting it deletes the matching `fees_choiceables` rows. |
| `InstallmentFee` (`installment_fees`) | Installment schedule for compulsory fees, per session year | `name`, `due_date`, `due_charges` (percent), `session_year_id`. The `due_date` accessor formats with the `date_formate` setting; use `getRawOriginal('due_date')` for arithmetic. |
| `PaidInstallmentFee` (`paid_installment_fees`) | One paid (or pending) installment for a student | `student_id`, `class_id`, `parent_id`, `installment_fee_id`, `session_year_id`, `amount`, `due_charges`, `date`, `payment_transaction_id`, `status` (0 not paid, 1 paid) |
| `FeesChoiceable` (`fees_choiceables`) | One paid (or pending) optional fee, or the due charges paid with a full compulsory payment | `student_id`, `class_id`, `fees_type_id` (null for due charges), `is_due_charges` (0/1), `total_amount`, `session_year_id`, `date`, `payment_transaction_id`, `status` (0 not paid, 1 paid) |
| `FeesPaid` (`fees_paids`) | Running ledger per student, class and session year | `parent_id`, `student_id`, `class_id`, `mode`, `payment_transaction_id`, `cheque_no`, `total_amount` (accumulates every payment, optional ones included), `is_fully_paid`, `due_charges`, `date`, `session_year_id` |
| `PaymentTransaction` (`payment_transactions`) | One payment attempt (online or offline) | `student_id`, `class_id`, `parent_id`, `mode`, `cheque_no`, `type_of_fee`, `payment_gateway`, `order_id`, `payment_id`, `payment_signature`, `payment_status`, `total_amount`, `date`, `session_year_id` |

All of these use `SoftDeletes`. Column codes used throughout the code:

| Column | Values |
|---|---|
| `payment_transactions.payment_status` | 0 failed, 1 succeeded, 2 pending |
| `payment_transactions.payment_gateway` | 2 Stripe (`PaymentService::STRIPE`); null for offline payments. 1 Razorpay, 3 Paystack and 4 Flutterwave appear on transactions made before they were removed (`PaymentService::RETIRED`); those ids are never reused |
| `mode` (transactions and `fees_paids`) | 0 cash, 1 cheque, 2 online |
| `payment_transactions.type_of_fee` | 0 compulsory in full, 1 installments, 2 optional (`FeesPaymentService::COMPULSORY`, `INSTALLMENTS`, `OPTIONAL`) |

Session-year columns that drive due charges (set on the Session Years page, see [Academic-Structure](Academic-Structure)): `fee_due_date`, `fee_due_charges` (percent applied to a full compulsory payment after the due date), `include_fee_installments`, `free_app_use_date`. `SessionYearService::storeInstallments()` refuses installment due dates outside the session dates, and the session-year "transfer" options include `TransferClassFeeType` to copy class fees into a new session year.

What a student owes:

- Compulsory total = sum of `fees_classes.amount` where `class_id` is the student's class in that session year and `choiceable = 0`.
- With installments, each installment is `compulsory total / number of installments` (an even split), plus `due_charges`% of that share once its due date has passed.
- Without installments, a full payment adds `fee_due_charges`% of the compulsory total once `fee_due_date` has passed.
- Optional fees are paid individually at their `fees_classes.amount`.

## Online payment flow

```mermaid
sequenceDiagram
    participant App as Student or parent app
    participant API as Admin panel API
    participant GW as Payment gateway
    participant WH as WebhookController
    participant DB as Database
    App->>API: POST add-fees-transaction (payment_method, type_of_fee, amount, selected fees)
    API->>DB: FeesPaymentService prepare() prices the request from the fee setup
    API->>DB: createPendingTransaction() saves payment_status 2 and rows with status 0
    API->>GW: createAndFormatPaymentIntent(server total, metadata)
    GW-->>API: PaymentIntent id
    API->>DB: save it as payment_transactions.order_id
    API-->>App: payment_gateway_details and payment_transaction_id
    App->>GW: pay with the Stripe payment sheet
    App->>API: POST store-fees (transaction_id, payment_id, payment_signature)
    GW->>WH: POST /webhook/stripe (signed)
    WH->>WH: verify signature, find transaction, compare amount and currency
    WH->>DB: markPaid() under a row lock (idempotent)
    WH->>DB: Notification and UserNotification rows
    WH-->>App: FCM push Payment Success to the parent
```

### App endpoints

All are behind `auth:sanctum`. Student routes also have `role:Student` and `student.session.active`; parent routes have `role:Parent` and `parent.children.session.active`, and the ones marked "CheckChild" additionally require a `child_id` belonging to the parent (`App\Http\Middleware\CheckChild`). See [API-Student](API-Student) and [API-Parent](API-Parent) for the rest of those APIs.

| Endpoint | Controller method | Purpose |
|---|---|---|
| `GET /api/settings?type=app_settings` | `ApiController::getSettings` | Public. Returns `online_payment`, `compulsory_fee_payment_mode`, `is_student_can_pay_fees`, `payment_options.currency_code/currency_symbol`, and, when Stripe is enabled, `payment_options.stripe` with only its status, publishable key and currency. Secret keys and webhook secrets are never returned (the Razorpay webhook secret used to be; see [commit 839c9eb](#forgery-protections-commit-839c9eb)). The removed gateways are always left out, as a disabled gateway is. |
| `GET /api/student/fees-details`, `GET /api/parent/fees-details` (CheckChild) | `getFeesDetails` | Compulsory, optional and installment items with paid flags, totals, `compulsory_due_date`, `compulsory_due_charges`, `is_fee_pending`. Also expires the latest pending transaction if it is more than one hour old (sets `payment_status` 0). |
| `POST /api/student/add-fees-transaction`, `POST /api/parent/add-fees-transaction` (CheckChild) | `storeFeesTransaction` | Validates `payment_method` (1-4), `amount`, `type_of_fee` (0-2), optional `optional_fees_data[]` / `installment_data[]` (each `{id: ...}`); refuses (`The selected payment method is not available.`) anything but Stripe (2) with `stripe_status` on; prices, creates the pending transaction and the gateway intent; returns `payment_gateway_details` (the formatted intent plus `payment_transaction_id`). `is_fully_paid` is still accepted from old app builds but ignored. |
| `POST /api/student/store-fees`, `POST /api/parent/store-fees` (CheckChild) | `storeFees` | Notes `payment_id` and `payment_signature` on the caller's own transaction, only if those columns are still empty. Student: pending transactions only. Parent: pending or succeeded (the webhook often arrives first). It never marks anything paid. |
| `POST /api/student/fail-payment-transaction`, `POST /api/parent/fail-payment-transaction` | `failPaymentTransactionStatus` | Marks the caller's own **pending** transaction failed (`payment_status` 0) and sends a "Payment Failed" notification. A transaction the webhook has already marked paid is refused. The student version uses a conditional update so a concurrent webhook wins. |
| `POST /api/student/get-payment-status`, `POST /api/parent/get-payment-status` | `Parent\FeesController::getPaymentStatus` (both routes) | Stripe only. `payment_intent_id` must match `^pi_[A-Za-z0-9_]+$` and belong to a transaction of the caller (student's own id, or the parent's children); returns the PaymentIntent `status` from Stripe. `StudentApiController::getPaymentStatus` still exists but no route points to it. |
| `GET fees-paid-list` | `feesPaidList` | `fees_paids` rows: the student's for the current session year; the child's for every session year on the parent API. |
| `GET fees-paid-receipt-pdf` | `feesPaidReceiptPDF` | Base64 PDF (`fees.fees_receipt` view). Parent: requires `fees_paid_id` of one of their children. Student: uses the student's first `fees_paids` row. |
| `GET fees-transactions-list` | `getFeesPaymentTransactions` | Paginated (15) `payment_transactions`, optional `session_year_id` (parent: optional `child_id`, else all the parent's children); also expires pending transactions older than one hour. |
| `GET fees-report` | `getFeesReport` | `FeesReportService::getStatistics()` (see [Reporting](#reporting-services)). |
| `GET /api/student/send-fee-notification` | `Student\FeesController::sendFeeNotification` | Pushes "Fees are dues" (type `fees-due`, `child_id` in the data) to the student's parents. See [Notifications](Notifications). |

The app sends `payment_method` 2 for Stripe. Older builds may still send 1 (Razorpay), 3 (Paystack) or 4 (Flutterwave); the server accepts the value and answers it the way it answers a gateway the school has switched off, so an old build shows its usual "not available" message.

### Server-side pricing (`FeesPaymentService::prepare`)

`prepare(int $studentId, int $sessionYearId, int $typeOfFee, array $optionalFees, array $installments, $clientAmount)` decides what is being paid and for how much. The app's `amount` is only compared with the server total.

- The student's class comes from `student_sessions` for the current session year (`session_year` setting); no session means refusal.
- Compulsory (0): refused if `fees_paids.is_fully_paid = 1` already, or if any installment has been paid (`Installments have been started; please pay the remaining installments.`). Due charges = `fee_due_charges`% of the compulsory total when the session's `fee_due_date` has passed. The plan gets `is_fully_paid = 1`.
- Installments (1): each id must be an installment of the session year, not repeated, and not already paid; due charges apply per installment. `is_fully_paid` becomes 1 only when the already-paid plus selected installments cover all of them.
- Optional (2): each `fees_type_id` must be an optional `fees_classes` row of the student's class and session year and not already paid. Amounts sent by the app per fee are ignored.
- Ids must be positive integers, given once each.
- Due-date boundary: the app may be in another timezone, so an item whose due date is today or yesterday (`DUE_MAYBE`) is accepted with or without its due charges, whichever matches the app's total. Past dates (`DUE_YES`) always include them.
- The app's total must match within `AMOUNT_TOLERANCE` (0.02 currency units), otherwise `The fee amount has changed. Please refresh and try again.`
- Errors are thrown as `ValidationException` with key `fees`; the controllers return them as validation errors.

`createPendingTransaction($plan, $parentId, $paymentMethod)` then writes a `payment_transactions` row (`mode` 2, `payment_status` 2, server `total_amount`) and one `fees_choiceables` or `paid_installment_fees` row per item with `status` 0 and the transaction id. `gatewayMetadata()` builds the metadata/notes sent to the gateway (student, parent, class, session year, transaction id, the row ids as JSON, payer email/name/mobile). That metadata is informational: webhooks never use it to decide what was paid.

Who is recorded as the parent: for the student endpoint `father_id`, else `guardian_id`; the payer email is the father's, else the guardian's. For the parent endpoint, the logged-in parent.

### Gateway adapter

`PaymentService::create($payment_method)` builds the adapter with keys from the `settings` table (`getSettings()`); only `PaymentService::STRIPE` (2) is accepted. `PaymentService::currencyCode()` uses the gateway's own `stripe_currency_code` setting, else `currency_code`, upper-cased. Saving the Fees configuration requires an enabled gateway's currency to be a three-letter code; any text used to be accepted, and each parent payment then failed with `error_occurred`.

| Gateway | What `createPaymentIntent` creates | Stored as `order_id` | Amount sent | Notes |
|---|---|---|---|---|
| Stripe | `paymentIntents->create()` with `metadata` | PaymentIntent id (`pi_...`) | `StripePayment::amountInSubunits()`: raised to Stripe's minimum charge for the currency (e.g. 0.50 USD/EUR/INR, 0.30 GBP, 50 JPY), x100 except zero-decimal currencies | The app confirms with the intent's client secret from `payment_gateway_response` |

## Payment confirmation (webhooks)

Routes (in `routes/web.php`, outside every auth group; `bootstrap/app.php` exempts `webhook/*` from CSRF):

| Method and URL | Handler | Signature check | Events handled | Transaction lookup |
|---|---|---|---|---|
| `POST /webhook/stripe` | `stripe` | `Stripe\Webhook::constructEvent()` with the `Stripe-Signature` header and `webhookSecret('stripe')`; refused outright when no secret is set | `payment_intent.succeeded` (uses `amount_received`), `payment_intent.payment_failed` | `payment_gateway = 2` and `order_id` = intent id |

`PaymentService::webhookSecret('stripe')` reads the `stripe_webhook_secret` settings row first, then `services.stripe.webhook_secret` (defined in [`config/services.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/services.php) from `STRIPE_WEBHOOK_SECRET`, which the admin panel never writes, so it is only a manual fallback). It returns `''` when neither is set, and the handler then refuses the call.

`POST /webhook/razorpay`, `/webhook/paystack`, `/webhook/flutterwave` and the `GET /response/paystack/success` and `/response/flutterwave/success` landing pages were removed with their gateways and answer 404.

After the signature, the handler:

1. Find the transaction created by this app for that gateway. Not found: log a warning and return 200 `Transaction not found` (so a gateway shared with another integration stops retrying).
2. On success events, `paid()` checks the reported currency against `PaymentService::currencyCode()` and the amount with `PaymentService::amountMatches()` (Stripe's sub-units). A mismatch is logged and answered with 400; nothing is recorded.
3. `FeesPaymentService::markPaid($transactionId, $attributes)` runs in a DB transaction with `lockForUpdate()` on the transaction. If the transaction is already `payment_status = 1` it returns null and the webhook answers `Payment already recorded`, so retries and duplicates are harmless. Otherwise it:
   - stores gateway ids (`payment_id`) and sets `payment_status = 1`;
   - restores (if a failed attempt soft-deleted them) and sets `status = 1` on every `fees_choiceables` (non-due-charge) and `paid_installment_fees` row carrying this `payment_transaction_id`;
   - for a compulsory payment, records due charges as a new `fees_choiceables` row (`is_due_charges = 1`) when the stored total exceeds the class's compulsory total;
   - adds the transaction total to the student's `fees_paids` row (locked) or creates it, and recomputes `is_fully_paid` (always 1 for compulsory; for installments, 1 when every installment of the session year is paid; unchanged for optional fees).
4. On failure events, `markFailed()` sets `payment_status = 0` and deletes the transaction's rows that are still `status = 0`, but leaves an already-paid transaction alone (a late failure for an earlier attempt cannot undo a payment). A later success for the same order still records the payment, restoring those rows.
5. When a payment was newly recorded (or a pending one newly failed), `notifyParents()` creates a `Notification` (`send_to` 2, type `online`, title `Payment Success` / `Payment Failed`, message `Amount :- <total>`), a `UserNotification` for the parent's user, and calls `sendSimpleNotification()`. A notification failure is logged and never undoes the payment.

What a payment covers always comes from the stored transaction and its rows; webhook metadata such as `is_fully_paid`, `due_charges` or `installment_fees_paid` is ignored.

Pending transactions are not swept by a scheduled job. They are only flipped to failed (`payment_status` 0) when the app reads `fees-details` or `fees-transactions-list` more than an hour after the attempt; their `status = 0` rows stay until a failure webhook removes them, and a late success webhook still records the payment.

## Offline payments by staff

The admin panel's **Fees > Fees Paid** page (`fees.paid.index`, view `fees/fees_paid.blade.php`) lists every enrolled student of a class and session year (`StudentSessions`-based, so unpaid students appear) through the Bootstrap-table endpoint `GET fees/paid/list`. From it staff record payments (permission `fees-paid` for every route below):

| Route | Method | Behaviour |
|---|---|---|
| `POST fees/compulsory-paid/store` (`fees.compulsory-paid.store`) | `compulsoryFeesPaidStore` | `mode` 0 cash or 1 cheque (`cheque_no`), `date`, `session_year_id`, `total_amount`, optional `installment_fees[]` (`id`, `amount`, `due_charges`, `fully_paid`), `installment_mode`, `due_charges_whole_year`. Creates a succeeded `payment_transactions` row (`type_of_fee` 1 if installments, else 0), the `paid_installment_fees` rows, and updates/creates `fees_paids` (`is_fully_paid` = 1 when `installment_mode` is 0, else the last installment's `fully_paid` flag). |
| `POST fees/optional-paid/store` (`fees.optional-paid.store`) | `optionalFeesPaidStore` | Same for `optional_fees_type_data[]` (`id`, `amount`) into `fees_choiceables`, `type_of_fee` 2. |
| `DELETE fees/paid/remove-choiceable-fees/{id}` | `feesPaidRemoveChoiceableFees` | Deletes one optional fee payment and subtracts it from the transaction and `fees_paids` (deleting them when they reach 0). |
| `DELETE fees/paid/remove-installment-fees/{id}` | `feesPaidRemoveInstallmentFees` | Same for one installment; sets `is_fully_paid` to 0. |
| `DELETE fees/paid/clear-data/{id}` | `clearFeesPaidData` | Deletes a `fees_paids` row and all `fees_choiceables`, `paid_installment_fees` and `payment_transactions` of that student, class and session year. |
| `GET fees/paid/receipt-pdf/{id}` (`fees.paid.receipt.pdf`) | `feesPaidReceiptPDF` | Streams the receipt PDF for one `fees_paids` row, built with `Pdf::loadView('fees.fees_receipt', ...)` using the `logo2` setting (`schoolSetting('logo2')`), `config('app.name')` (the school name, see [Configuration](Configuration)) and the `school_address` and `currency_symbol` settings. The app receipt endpoints use the same sources. |

Unlike the app endpoints, staff-entered amounts are taken from the request (staff are trusted). Both store methods require the student to have a father or guardian (`no_parent_or_guardian_assigned` otherwise), because `payment_transactions.parent_id` and `paid_installment_fees.parent_id` reference `parents`. Both send a "Payment Success" push with type `Online` to that parent, even for cash.

Other fee pages (all in the `Fees\` controllers, views in `resources/views/fees/`):

| Page | Routes | Permission |
|---|---|---|
| Fees Type | resource `fees-type` (`index`, `store`, `show` = list JSON, `update`, `destroy`) | `fees-type` |
| Assign Fees Classes | `fees/classes` (`fees.class.index`), `fees/classes/list`, `POST class/fees-type` (`class.fees.type.update`), `DELETE class/fees-type/{fees_class_id}` | `fees-classes` |
| Fees Transaction Logs | `fees/transaction-logs` (`fees.transactions.log.index`), `fees/transaction-logs/list` (filters: session year, class, `payment_status`; search on id, `order_id`, `payment_id`, student name) | `fees-paid` |
| Fees Configuration | `fees-config` (`fees.config.index`), `POST fees-config/update` (`fees.config.udpate`) | `fees-config` |

## Settings reference

Everything below is stored only in the `settings` table (`type`/`message` pairs, read with `getSettings()`). The fees configuration page (`Fees\ConfigController::feesConfigUpdate`, view `fees/fees_config.blade.php`) writes the gateway keys there. It no longer copies anything into `.env`: commit `e13ac7d` removed the `changeEnv()` helper, so the checkout and the webhooks read the same settings rows. A gateway's keys are only saved while its status is 1, and `required_if:<gateway>_status,1` makes its keys, webhook secret, webhook URL and currency mandatory when enabling it. With `DEMO_MODE=true` (`config('app.demo_mode')`) the form shows `x` placeholders instead of the stored keys.

| Setting key | Written by | `.env` fallback (only when the setting is empty) | Used by |
|---|---|---|---|
| `currency_code`, `currency_symbol` | Fees configuration | none | Default currency; receipts; `/api/settings` |
| `default_due_charges`, `enable_fees_installment` | Fees configuration | none | Defaults shown on the session-year form |
| `compulsory_fee_payment_mode` | Fees configuration | none | When 1, login/profile APIs return `is_fee_payment_due` (from `free_app_use_date`, `fee_due_date`, installments) for an informational overdue-fee reminder. The public app-settings compatibility field is always `0`, so older installed apps cannot restore a fee lock. It never blocks Home, learning, marks or released reports |
| `is_student_can_pay_fees` | Fees configuration | none | Sent to the app; the student payment endpoints do not check it |
| `online_payment` | General settings (`SettingController::update`) | none | Sent to the app; not checked by the payment endpoints |
| `stripe_status`, `stripe_publishable_key`, `stripe_secret_key`, `stripe_webhook_secret`, `stripe_webhook_url`, `stripe_currency_code` | Fees configuration | `STRIPE_WEBHOOK_SECRET` (`services.stripe.webhook_secret`) | Secret key from settings only; webhook secret from the setting, then the config fallback |

`config/services.php` also defines `services.stripe.key/secret` from `.env`, but no payment code reads them.

The `stripe_webhook_url` field is read-only, pre-filled with `<scheme://host>/webhook/stripe`, and only stored for reference; the Stripe dashboard must point at that URL.

Installs that used a removed gateway still have its `razorpay_*`, `paystack_*` or `flutterwave_*` rows. Nothing reads them any more and `/api/settings` never returns them; they hold secret keys, so delete them when the keys move to Secret Manager.

## Forgery protections (commit 839c9eb)

That commit rewrote the payment path while all four gateways were still in place. The behaviour it guarantees for Stripe, and that `PaymentWebhookTest` checks:

- `GET /api/settings` no longer returns the Razorpay webhook secret (it was public, so anyone could sign a fake "payment captured" webhook). The commit message asks installs to rotate that secret.
- Webhooks fail closed without a secret; Flutterwave actually checks `verif-hash` (previously any request passed); Razorpay and Paystack no longer accept an HMAC made with an empty key; Razorpay's failure branch is signed like the success branch; comparisons are constant time.
- The transaction is found by the stored gateway order id (Flutterwave: by transaction id plus gateway), amount and currency are checked, and coverage comes from stored rows only.
- The duplicate-webhook guard used to check a column that does not exist, so replays added the amount to `fees_paids` again; recording is now idempotent under a row lock.
- Students and parents used to choose the amount, the fully-paid flag and which installments/optional fees were covered (paying 1.00 could mark everything paid). `FeesPaymentService` now prices the payment from the fee setup, checks every item belongs to the child and is unpaid, and charges the server total.
- `store-fees` and `fail-payment-transaction` only work on the caller's own pending transaction (parent `store-fees` also accepts its own succeeded one).

## Annual fee plans and previews

Finance staff with `fee-plans-manage` use **Money → Fee plans** to save annual
rates for all learners, a canonical grade or a school class. Draft/archived rates
are ignored by previews. New-year copies require a later year and an explicit
uplift; they stay draft until reviewed. Custom Finance roles need an explicit
permission grant; the demo Finance role includes it.

The preview reads dated enrolments on the selected date and lists missing or
overlapping plans, imported dates needing confirmation, unavailable homerooms and
partial-year enrolments needing a pro-rata/refund rule. It keeps currencies
separate. It is an annual-rate preview, not an invoice or a family statement:
there is no bill posting, payment attempt or ledger effect in this increment.

No gateway is selected for Phase 5. The owner's 26 October decision supersedes
the old Peach preference; provider-specific work is deprioritized. Existing
Stripe/v1 payment history remains intact while billing and commerce progress.

## Removed gateways

The Build Bible removes Razorpay, Paystack and Flutterwave in Phase 0 and keeps Stripe. What changed, and what stayed for installed apps (spec: `engineering/specs/platform/removals.md`):

| Area | Now |
|---|---|
| Server code | `RazorpayPayment`, `PaystackPayment`, `FlutterwavePayment`, `config/paystack.php`, their `config/services.php` entries, the Paystack keys in `SettingsConfigService` and their tabs on the Fees configuration page are gone |
| Composer | `razorpay/razorpay`, `unicodeveloper/laravel-paystack` and the `laravel-shift/laravel-paystack` VCS repository are removed |
| Webhooks | `POST /webhook/razorpay`, `/webhook/paystack`, `/webhook/flutterwave`, `GET /response/paystack/success` and `/response/flutterwave/success` answer 404 |
| `GET /api/settings?type=app_settings` | `payment_options` never contains `razorpay`, `paystack` or `flutterwave`, exactly as when a school switched one off, so installed apps stop offering them. No key was renamed; the API snapshots are unchanged |
| `add-fees-transaction` | `payment_method` 1, 3 and 4 still validate and are answered `The selected payment method is not available.`, as for a disabled gateway |
| History | `payment_transactions.payment_gateway` keeps 1, 3 and 4 on old payments; the transaction log still shows Razorpay, Paystack and Flutterwave for them (`feesOnlineTransactionLogParentGateway` in `formatter.js`) |
| Apps | `razorpay_flutter` is removed from the student/parent app by the app work stream; installed builds keep working because the server simply stops offering the gateway |

A payment left pending on a removed gateway can no longer be confirmed by its webhook; settle any such transaction by hand.

## Reporting services

| Service | Used by | What it returns |
|---|---|---|
| `FeesCollectionService::summary($sessionYearId)` | `GET home/widget/fees-collection` (`HomeController::feesCollectionWidget`, permission `fees-paid`) | `currency`, `collected`, `upcoming_dues`, `overdue`, `series`, `labels` for the dashboard donut. `outstandingByDueDate()` walks every active `student_sessions` row, takes the class's compulsory total minus what the student paid (all `fees_paids` minus paid optional fees), and buckets it by unpaid installment due dates (overdue buckets include the installment's due-charge percent) or by the session `fee_due_date`. Note: `collected()` returns the whole `fees_paids` sum; the line that would subtract optional fees sits after a `return` and never runs. |
| `FeesReportService::getStatistics($studentId, $sessionYearId)` | `GET /api/student/fees-report`, `GET /api/parent/fees-report` | `total_fee_payable` (compulsory total), `total_paid` (succeeded transactions with `type_of_fee` 0 or 1), `total_pending`, `last_payment_date`, `next_due_date` (next unpaid installment or the session due date), `instalments` (`total`, `completed`, `pending`) or null. Returns `[]` when the student has no session that year. |

## The `app:delete-fees` command

[`app/Console/Commands/DeleteFeesPaid.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Console/Commands/DeleteFeesPaid.php) registers `php artisan app:delete-fees` ("Delete Fees Data"). It runs `DB::table(...)->delete()` on `fees_choiceables`, `payment_transactions` and `fees_paids` for **every** session year, bypassing soft deletes, and logs `data-delete`. It does not touch `paid_installment_fees` directly, though that table's `payment_transaction_id` foreign key is `ON DELETE CASCADE`. It is not scheduled in `routes/console.php`; treat it as a destructive maintenance tool. For per-session clean-up use the session year's "clear specific data" option (`fees_transactions` deletes `fees_choiceables`, `fees_classes`, `installment_fees` and `fees_paids` of that session year). See [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands).

## Tests

`tests/Feature/PaymentWebhookTest.php` (in-process, `RefreshDatabase`, seeds like the installer) covers:

| Test | Checks |
|---|---|
| `test_compulsory_fees_are_priced_on_the_server` | Wrong amount refused; full payment total and `is_fully_paid` |
| `test_due_charges_are_required_once_the_due_date_has_passed` | Due charges required after the due date; either total accepted on the due date |
| `test_installments_are_priced_and_checked_on_the_server` | Per-installment pricing with due charges, duplicates/unknown ids refused, paid installments and full payment after installments refused |
| `test_optional_fees_must_belong_to_the_class_and_not_be_paid` | Client per-fee amounts ignored, other-class fees and paid fees refused |
| `test_pending_transaction_and_gateway_metadata_use_server_values` | Pending transaction, row amounts and metadata come from the plan |
| `test_payment_is_recorded_once_from_the_stored_transaction` | Metadata pointing at another student is ignored; retried delivery adds the amount once |
| `test_payment_with_a_different_amount_or_currency_is_not_recorded` | Amount or currency mismatch gets 400 |
| `test_late_failure_event_does_not_undo_a_payment` | `payment_intent.payment_failed` after `payment_intent.succeeded` leaves it paid |
| `test_failed_attempt_then_success_on_the_same_order_is_recorded` | Failure deletes pending rows; later success restores and records them |
| `test_stripe_webhook_needs_a_configured_secret_and_valid_signature` | Stripe signature checking and refusal without a secret |
| `test_compulsory_payment_with_due_charges_records_them_from_the_stored_total` | Due charges recorded from the stored total, not metadata |
| `test_removed_gateways_have_no_webhooks_and_their_old_transactions_still_read` | The removed webhook and landing URLs answer 404; old Razorpay, Paystack and Flutterwave transactions keep their gateway in the transaction log |

`StudentApiSecurityTest::test_public_settings_do_not_include_payment_secrets`, `test_removed_gateways_are_neither_offered_nor_accepted` and `test_fee_payments_use_server_amounts_and_own_transactions`, and `ParentApiSecurityTest::test_fee_receipt_and_payment_endpoints_refuse_another_familys_records`, cover the app side. See [Testing-and-CI](Testing-and-CI).

## Gotchas

- Gateway keys and webhook secrets saved in the admin panel take effect on the next request, including with `php artisan config:cache`, because they are read from the settings table. `.env` payment keys are only consulted while the matching setting is empty; with cached config they are whatever was in `.env` when the cache was built.
- Older installs may still have payment keys in `.env` from when the panel copied them there; they are no longer updated, so a stale `.env` webhook secret is harmless only as long as the setting is filled in.
- An enabled gateway with a missing webhook secret leaves every payment pending: the app shows the gateway's success, but nothing is recorded until a valid webhook arrives.
- The student app's receipt endpoint uses the student's first `fees_paids` row, not one for a chosen session year.

## See also

- [Notifications](Notifications)
- [API-Student](API-Student), [API-Parent](API-Parent)
- [Admin-Panel-Overview](Admin-Panel-Overview)
- [Configuration](Configuration)
- [Security-Model](Security-Model)
- [Database-Schema](Database-Schema)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)


## Verified manual ledger receipts

Staff with `fee-payments-manage` use **Money → Fee payments**. Choose a family
account and the holder whose shares the payment covers; optionally reserve it for
one learner. Record confirmed cash or EFT using a unique cash receipt/bank
transaction ID, amount and received date. The reusable family reference is not a
unique transaction ID. Confirm this receipt was not already entered in legacy
history. Do not re-enter old payments here: the two histories remain separate.

A new receipt is held credit until allocation is reviewed and posted. Choose the
school allocation rule explicitly, open the receipt, review its eligible unpaid
shares and confirm allocation. Excess money stays held for that holder/learner;
it does not automatically settle another holder's obligation. A changed rule,
credit or outstanding share requires a fresh review. Posted history is immutable;
refunds and payer transfers require future reasoned workflows. Held credit can
prevent removing learner assignments or emptying an account, and receipt currency
cannot be changed. Finance can settle retained old-year invoices without editing
their charges. Disabling fees retains read-only financial history.

Parents open **Fee statements** from Home to view only their permitted shares,
receipts and held credit. Invoice balance and credit appear separately. Learner
pinning can mean held money cannot pay another displayed charge automatically.
A receipt touching a learner they can no longer view is withheld; totals explicitly
cover permitted ledger records. Other holders' details and raw bank references
are never in the parent response. No Pay now/provider activation is introduced.
Existing v1 receipt screens and the legacy leadership aggregate remain unchanged.

## Credit notes and corrections

Finance users with `fee-credits-manage` open **Fee credit notes**, choose a family
and a recorded learner/holder share, and enter a positive amount, correction
category and private explanation. Review the debt reduction and funds restored
before confirming. Changed charges/allocations require a fresh review. Exact
replay records the correction once. An original invoice cannot be edited.

Credits reduce unpaid debt first. A paid correction returns funds to their original
receipts as held credit, retaining the original holder and any learner restriction.
Finance can review a later allocation through the existing receipt workflow.
This does not send cash, make a bank payout or choose a school refund formula.
Statements show original charges, credits, net charges, applied payments, debt and
held funds separately. Historical invoice amounts and receipt amounts are retained.
Disabled fees retain credit history read-only. Private explanations are encrypted
at rest and available only to authorized Finance; parents see fixed categories
and their own currently permitted shares.

## Fee items and one-off charges

Finance with `fee-items-manage` opens **Fee items and charges**, creates a learner
or family item, enters its price/currency and explicitly selects **No VAT charged**.
Activate the item when ready. Taxable items and automatic membership/discount rules
remain separate work. Item names appear on invoices and parent statements.

Choose **Prepare item charge**, select its family payer, then select the learner
for a learner item. A family item charges the family once and apportions its total
over the reviewed learner roster, preserving each holder's overall percentage
share exactly. Review all learner/holder amounts, occurrence, date and due date
before confirming. Use a unique occurrence reference, such as an excursion
identifier: reopening a form or using a new request UUID cannot bill it twice.
Another occurrence requires another reference. Duplicate protection also survives
application-key rotation; the private reference text is encrypted at rest.

Posting creates a new immutable invoice and balanced receivable/income entry.
Existing annual tuition can still bill once per learner/year in either order.
Catalogue price/name revisions do not alter earlier invoices. An item with charge
history keeps its scope and currency; archive it to stop future charges. Correct
a posted share through a credit note. Oldest-first allocation uses due/date/line
order; tuition-first prioritises annual tuition, then additional items, while
retaining holder and learner restrictions. Retained financial history is readable
when fees are disabled. No payment gateway or payout is activated.


## Bank imports and EFT matching

Finance with `fee-payments-manage` opens **Money → Bank statement matching**.
Download the CSV headings and convert the school's bank export explicitly: ISO
dates, original unique bank transaction IDs, separate reusable family references,
one decimal credit/debit per row, and a currency. Maximum500 rows/256KiB per file.
Review and confirm the bank evidence; importing never posts money. Exact repeated
files and overlapping rows are retained once. Changed details under an existing
transaction ID stop the whole import for investigation.

Open an unmatched credit. Exact family references suggest an account; choose and
verify the holder and optional learner pin and enter a verification note. Review
then confirm. An agreeing manual EFT receipt is linked without a second posting;
otherwise one verified EFT receipt holds credit for separate reviewed allocation.
Existing mismatching receipts and stale previews stop confirmation. Never change
a transaction reference merely to get around a duplicate or mismatch.

Leave uncertain rows unresolved. Bank debits cannot become receipts. Non-fee
transactions can be reviewed and excluded with a reason, without posting money.
Matched/excluded/all filters retain history. Resolutions are immutable; mistakes
need a reasoned correction workflow rather than editing bank evidence. Disabled
fees keep read-only history. No gateway or bank connection is required.

## Arrears and accounting

Open **Money → Arrears and accounting**. Choose one currency and see current,
1–29,30–59,60–89 and90+ day unpaid invoice shares. Held credit is shown separately
because it may belong to another holder or be pinned to a learner. Filters change
the family list; totals still cover all accounts in that currency. Open a family
to review its full statement and separately allocate eligible credit.

Download aged debtors, invoice-share detail or posted fee journals as private CSVs.
Journal dates use the school calendar; ageing always shows current balances. For
Xero/QuickBooks column presets, configure your actual chart codes/names, base
currency and date format first; Xero also needs the organisation's exact zero-tax
label. Verify the CSV against your accountant's current import template. Journal
references are stable: choose non-overlapping periods and import each once.
Exports do not change money. Disabled fees retain report/download access.

Finance can configure private fee-statement reminders under **Money → Fee
reminders**. Choose minimum overdue days, cadence and earliest school-local hour
then explicitly enable the schedule. The existing minute scheduler invokes the
hourly `fees:remind` command. Prompts contain no balances/names, recheck current
access/debt at delivery and respect parent notification choices. Queued history
means an intent was recorded; channel preferences/access still determine delivery.

Fee plans can narrow grade/class/all targeting by phase and eligible learner
profile fields, such as campus or boarding. Configure payment options using one
month-day and percentage per line (for example `01-15 25.00`); percentages total
100. Choose a default and use the family workbench to select another option for a
learner before tuition posts. Review every installment and holder amount in the
bill preview. Posted invoices preserve their schedule; statements, allocation and
reminders follow each actual due date. Hidden/sensitive profile fields are excluded.

## Explicit fee item VAT

Finance declares school VAT registration under Fee items → School VAT details,
then chooses each item's tax treatment and rate. Review shows before-VAT, VAT and
total before posting. Posted invoices retain original legal details; credits reverse
the original tax even after settings change. Parent statements show their own VAT
only. Configure the VAT liability chart mapping before package accounting export;
journals already include VAT and use a zero-tax label to avoid adding it twice.

## Automatic item batches

Open an item and choose Configure item eligibility. Select a compulsory rule or
a dated activity/route/teaching/subject source, then prepare an automatic batch.
Use the same occurrence reference while resolving exceptions later. Review the
payer exceptions and every allocation; confirm any exclusions before posting.
Family items charge once for the family. Membership changes affect future charges;
posted invoices require reasoned credits. This does not record parent opt-in consent.

## Parent optional fee items

Finance chooses **Parent opt-in** and an explicit yearly or per-occurrence frequency
under an item's rules. Parents open **Optional fee items** from their account or
statement, review their own price/share/VAT and frequency, and confirm. Every
current payer must approve the current terms before Finance can bill. Changed
prices or allocations need renewed approval. Withdrawing stops future optional
charges; posted invoices require the existing reasoned credit process.
