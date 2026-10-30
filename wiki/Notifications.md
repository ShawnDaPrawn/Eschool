This page covers how the admin panel notifies people: push notifications to the Flutter apps through Firebase Cloud Messaging (FCM HTTP v1), the in-app notification list stored in the database, and email (Amazon SES or SMTP through Laravel's mailer, with editable templates). Since Phase 0 mail and pushes are sent by queued jobs on the worker, not inside the web request, and every attempt is recorded in `notification_deliveries`; SES reports bounces, complaints and deliveries back through a webhook. It lists every event in the code that sends a notification and who receives it.

## School and personal choices

Settings → Notification rules provides a per-portal channel grid. Registered
platform safety requirements stay locked; optional channels support mandatory,
default on, default off and not offered. The shared app screen reads/writes
`/api/v2/me/notification-preferences`, and context contains effective school rules.
The [delivery specification](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/specs/platform/notification-rules.md) describes precedence and migration.

Business producers use a transactional outbox and per-recipient fanout. Content
no longer goes to audience topics: individual preferences and current membership
are checked for inbox, push and email. Topics retain silent context invalidation.
New inbox entries need explicit recipient access; historical broadcasts remain
readable. Push/mail queue payloads are encrypted. Workers recheck rules, access
and destination before transport; suppressed jobs contact no provider.

Under-18 handle requests notify verified approving guardians through the same
mandatory rules and link to `/handles`. Both apps recognise `/handles` and `/chat`.
Real provider/device verification remains pending staging acceptance.


Click-by-click setup is in the Docusaurus pages [Notification Settings](https://shawndaprawn.github.io/Eschool/admin-panel/notification-settings/), [Email Configuration](https://shawndaprawn.github.io/Eschool/admin-panel/email-config/), [Custom Notifications](https://shawndaprawn.github.io/Eschool/features/custom-notifications/) and, for the app side, [Integrate Firebase](https://shawndaprawn.github.io/Eschool/mobile-app/integrate-firebase/).

## Push notifications (FCM)

### Code

The helpers are in [`app/Helpers/notification_helper.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Helpers/notification_helper.php), autoloaded through `composer.json` (`autoload.files`). They build the FCM messages and queue them; the [`SendPush`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Jobs/Platform/SendPush.php) job sends each one on the worker through `App\Services\Platform\Notifications\FcmClient` (Laravel's HTTP client, 5 s connect and 10 s timeouts). `google/apiclient` (`^2.18`) is used only to mint OAuth access tokens; `composer.json` keeps just the `FirebaseCloudMessaging` service from `google/apiclient-services`.

| Function | What it does |
|---|---|
| `sendSimpleNotification($user, $title, $body, $type, $image, $userinfo)` | Sends to specific users. `$user` is a list of `users.id`. Loads those users with a non-empty `fcm_id` and `device_type` `android` or `ios` and queues one `SendPush` per device (`PushQueue::toDevice()`), with a `notification_deliveries` row each. Android tokens get a data-only message; iOS tokens get a `notification` block (title, body) plus the same `data`. |
| `sendNotificationToTopic($title, $body, $type, $image, $userinfo)` | Sends to topics. `$userinfo` picks a pair from `all` (`allAndroid`, `allIOS`), `students`, `parents`, `teachers`; one `SendPush` per topic. Android topics get data-only, iOS topics get `notification` + `data`. Only the custom-notification broadcast calls it, always with `all`. |
| `getAccessToken()` | Reads the `service_account_file` setting, resolves it on the `local` disk (`storage/app`), falling back to the `public` disk for older installs, checks that `Google\Client` exists, then `setAuthConfig()`, scope `https://www.googleapis.com/auth/firebase.messaging`, `fetchAccessTokenWithAssertion()`. The token is cached for 50 minutes per service-account file. Errors are logged and return null. |

`SendPush` reads FCM's answer: a 2xx records the delivery `sent` with FCM's message name; a 429, a 5xx or a network error is retried (3 tries, 60/300/900 s backoff); any other refusal (an expired or unregistered token, a bad message) is recorded `failed` with the status and FCM error code and not retried. Without credentials it logs "Cannot send FCM notification: Access token is null", as the helpers always did, and records the delivery `failed` without retrying. A push never fails the action that triggered it.

The `data` payload always contains `click_action` = `FLUTTER_NOTIFICATION_CLICK`, `title`, `body`, `type`, `image`. Type `chat` adds `sender_info` (the sender profile as a JSON string); type `fees-due` adds `child_id`. The apps route chat notifications (`type == 'chat'`) to the chat screens; see `lib/utils/notificationUtils/generalNotificationUtility.dart` in both apps.

### Firebase credentials

All three live only in the `settings` table; nothing is written to `.env` (the old `SENDER_ID` / `FIREBASE_PROJECT_ID` copies were dropped with `changeEnv()` in commit `e13ac7d`).

| Setting (`settings.type`) | Notes |
|---|---|
| `sender_id` | Required by the form; not used by the HTTP v1 sender (the line that read it is commented out). |
| `project_id` | Used in the send URL. For the apps in this repo, set it to `navigateed-d8c06`. The form also requires `sender_id`; the HTTP v1 sender ignores it, and the matching sender id is `972068206844`. |
| `service_account_file` | Path of the uploaded service-account JSON on the local disk, normally `firebase/service_account.json` |

The page is **System Settings > Notification Setting**: `GET fcm-settings` (`SettingController::fcm_index`, view `settings/fcm_key.blade.php`, permission `fcm-setting-create`) and `POST notification-setting` (`SettingController::notification_setting`, permission `setting-create`). The upload must be a JSON file of at most 64 KB containing `private_key` and `client_email`. It is stored with `storeAs('firebase', 'service_account.json', 'local')`, that is `storage/app/firebase/service_account.json`, which the web server does not serve. If an older install still has the file on the public disk, saving the form again moves it to the local disk and deletes the public copy. Never commit this file.

### Device tokens

Each signed-in device has its own push registration in `user_devices` (one row per FCM token: the person, `android` or `ios`, the app, and the sign-in token that registered it; the FCM token is encrypted). `PushDevices` lists a person's active devices and `Notifier` queues one `SendPush` per device, each with its own `notification_deliveries` row. A device stops receiving pushes when it signs out, when its sign-in token is revoked (password change, deactivation), when FCM reports the token `UNREGISTERED`, or when another account signs in on the same phone. Specification: [push devices](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/specs/platform/push-devices.md).

The apps still send `fcm_id` and `device_type` with the sign-in calls (`POST /api/student/login`, `/api/parent/login`, `/api/teacher/login`, the facilitator and vendor sign-ins and guardian invitation acceptance); `PortalSession::signIn` registers the device. `users.fcm_id` / `device_type` remain a compatibility projection of the most recent registration. A token-refresh endpoint is a planned follow-up.

The apps also subscribe to topics: the student/parent app to `studentsAndroid`/`studentsIOS` or `parentsAndroid`/`parentsIOS` plus `allAndroid`/`allIOS`; the teacher app to `teachersAndroid`/`teachersIOS` plus `allAndroid`/`allIOS`.

## Stored notifications

| Model (table) | Columns |
|---|---|
| `Notification` (`notifications`) | `title` (128), `type` (128), `message` (128), `send_to` (int), `image` (512, nullable), `date`, `is_custom` (1 custom, 0 generated), `session_year_id`, timestamps, soft deletes. `users()` is a `belongsToMany` through `user_notifications`. |
| `UserNotification` (`user_notifications`) | `notification_id` (FK, cascade), `user_id` (FK, cascade). One row per targeted user. |

`send_to` values in use: 1 everyone (custom broadcast), 2 specific users, 3 and 4 are used by generated notifications (see the trigger table). The column is a plain integer; the code does not define constants for it.

The `title` and `message` columns are 128 characters and the MySQL connection is configured with `'strict' => false` (`config/database.php`), so longer text is cut to 128 characters in the stored copy while the push carries the full text.

### In-app lists

| Endpoint | Method |
|---|---|
| `GET /api/student/get-notification` | `Student\NotificationController::getNotifications` |
| `GET /api/parent/get-notification` | `Parent\NotificationController::getNotifications` |
| `GET /api/teacher/get-notification` | `Teacher\NotificationController::getNotifications` |

All three return `Notification` rows of the current session year (`session_year` setting) where the id is in the user's `user_notifications` **or** `send_to` is 1 or 4, newest first, Laravel-paginated (15 per page, `?page=`). Notifications without `user_notifications` rows and with another `send_to` (for example the fee reminder below) never appear in any list.

## What triggers a notification

"Row" means a `notifications` row plus `user_notifications` for the recipients. "Push only" means `sendSimpleNotification()` without any stored row.

| Event | Where | Recipients | Stored | Push `type` |
|---|---|---|---|---|
| Custom notification, "All Users" chosen | `NotificationController::store` | Topics `allAndroid`, `allIOS` | `send_to` 1, `is_custom` 1, no user rows | `custom` |
| Custom notification, specific users | `NotificationController::store` | The selected users | `send_to` 2, `is_custom` 1, user rows | `custom` |
| Announcement (admin panel, "noticeboard") | `AnnouncementController::store` / `update` | Every student user (`Students::select('user_id')`) | none | the `set_data` value (`noticeboard`) |
| Announcement (admin panel, teacher, class section) | `AnnouncementController::store` / `update` | Students of the class section | none | `class_section` |
| Announcement (teacher app) | `Teacher\AnnouncementController::sendAnnouncement` / `updateAnnouncement` | Students of the class section | none | `class_section` |
| New / updated assignment | `AssignmentController::store` / `update`, `Teacher\AssignmentController::createAssignment` / `updateAssignment` | Students of the class section (for a new assignment in an elective subject, only those who chose it) | `send_to` 3, user rows | `assignment` |
| Assignment submission accepted / rejected | `AssignmentController::updateAssignmentSubmission`, `Teacher\AssignmentController::updateAssignmentSubmission` | The student | `send_to` 3, user rows | `assignment` |
| Assignment submitted | `Student\AssignmentController::submitAssignment` | Subject teachers of that subject and class section | `send_to` 2, user rows | `assignment_submission` |
| Student marked absent (admin panel, attendance sheet upload or teacher app) | `AttendanceController::store`, `AttendanceImport`, `Teacher\AttendanceController::submitAttendance`, all through `AbsenceAlertService::notify()` | Father, mother and guardian users of each student newly marked absent (not again when the day is saved again) | `send_to` 2, user rows | `attendance` |
| Student leave applied | `Student\LeaveController::applyLeave`, `Parent\LeaveController::applyLeave` | Class teachers of the student's class section | `send_to` 3, user rows | `leave` |
| Student leave approved / rejected | `Leave\StudentLeaveController::studentLeaveStatusUpdate`, `Teacher\LeaveController::studentLeaveStatusUpdate` | The student's parents | `send_to` 3, user rows | `leave` |
| Chat message | `sendMessage` in the three API controllers | The receiver | none | `chat` (with `sender_info`) |
| Online payment recorded / failed (webhook) | `WebhookController::notifyParents` | The transaction's parent | `send_to` 2, user row | `online` |
| App reports a failed payment | `Student\FeesController::failPaymentTransactionStatus` (student user), `Parent\FeesController::failPaymentTransactionStatus` (parent user) | as listed | `send_to` 2, user row | `online` |
| Cash / cheque payment recorded by staff | `Fees\PaymentController::compulsoryFeesPaidStore`, `optionalFeesPaidStore` | Father's (else guardian's) user | `send_to` 2, user row | `Online` |
| Fee reminder from the student app | `GET /api/student/send-fee-notification` (`Student\FeesController::sendFeeNotification`) | The student's parents | `send_to` 2, no user rows (so not listed anywhere) | `fees-due` (with `child_id`) |

Exams, online exams, results, events, holidays and timetables send no notifications in the current code.

Notes on specific triggers, as written:

- Pushes are queued, so neither a slow nor a failing FCM affects the triggering request any more; with the `sync` queue (tests, local development) a failed send is logged and swallowed. `compulsoryFeesPaidStore` still special-cases a push exception with "Data Stored successfully. But App push notification not send.", which can no longer happen.

## Custom notifications (admin panel)

**Custom Notifications** in the sidebar (permission `notification-create` to see it):

| Route | Method | Permission |
|---|---|---|
| `GET notifications` (`notifications.index`) | `index` (view `notification/index.blade.php`) | `notification-list` |
| `GET notifications/recipients/list` (`notifications.recipients`) | `getRecipients`: Bootstrap-table JSON of users with role Student, Parent or Teacher (`recipient_type` = `students`/`parents`/`teachers`); students and parents are limited to those with a session in the current session year; searchable by name and email | `notification-create` |
| `POST notifications` (`notifications.store`) | `store`: `send_to[]` from 1 (all users), 3 (students), 4 (parents), 5 (teachers), `title`, `message`, optional `image`, `user_id[]` | `notification-create` |
| `GET notifications/{id}` (`notifications.show`) | `show`: list JSON of `is_custom = 1` rows for a session year | `notification-list` |
| `DELETE notifications/{id}` (`notifications.destroy`) | `destroy` (soft delete) | `notification-delete` |

`send_to` options 3, 4 and 5 only choose which recipient picker the page shows; on save the notification is a broadcast (`send_to` 1) if "All Users" is among the choices, otherwise it targets the picked `user_id[]` (`send_to` 2) and at least one user is required. An image is compressed by the `resizeImage()` helper (Intervention Image, quality 50) and stored under `storage/app/public/notifications/`; the push carries its `asset('storage/...')` URL.

## Email

### Configuration

In production the mailer is Amazon SES (`MAIL_MAILER=ses`, see [SES bounces, complaints and deliveries](#ses-bounces-complaints-and-deliveries)). Schools that self-host can use SMTP. The mail server is configured in **System Settings > Email Configuration** (`GET email-settings`, route `setting.email-config-index`, view `settings/email_configuration.blade.php`). The settings are stored only in the `settings` table; since commit `e13ac7d` nothing is written to `.env`.

| Setting (`settings.type`) | Applied onto config key at boot | Written by |
|---|---|---|
| `mail_mailer` | `mail.default` | `POST email-settings` (`SettingController::email_update`, permission `email-setting-create`); all seven fields are required. Saving also resets `email_configration_verification` to 0. |
| `mail_host`, `mail_port` (cast to int), `mail_username`, `mail_password`, `mail_encryption` | `mail.mailers.smtp.host`, `.port`, `.username`, `.password`, `.encryption` | same |
| `mail_send_from` | `mail.from.address` | same |
| `school_name` (General Settings) | `app.name`, and `mail.from.name` when that was equal to `app.name` (the `.env.example` default `MAIL_FROM_NAME="${APP_NAME}"`) | `SettingController::update` |
| `email_configration_verification` | none | Set to 1 by `POST verify-email-settings` (`setting.varify-email-config`, `SettingController::verifyEmailConfigration`) when a test email (view `mail.blade.php`, subject "Connection Verified successfully", sender name `Eschool Admin`) is sent successfully; this one mail is sent at once (`MailService::sendNow()`), not queued. While it is 0, the admin header shows "email configuration is not verified" to users with `email-setting-create`. |

[`App\Services\SettingsConfigService::apply()`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/SettingsConfigService.php) runs from `AppServiceProvider::boot()` on every request and console command. It loads all settings and copies each non-empty value above onto its config key, so the `.env` `MAIL_*` values (read by [`config/mail.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/mail.php)) are only a fallback for settings that are empty, and saved mail settings take effect on the next request even with `php artisan config:cache`. If the settings table cannot be read (not installed or not migrated yet) the `.env` config is kept. A `MAIL_FROM_NAME` that differs from `APP_NAME` is left alone. The same service also applies the `time_zone` setting to `app.timezone` and PHP's default timezone, and the Paystack keys (see [Payments](Payments#settings-reference)). `SettingsConfigTest` checks the overrides and fails if any code outside `config/` calls `env()`.

The form displays the effective config values (`config('mail.default')`, `config('mail.mailers.smtp.*')`, `config('mail.from.address')`), or `x` placeholders when `config('app.demo_mode')` is true. Its mailer drop-down offers `smtp`, `mailgun`, `sendmail`, `postmark` and `amazon_ses`; `config/mail.php` defines `smtp`, `ses` (and `amazon_ses`, the same SES transport), `mailgun`, `postmark`, `sendmail`, `log`, `array` and `failover`. The form only collects SMTP-style fields; SES takes its keys from the environment. `config/mail.php` defaults: mailer `env('MAIL_MAILER', 'log')`, SMTP host `smtp.gmail.com`, port 587, encryption `tls`, and `'verify_peer' => false` on the SMTP mailer (the SMTP server's TLS certificate is not verified). Senders take the from address from `config('mail.from.address')`. See [Configuration](Configuration).

### MailService and templates

[`MailService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/MailService.php) queues mail; the [`SendMail`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Jobs/Platform/SendMail.php) job sends it on the worker (3 tries, 60/300/900 s backoff; the queued payload is encrypted because welcome mails carry new logins).

- `queue(Mailable $mail, ?int $recipientUserId)` renders the mail once (a template or view error is caught here, logged and `false` returned, so nothing broken is queued), checks the recipient address, records a `queued` delivery and dispatches `SendMail`.
- `send($view, $data, $callback)` and `sendWithFallback(...)` keep their old signatures: the callback runs against a recorder (`App\Mail\Platform\MessageRecorder`) and its addresses, subject and attachments are queued with the view as a `ViewMail` (attachment contents travel with the job, since uploads are temporary). They return `true` once queued, `false` when the mail could not be built. A callback that uses anything else is sent at once, the old way.
- `sendNow($view, $data, $callback)` sends on the request and says whether the mailer accepted it; only the mail settings test uses it.
- Failures are logged as before ("Email sending failed: ..." and "Failed email details" with the data redacted: `body` and password fields never reach the log).

Editable templates live in the `email_tempalates` table (model `App\Models\EmailTemplate`, columns `type` unique, `subject`, `content`). [`database/seeders/EmailSeeder.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/database/seeders/EmailSeeder.php) creates three with `firstOrCreate` (so admin edits survive), and both the installer (`config/installer.php`) and the system updater run it.

| `type` | Used for | Placeholders in the seeded HTML |
|---|---|---|
| `student_registration` | Parent welcome email on admission and on accepting an online registration | `{$name}`, `{$child_name}`, `{$class_name}`, `{$username}`, `{$password}`, `{$child_grnumber}`, `{$child_password}`, `{$school_name}`, `{$school_contact}`, `{$school_email}` |
| `staff_registration` | Teacher and staff welcome email | `{$name}`, `{$role}`, `{$school_name}`, `{$email}`, `{$password}`, `{$school_contact}`, `{$school_email}` |
| `student_application_regect` | Rejected online registration | `{$name}`, `{$child_name}`, `{$class_name}`, `{$school_name}`, `{$school_contact}`, `{$school_email}` |

[`EmailTemplateService::render($template, $data)`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/EmailTemplateService.php) replaces `{$key}` or `{key}` with the HTML-escaped scalar value and leaves unknown placeholders as they are. Templates are never compiled as Blade (commit `ef7df75` stopped `Blade::render`, which let a template run PHP). The template mails are [`TemplateMail`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Mail/Platform/TemplateMail.php) Mailables (`parentWelcome()`, `registrationRejected()`, `staffWelcome()`): when built they read the template by `type` and render it through `EmailTemplateService`, and pass the HTML as `body` to the same thin Blade wrapper as before (`students/email.blade.php`, `staff/email.blade.php`, `teacher/email.blade.php`), which prints it unescaped. Subjects and senders are unchanged.

The editor is **System Settings > Email Template**: `GET email-tamplate` (route name `email.Template`, `EmailController::index`, view `settings/email-template.blade.php`) and `POST email/store` (`email.store`), both requiring `email-setting-create`. `store` takes `type` = `student`, `staff` or `rejection` and `content`; the subject saved with it is fixed by the controller. The senders only read `content`; each one sets its own subject.

### Which emails are sent

| Email | Trigger | Recipients | Template / view | Subject |
|---|---|---|---|---|
| Parent welcome with credentials | `Student\StudentController::store` (admission) | Father and mother, or guardian | `student_registration` / `students.email` | `Welcome to <school_name>` |
| Parent welcome with credentials | `Student\OnlineRegistrationController::updateStatus` with status 1 (online registration accepted) | The student's parents | `student_registration` / `students.email` | as above |
| Registration rejected | `Student\OnlineRegistrationController::updateStatus` with another status (user `status` set to 2) | The student's parents | `student_application_regect` / `students.email` | `Response To Online Registration` |
| Parent welcome on bulk upload | `StudentsImport::collection()` (Students > Add Bulk Data) | Father, mother, guardian of each row | `student_registration` / `students.email` | `Welcome to <school_name>` |
| Teacher welcome | `TeacherController::store` | The teacher | `staff_registration` / `teacher.email` | `Welcome to <school_name>` |
| Staff welcome | `StaffController::store` | The staff member | `staff_registration` / `staff.email` | `Welcome to <school_name>` |
| Contact-form reply | `POST contact-us/reply/{id}` (`WebSettingController::reply`, permission `contact-us`) | The `email` posted with the reply | view `web_settings/testmail.blade.php`, attachments allowed | `Reply to Your Query` |
| Mail settings test | `POST verify-email-settings` | The address entered | view `mail.blade.php` | `Connection Verified successfully` |
| Password reset link | Web `POST password/email` (`ForgotPasswordController`, `throttle:login`) and API `POST /api/forgot-password` (`ApiController::forgotPassword`, `throttle:login`) | The account email | Laravel's default reset notification | Laravel default |

Every mail in the table except the mail settings test is queued (see [Queued delivery](#queued-delivery-and-notification_deliveries)).

Sender: the student/parent emails in `Student\StudentController` and `Student\OnlineRegistrationController`, and the contact-us reply call `from(config('mail.from.address'), <school name>)`; the teacher, staff and bulk-import emails do not set a sender, so Laravel uses `mail.from.address` and `mail.from.name` (which normally follows the school name, see above).

Generated passwords are the person's date of birth as `ddmmyyyy` (`str_replace('-', '', date('d-m-Y', ...))`); the student's GR number is used as the login "email". `POST /api/student/forgot-password` sends no email: when the GR number and date of birth match it sets `users.reset_request = 1`, and staff reset the password from **Students > Students Reset Password** (`reset-password-list`, `student-change-password`, permission `student-reset-password`). The API answer is the same whether or not an account matched.

Known problems, as written:

- `TeacherController::store`, when it restores a soft-deleted user with the same email, sends `teacher.email` without `body`; `MailService` catches the render error before queueing and logs the (redacted) payload.
- The contact-us reply reports success even when sending fails; it now means "queued", and a failed send shows in `notification_deliveries`.
- Welcome emails for parents use the `teacher_app_link` / `teacher_ios_app_link` settings as their `android_app` / `ios_app` values.

## Queued delivery and notification_deliveries

Every mail and push is a job on the database queue (`QUEUE_CONNECTION=database`, worker `navigateed-worker`; `sync` in tests) and has one `notification_deliveries` row per attempt:

| Column | Meaning |
|---|---|
| `delivery_key` | UUID shared by the attempts of one message |
| `type` | `mail.<template type or view>` (for example `mail.student_registration`, `mail.web_settings.testmail`) or `push.<type>` (`push.chat`, `push.attendance`, ...) |
| `channel` | `mail` or `push` |
| `recipient_user_id`, `recipient_hash` | the user where known, and SHA-256 of the lower-cased address, device token or `topic:<name>`: raw addresses and tokens are never stored |
| `status` | `queued`, `sent`, `failed`, then from the provider `delivered`, `bounced` or `complained` |
| `provider`, `provider_message_id` | mailer name (`ses`, `smtp`, ...) or `fcm`; SES's message id, the Message-ID, or FCM's message name |
| `attempt`, `error_summary` | 1, 2, 3; a short error with addresses replaced by `[address]` |
| `queued_at`, `sent_at`, `failed_at`, `feedback_at` | when each happened |

Each status change writes one `notification_delivery` log line (ids, channel, type, status, provider, attempt) for log-based metrics; a message given up after its last attempt writes `notification_delivery_abandoned`. Settings: `config/platform.php` (`deliveries`: queue, tries, backoff). Spec: [queue-and-delivery](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/specs/platform/queue-and-delivery.md).

## SES bounces, complaints and deliveries

Mail goes through Amazon SES in `af-south-1` when `MAIL_MAILER=ses` ([ADR 0004](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/adr/0004-transactional-email.md)); SMTP keeps working with `MAIL_MAILER=smtp`. An SES configuration set publishes Delivery, Bounce, Complaint and Reject events to an SNS topic subscribed to `POST /webhook/mail/ses` ([MailWebhookController](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Http/Controllers/Webhooks/MailWebhookController.php), `App\Services\Platform\Notifications\SesNotifications`):

- outside CSRF like the payment webhooks, `throttle:mail-webhook` (600 a minute per address);
- the SNS signature is verified (`aws/aws-php-sns-message-validator`) against a certificate fetched over HTTPS from an `sns.<region>.amazonaws.com` host and cached for a day;
- only topics listed in `SES_SNS_TOPIC_ARNS` are accepted (anyone can sign from their own topic); with none configured every call answers 401;
- a `SubscriptionConfirmation` is confirmed by fetching its `SubscribeURL`, on SNS hosts only;
- Delivery, Bounce, Complaint and Reject set the delivery with the quoted SES message id to `delivered`, `bounced` (`Bounce: Permanent/General`), `complained` (`Complaint: abuse`) or `failed` (`Reject: <reason>`); a late delivery notice never undoes a bounce or complaint;
- each SNS `MessageId` is processed once (`webhook_receipts`).

Hard bounces and complaints are also suppressed by SES's account-level suppression list. Showing undeliverable addresses to schools is a later phase.

## See also

- [Database-Schema](Database-Schema)
- [Chat](Chat)
- [Payments](Payments#payment-confirmation-webhooks)
- [Configuration](Configuration)
- [Mobile-App-Configuration](Mobile-App-Configuration)
- [API-Overview](API-Overview)
- [Admin-Panel-Overview](Admin-Panel-Overview)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)


Academic result release records a transactional `academics.marks_released` event.
Its scoped listener creates one generic notice per release/learner and resolves
current guardian IDs, then calls Notifier. Retries reuse the notice source key;
Notifier enforces membership, modules, preferences and per-channel delivery claims.
Notification bodies contain no scores or child names; opening the marks link
requires current own-child authorization. Draft, capture and recompute events
do not publish results or send family notices.


## Approved school messages

Communication → School messages provides standard/custom audiences, role approval,
school-local scheduling and private read acknowledgements. It uses
`communication.message` and `communication.approval_requested`; direct chat keeps
`comms.message`. The selected channels still respect existing school/personal
choices. Recovery resumes durable batches after worker interruption. Generic
notices carry no private subject/body; access, current guardian rights, publication
identity and withdrawal are checked before transport. See the
[operator guide](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/specs/communication/operations.md)
and [school messages guide](https://shawndaprawn.github.io/Eschool/features/school-messages/).
