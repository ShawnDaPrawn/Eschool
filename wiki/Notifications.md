This page covers how the admin panel notifies people: push notifications to the Flutter apps through Firebase Cloud Messaging (FCM HTTP v1), the in-app notification list stored in the database, and email (SMTP through Laravel's mailer, with editable templates). It lists every event in the code that sends a notification and who receives it.

Click-by-click setup is in the Docusaurus pages [Notification Settings](https://shawndaprawn.github.io/Eschool/admin-panel/notification-settings/), [Email Configuration](https://shawndaprawn.github.io/Eschool/admin-panel/email-config/), [Custom Notifications](https://shawndaprawn.github.io/Eschool/features/custom-notifications/) and, for the app side, [Integrate Firebase](https://shawndaprawn.github.io/Eschool/mobile-app/integrate-firebase/).

## Push notifications (FCM)

### Code

All push code is in [`app/Helpers/notification_helper.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Helpers/notification_helper.php), autoloaded through `composer.json` (`autoload.files`). It uses `google/apiclient` (`^2.18`) only to mint OAuth access tokens; `composer.json` keeps just the `FirebaseCloudMessaging` service from `google/apiclient-services` (`extra.google/apiclient-services` plus the `Google\Task\Composer::cleanup` pre-autoload hook). The FCM call itself is a cURL POST.

| Function | What it does |
|---|---|
| `sendSimpleNotification($user, $title, $body, $type, $image, $userinfo)` | Sends to specific users. `$user` is a list of `users.id`. Loads the `fcm_id` of those users with a non-empty token, split by `users.device_type` (`android` / `ios`), and POSTs one message per token to `https://fcm.googleapis.com/v1/projects/<project_id>/messages:send`. Android tokens get a data-only message; iOS tokens get a `notification` block (title, body) plus the same `data`. |
| `sendNotificationToTopic($title, $body, $type, $image, $userinfo)` | Sends to topics. `$userinfo` picks a pair from `all` (`allAndroid`, `allIOS`), `students`, `parents`, `teachers`; Android topics get data-only, iOS topics get `notification` + `data`. Only the custom-notification broadcast calls it, always with `all`. |
| `sendFCMCurl($url, $access_token, $Data)` | Performs the POST with `Authorization: Bearer <token>`. Logs and returns false when cURL itself fails or there is no token. It does not read the FCM response, so rejected messages (for example an expired token) are not reported. TLS verification uses cURL defaults (commit `420df6d`). |
| `getAccessToken()` | Reads the `service_account_file` setting, resolves it on the `local` disk (`storage/app`), falling back to the `public` disk for older installs, checks that `Google\Client` exists, then `setAuthConfig()`, scope `https://www.googleapis.com/auth/firebase.messaging`, `fetchAccessTokenWithAssertion()`. Each send fetches a new token (no caching). Errors are logged and return null. |

The `data` payload always contains `click_action` = `FLUTTER_NOTIFICATION_CLICK`, `title`, `body`, `type`, `image`. Type `chat` adds `sender_info` (the sender profile as a JSON string); type `fees-due` adds `child_id`. The apps route chat notifications (`type == 'chat'`) to the chat screens; see `lib/utils/notificationUtils/generalNotificationUtility.dart` in both apps.

### Firebase credentials

All three live only in the `settings` table; nothing is written to `.env` (the old `SENDER_ID` / `FIREBASE_PROJECT_ID` copies were dropped with `changeEnv()` in commit `e13ac7d`).

| Setting (`settings.type`) | Notes |
|---|---|
| `sender_id` | Required by the form; not used by the HTTP v1 sender (the line that read it is commented out). |
| `project_id` | Used in the send URL. |
| `service_account_file` | Path of the uploaded service-account JSON on the local disk, normally `firebase/service_account.json` |

The page is **System Settings > Notification Setting**: `GET fcm-settings` (`SettingController::fcm_index`, view `settings/fcm_key.blade.php`, permission `fcm-setting-create`) and `POST notification-setting` (`SettingController::notification_setting`, permission `setting-create`). The upload must be a JSON file of at most 64 KB containing `private_key` and `client_email`. It is stored with `storeAs('firebase', 'service_account.json', 'local')`, that is `storage/app/firebase/service_account.json`, which the web server does not serve. If an older install still has the file on the public disk, saving the form again moves it to the local disk and deletes the public copy. Never commit this file.

### Device tokens

| Column | Type | Set by |
|---|---|---|
| `users.fcm_id` | `string(1024)`, nullable | `fcm_id` sent with `POST /api/student/login`, `/api/parent/login`, `/api/teacher/login` (saved only after a successful login) |
| `users.device_type` | `string(128)`, `android` or `ios` | `device_type` sent with the same login calls |

There is no separate "update token" endpoint; the apps send `fcm_id` and `device_type` (from `Platform.isAndroid`) only when logging in (`lib/data/repositories/authRepository.dart`). `POST /api/logout` (`ApiController::logout`) sets `fcm_id` to `''` and deletes the current Sanctum token. A user has one token, so the last device to log in receives that user's pushes. Users whose `device_type` is neither value receive nothing.

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

- Payment pushes from the webhook are wrapped in `try`/`catch` so a failed push never undoes the payment. Other callers are inside the controller's general `try`, so an FCM exception there becomes an `error_occurred` response even though the data was saved (`compulsoryFeesPaidStore` special-cases this with "Data Stored successfully. But App push notification not send.").

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

The mail server is configured in **System Settings > Email Configuration** (`GET email-settings`, route `setting.email-config-index`, view `settings/email_configuration.blade.php`). The settings are stored only in the `settings` table; since commit `e13ac7d` nothing is written to `.env`.

| Setting (`settings.type`) | Applied onto config key at boot | Written by |
|---|---|---|
| `mail_mailer` | `mail.default` | `POST email-settings` (`SettingController::email_update`, permission `email-setting-create`); all seven fields are required. Saving also resets `email_configration_verification` to 0. |
| `mail_host`, `mail_port` (cast to int), `mail_username`, `mail_password`, `mail_encryption` | `mail.mailers.smtp.host`, `.port`, `.username`, `.password`, `.encryption` | same |
| `mail_send_from` | `mail.from.address` | same |
| `school_name` (General Settings) | `app.name`, and `mail.from.name` when that was equal to `app.name` (the `.env.example` default `MAIL_FROM_NAME="${APP_NAME}"`) | `SettingController::update` |
| `email_configration_verification` | none | Set to 1 by `POST verify-email-settings` (`setting.varify-email-config`, `SettingController::verifyEmailConfigration`) when a test email (view `mail.blade.php`, subject "Connection Verified successfully", sender name `Eschool Admin`) is sent successfully. While it is 0, the admin header shows "email configuration is not verified" to users with `email-setting-create`. |

[`App\Services\SettingsConfigService::apply()`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/SettingsConfigService.php) runs from `AppServiceProvider::boot()` on every request and console command. It loads all settings and copies each non-empty value above onto its config key, so the `.env` `MAIL_*` values (read by [`config/mail.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/mail.php)) are only a fallback for settings that are empty, and saved mail settings take effect on the next request even with `php artisan config:cache`. If the settings table cannot be read (not installed or not migrated yet) the `.env` config is kept. A `MAIL_FROM_NAME` that differs from `APP_NAME` is left alone. The same service also applies the `time_zone` setting to `app.timezone` and PHP's default timezone, and the Paystack keys (see [Payments](Payments#settings-reference)). `SettingsConfigTest` checks the overrides and fails if any code outside `config/` calls `env()`.

The form displays the effective config values (`config('mail.default')`, `config('mail.mailers.smtp.*')`, `config('mail.from.address')`), or `x` placeholders when `config('app.demo_mode')` is true. Its mailer drop-down offers `smtp`, `mailgun`, `sendmail`, `postmark` and `amazon_ses`; `config/mail.php` defines `smtp`, `ses`, `mailgun`, `postmark`, `sendmail`, `log`, `array` and `failover`, so `amazon_ses` is not a valid mailer name (the SES one is `ses`), and the form only collects SMTP-style fields. `config/mail.php` defaults: mailer `env('MAIL_MAILER', 'log')`, SMTP host `smtp.gmail.com`, port 587, encryption `tls`, and `'verify_peer' => false` on the SMTP mailer (the SMTP server's TLS certificate is not verified). Senders take the from address from `config('mail.from.address')`. See [Configuration](Configuration).

### MailService and templates

[`MailService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/MailService.php):

- `send($view, $data, $callback)` wraps `Mail::send()`, returns false on any exception and logs the error **and the full `$data`** at info level.
- `sendWithFallback(...)` calls `send()` and additionally logs the payload when it fails.
- `isEmailConfigured()` checks for SMTP username and password; nothing calls it.

Because welcome emails carry generated passwords in `$data`, a failed send writes those passwords to `storage/logs`. The log redaction added in commit `ee49250` applies to `ResponseService` error logs, not to `MailService`.

Editable templates live in the `email_tempalates` table (model `App\Models\EmailTempalate`, columns `type` unique, `subject`, `content`). [`database/seeders/EmailSeeder.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/database/seeders/EmailSeeder.php) creates three with `firstOrCreate` (so admin edits survive), and both the installer (`config/installer.php`) and the system updater run it.

| `type` | Used for | Placeholders in the seeded HTML |
|---|---|---|
| `student_registration` | Parent welcome email on admission and on accepting an online registration | `{$name}`, `{$child_name}`, `{$class_name}`, `{$username}`, `{$password}`, `{$child_grnumber}`, `{$child_password}`, `{$school_name}`, `{$school_contact}`, `{$school_email}` |
| `staff_registration` | Teacher and staff welcome email | `{$name}`, `{$role}`, `{$school_name}`, `{$email}`, `{$password}`, `{$school_contact}`, `{$school_email}` |
| `student_application_regect` | Rejected online registration | `{$name}`, `{$child_name}`, `{$class_name}`, `{$school_name}`, `{$school_contact}`, `{$school_email}` |

[`EmailTemplateService::render($template, $data)`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/EmailTemplateService.php) replaces `{$key}` or `{key}` with the HTML-escaped scalar value and leaves unknown placeholders as they are. Templates are never compiled as Blade (commit `ef7df75` stopped `Blade::render`, which let a template run PHP). The rendered HTML is passed as `body` to a thin Blade wrapper (`students/email.blade.php`, `staff/email.blade.php`, `teacher/email.blade.php`) that prints it unescaped.

The editor is **System Settings > Email Template**: `GET email-tamplate` (route name `email.Template`, `EmailController::index`, view `settings/email-template.blade.php`) and `POST email/store` (`email.store`), both requiring `email-setting-create`. `store` takes `type` = `student`, `staff` or `rejection` and `content`; the subject saved with it is fixed by the controller. The senders only read `content`; each one sets its own subject.

### Which emails are sent

| Email | Trigger | Recipients | Template / view | Subject |
|---|---|---|---|---|
| Parent welcome with credentials | `Student\StudentController::store` (admission) | Father and mother, or guardian | `student_registration` / `students.email` | `Welcome to <school_name>` |
| Parent welcome with credentials | `Student\OnlineRegistrationController::updateStatus` with status 1 (online registration accepted) | The student's parents | `student_registration` / `students.email` | as above |
| Registration rejected | `Student\OnlineRegistrationController::updateStatus` with another status (user `status` set to 2) | The student's parents | `student_application_regect` / `students.email` | `Response To Online Registration` |
| Parent welcome on bulk upload | `StudentsImport::collection()` (Students > Add Bulk Data) | Father, mother, guardian of each row | `Mail::send('students.email', ...)` directly, no template | `Welcome to <school_name>` |
| Teacher welcome | `TeacherController::store` | The teacher | `staff_registration` / `teacher.email` | `Welcome to <school_name>` |
| Staff welcome | `StaffController::store` | The staff member | `staff_registration` / `staff.email` | `Welcome to <school_name>` |
| Contact-form reply | `POST contact-us/reply/{id}` (`WebSettingController::reply`, permission `contact-us`) | The `email` posted with the reply | view `web_settings/testmail.blade.php`, attachments allowed | `Reply to Your Query` |
| Mail settings test | `POST verify-email-settings` | The address entered | view `mail.blade.php` | `Connection Verified successfully` |
| Password reset link | Web `POST password/email` (`ForgotPasswordController`, `throttle:login`) and API `POST /api/forgot-password` (`ApiController::forgotPassword`, `throttle:login`) | The account email | Laravel's default reset notification | Laravel default |

Sender: the student/parent emails in `Student\StudentController` and `Student\OnlineRegistrationController`, and the contact-us reply call `from(config('mail.from.address'), <school name>)`; the teacher, staff and bulk-import emails do not set a sender, so Laravel uses `mail.from.address` and `mail.from.name` (which normally follows the school name, see above).

Generated passwords are the person's date of birth as `ddmmyyyy` (`str_replace('-', '', date('d-m-Y', ...))`); the student's GR number is used as the login "email". `POST /api/student/forgot-password` sends no email: when the GR number and date of birth match it sets `users.reset_request = 1`, and staff reset the password from **Students > Students Reset Password** (`reset-password-list`, `student-change-password`, permission `student-reset-password`). The API answer is the same whether or not an account matched.

Known problems, as written:

- `StudentsImport` passes no `body` to `students.email`, and that view prints `$body` for `application_accept` emails, so rendering is expected to fail with an undefined-variable error; the import runs in a DB transaction that rolls back on any exception.
- `TeacherController::store`, when it restores a soft-deleted user with the same email, sends `teacher.email` without `body`; `MailService` catches the resulting error and logs the payload.
- The contact-us reply reports success even when sending failed (the return value of `sendWithFallback()` is ignored).
- Welcome emails for parents use the `teacher_app_link` / `teacher_ios_app_link` settings as their `android_app` / `ios_app` values.

## See also

- [Chat](Chat)
- [Payments](Payments#payment-confirmation-webhooks)
- [Configuration](Configuration)
- [Mobile-App-Configuration](Mobile-App-Configuration)
- [API-Overview](API-Overview)
- [Admin-Modules](Admin-Modules)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)
