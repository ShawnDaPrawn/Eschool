# Queue, scheduler and transactional mail (Phase 0, features 0.4 and 0.5)

Phase 0, features 0.4 and 0.5. Owner role: Backend (work stream S5). Source: Build Bible section 6, build steps 4 and 5; section 3.1 (Notifier); decision Q0.1 and [ADR 0004](../../adr/0004-transactional-email.md). The worker and Cloud Scheduler infrastructure is stream S4's ([environments](environments.md)); this spec covers the jobs, the delivery record and the mail provider.

Nothing a user sees changes: the same mails and pushes go to the same people with the same subjects, bodies and payloads. What changes is that they leave the web request, are retried, and every attempt is recorded.

## User stories

- **School admin:** when I admit a learner or add a teacher, the page answers at once even if the mail server is slow, and the welcome mail still arrives (retried if the server was down).
- **School admin:** "Verify email settings" still tells me straight away whether the mail server accepted the test mail.
- **Teacher, learner, parent:** pushes (chat, absence, leave, assignments, payments, notices) arrive as before; the action that triggered one never fails because of the push.
- **Platform operator:** I can see, per message, whether it was queued, sent, failed, delivered, bounced or complained about, without reading addresses or device tokens in tables or logs.
- **Engineer:** I send mail with `MailService::queue(new TemplateMail(...))` (or the old `MailService::sendWithFallback()`) and push with the existing helpers; retries and records come for free.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| The default configuration | the app boots outside tests | the queue connection is `database`, jobs dispatched in a transaction are queued only if it commits, `retry_after` (180 s) is above the worker's `--timeout=120` | `QueuedDeliveryTest::test_the_default_queue_is_the_database_and_tests_stay_sync` |
| A parent welcome mail | it is queued | a `SendMail` job (3 tries, 60/300/900 s backoff, encrypted payload) and a `queued` delivery row with the user id and a SHA-256 of the address, never the address | `test_a_welcome_mail_is_queued_encrypted_with_a_delivery_row` |
| The same mail | the job runs | subject, sender, recipient and body are exactly what `EmailTemplateService` renders from `email_templates`; the row is `sent` with the provider message id | `test_the_queued_welcome_mail_has_the_same_subject_and_body_as_before` |
| A mail server that refuses | two attempts run, then the last fails | one row per attempt, `failed`, error summary without addresses; `notification_delivery_abandoned` logged with ids only | `test_a_failed_attempt_is_recorded_and_retried_with_a_row_per_attempt` |
| A legacy callback mail with an attachment (contact-form reply) | it is queued | addresses, subject and attachment travel with the job | `test_a_legacy_callback_mail_keeps_its_addresses_subject_and_attachments` |
| A mail whose view cannot render, or without a valid address | it is queued | nothing is queued, `false` is returned and the failure logged as before | `test_a_mail_that_cannot_be_built_or_addressed_is_not_queued` |
| The mail settings test | it is sent | it is sent at once and reports success or failure | `test_the_mail_settings_test_is_sent_at_once_and_reports_failure` |
| Users with Android and iOS tokens | `sendSimpleNotification()` runs | one `SendPush` per device with the same FCM message as before; rows hold the user id and a hash of the token | `test_pushes_are_queued_per_device_with_the_same_messages` |
| A broadcast | `sendNotificationToTopic()` runs | one job per topic | `test_topic_pushes_queue_one_job_per_topic` |
| FCM configured | a push job runs | posted to FCM v1 with the cached OAuth token; row `sent` with FCM's message name | `test_a_push_is_sent_to_fcm_and_recorded_with_its_message_id` |
| FCM answers 503, then 404 UNREGISTERED | the job runs twice | the 503 is retried, the 404 recorded as failed and not retried | `test_fcm_server_errors_are_retried_and_refusals_are_not` |
| No FCM credentials | a push job runs | recorded as failed, not retried, the old "Access token is null" line logged | `test_a_school_without_push_configured_records_the_failure_without_retrying`, `DailyWorkflowsTest` |
| An SNS-signed SES Bounce, Complaint, Delivery or Reject | posted to `/webhook/mail/ses` | the delivery with that SES message id becomes `bounced`, `complained`, `delivered` or `failed`; a late delivery never undoes a bounce | `SesWebhookTest` (bounce, complaints and deliveries) |
| The same SNS message twice | posted again | processed once | `test_the_same_sns_message_is_processed_once` |
| A tampered message, a certificate URL off `sns.*.amazonaws.com`, another topic, or no topic configured | posted | 401 / 401 (certificate not fetched) / 403 / 401, nothing changes | `SesWebhookTest` refusals |
| A subscription request | posted | confirmed by GET on the SNS host only | `test_a_subscription_request_is_confirmed_only_on_an_sns_host` |
| The route | inspected | `webhook/mail/ses` (outside CSRF), `throttle:mail-webhook` | `test_the_route_is_rate_limited_and_outside_csrf` |
| `MAIL_MAILER=ses` / `amazon_ses` / `smtp` | the mailer is built | SES in `af-south-1` / the same SES transport / SMTP | `test_the_ses_mailer_uses_cape_town_and_smtp_still_works` |
| The outbox | the scheduler runs | `DispatchOutbox` every minute | `OutboxTest::test_the_dispatcher_is_scheduled_every_minute_and_runs_on_demand` |

## Data changes

| Table | Columns | Notes |
| --- | --- | --- |
| `notification_deliveries` (new) | `id`, `school_id` (nullable, indexed), `delivery_key` (uuid shared by the attempts of one message), `type` (`mail.<template or view>`, `push.<type>`), `channel` (`mail`, `push`), `recipient_user_id` (nullable), `recipient_hash` (SHA-256 of the lower-cased address, token or `topic:<name>`), `status` (`queued`, `sent`, `failed`, `delivered`, `bounced`, `complained`), `provider` (mailer name or `fcm`), `provider_message_id` (SES message id, Message-ID or FCM name; indexed), `attempt`, `error_summary` (500, addresses replaced by `[address]`), `queued_at`, `sent_at`, `failed_at`, `feedback_at`, timestamps | One row per attempt. Never holds a raw address or token. |
| `webhook_receipts` (new) | `id`, `provider`, `event_id`, `event_type`, `received_at`; unique (`provider`, `event_id`) | Idempotency for SNS messages; reusable by other webhooks. |
| `jobs`, `failed_jobs` | existing | Used by the database queue. |

Migration `2026_10_22_100000_create_notification_deliveries_table`; `down()` drops both tables (tested in `KernelMigrationsTest`).

## Endpoints

| Method and path | Middleware | Request | Response |
| --- | --- | --- | --- |
| `POST /webhook/mail/ses` (`webhook.mail.ses`) | `web` group (CSRF exempt for `webhook/*`), `throttle:mail-webhook` (600 a minute per address) | An SNS message as JSON (`text/plain`): `Type`, `MessageId`, `TopicArn`, `Message`, `Timestamp`, `SignatureVersion`, `Signature`, `SigningCertURL` (+ `SubscribeURL`, `Token`) | `{"error": false, "message": "Recorded" \| "Ignored" \| "Already processed" \| "Subscription confirmed"}`; 400 invalid payload, 401 bad signature or no topic configured, 403 topic not allowed, 502 confirmation failed |

No API v1 or v2 change; `openapi.yaml` is unaffected (the webhook is not part of the app API).

## Screens

None. (A delivery log page for school admins is a later phase.)

## Notification types

No new types. Every existing mail and push keeps its trigger, recipients and payload; their delivery `type` values are `mail.student_registration`, `mail.student_application_regect`, `mail.staff_registration`, `mail.web_settings.testmail`, `mail.mail`, `mail.<view>` for other legacy mails, and `push.<type>` (`push.chat`, `push.attendance`, `push.custom`, ...). The registry of notification types (`config/notification_types.php`) is Phase 1.

## Module switch

None: queueing and delivery are part of the kernel and always on.

## Configuration and secrets

| Key | Where | Default | Notes |
| --- | --- | --- | --- |
| `QUEUE_CONNECTION` | `config/queue.php` | `database` | `sync` in `phpunit.xml`. |
| `NOTIFICATION_QUEUE`, `NOTIFICATION_TRIES` | `config/platform.php` (`deliveries`) | `default`, `3` | Backoff 60/300/900 s. |
| `MAIL_MAILER` | `config/mail.php` | `.env.example`: `ses` | `smtp` keeps working; `amazon_ses` is an alias of `ses`. The `mail_mailer` settings row still overrides it. |
| `SES_ACCESS_KEY_ID`, `SES_SECRET_ACCESS_KEY` (secret) | `config/services.php` (`ses`) | fall back to `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | Secret Manager `navigateed-ses-access-key-id`, `navigateed-ses-secret-access-key`. Needed on the worker, and on the web service for the mail settings test. |
| `AWS_SES_REGION` | `config/services.php` | `af-south-1` | |
| `AWS_SES_CONFIGURATION_SET` | `config/services.php` (`ses.options`) | empty | The configuration set that publishes Delivery, Bounce, Complaint and Reject to SNS. |
| `SES_SNS_TOPIC_ARNS` | `config/services.php` (`ses_notifications.topic_arns`) | empty (webhook refuses everything) | Comma-separated. |

## Security notes

- Welcome mails carry new logins: the `SendMail` payload in `jobs` is encrypted with `APP_KEY` (`ShouldBeEncrypted`).
- No raw address or device token in `notification_deliveries` or in the new log lines; error summaries have addresses replaced. The legacy failure log of `MailService` (`Failed email details`) still includes the address, as before and as `LogRedactionTest` expects; passwords and bodies stay redacted.
- The SES webhook acts only on messages whose SNS signature verifies with a certificate fetched over HTTPS from an `sns.<region>.amazonaws.com` host, and only from configured topic ARNs (any AWS account can sign from its own topic). Subscription URLs are fetched only on SNS hosts (no SSRF). Each SNS message id is processed once. Rate limited.
- Template rendering still goes through `EmailTemplateService` (escaped values, never Blade-compiled).
- FCM uses Laravel's HTTP client with TLS verification and 5/10 s timeouts.

## Logs and metrics

| Log line | Level | Context (ids only) | Use |
| --- | --- | --- | --- |
| `notification_delivery` | info | `delivery_id`, `delivery_key`, `channel`, `type`, `status`, `provider`, `attempt`, `user_id` | One per status change: a log-based metric per channel, type and status. |
| `notification_delivery_abandoned` | error | `job`, `delivery_key`, `attempts`, `exception` | Last attempt failed (S4's `Queued job failed` line covers the job itself). |
| `notification_delivery_record_failed` | error | `step`, `delivery_key`, `exception` | The row could not be written; the message still goes. |
| `ses_notification`, `ses_notification_rejected` | info, warning | `type`, `result`, `deliveries_updated` / `status`, `reason` | Webhook outcomes. |
| `outbox_dispatch`, `outbox_listener_failed`, `outbox_event_parked` | info, error | counts / `event_id`, `type`, `attempt`, `exception` | See [kernel](kernel.md). |

## Deployment

1. Run the worker (`navigateed-worker`, `queue:work --tries=3 --timeout=120`) with this release; otherwise mail and pushes wait in `jobs`.
2. Cloud Scheduler runs `schedule:run` every minute (S4).
3. SES: enable `af-south-1`, verify `mail.navigateed.co.za` (DKIM, custom MAIL FROM, DMARC per ADR 0004), create the configuration set with an SNS destination, subscribe the topic to `https://<panel>/webhook/mail/ses`, set the env vars above, request production access.

## Self-test steps

1. Admit a learner on staging: the page answers immediately; `notification_deliveries` shows `mail.student_registration` queued, then sent with an SES message id; the parent receives the same mail as before.
2. Stop the worker, add a teacher, start the worker: the mail goes out after the restart.
3. Send to a known bouncing address (`bounce@simulator.amazonses.com`): the row becomes `bounced` within a minute.
4. Mark a learner absent: the parent's phone gets the push; a `push.attendance` row is `sent`.
5. Email Configuration > Verify with a wrong SMTP password (`MAIL_MAILER=smtp`): the page says the send failed.
6. `curl -X POST https://<panel>/webhook/mail/ses -d '{}'`: 400 (or 401 without a topic configured); nothing changes.
