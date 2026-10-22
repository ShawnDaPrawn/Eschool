# Security fixes in the seed (Phase 0, feature 0.7)

Phase 0, feature 0.7. Owner role: Backend (security). Work stream S6. Build Bible section 5.1 (security baseline) and section 6, build step 7. What the users see: stronger passwords are asked for, a deactivated teacher is signed out, new learners' passwords are set by their guardians through a link instead of being their date of birth, and the school can print a sign-in set-up sheet. Everything else is underneath.

## What changes

| Area | Before | After |
| --- | --- | --- |
| Password rule | `min:8` on some forms, none on the emailed reset page | `Password::min(10)->letters()->numbers()->uncompromised()`, set once as `Password::defaults()` (`App\Services\Platform\Identity\PasswordPolicy`), on every endpoint where a person chooses a password |
| Teacher status | `users.status` ignored for teachers | checked at the teacher app and panel sign-in; `EnsureTeacherActive` (`teacher.active`) on both teacher API groups; Deactivate / Activate on the teacher list |
| App tokens | named after the person, never revoked at sign-in | named `student-app`, `parent-app`, `teacher-app` (`AppTokens`); see the token rule below |
| CORS | every origin | the panel (`APP_URL`) and the web portals only; cookies for Sanctum's cookie mode |
| Web portal sign-in (S9, self-test defect D1) | the portals could not sign in from their own origin: the panel's web group encrypted cookies twice, so the API could not read the session `/sanctum/csrf-cookie` started (419), and sign-in issued only tokens (401 after it) | a portal sign-in (a request from a stateful origin) logs the `web` guard in on a regenerated session and answers `token: ""`; a refused one keeps no session; logout ends the session; Sanctum's `AuthenticateSession` ends it when the password changes (`PortalSession`, `portal.sign-in`) |
| Uploaded files (S9, defect D2) | `/storage` sent no CORS headers, so the portals could not show pictures or PDFs | Apache (`docker/storage-cors.conf`) and `server.php` answer `GET`/`HEAD` from the CORS origins with `Access-Control-Allow-Origin` and `Vary: Origin` |
| Learner passwords | date of birth (`ddmmyyyy`), mailed to the parents; a staff reset put it back | a random password nobody sees; each guardian is emailed a signed, expiring, single-use link to choose it; the school can print a one-time set-up code; existing date-of-birth passwords are flagged for a forced change |
| Uploads | `UploadService` read a missing `school_id`; client file names stored as sent | stored under `schools/{id}/` (`CurrentSchool`); every client name through `FileNames::sanitize()`; wrong content and unusable names refused |
| Content security policy | none | report-only policy on every HTML page, `POST /csp-report` |
| Secrets | in `settings` rows, printed back into the settings pages | read from the environment (Secret Manager) first; the row only a fallback; `secrets:audit-settings --purge` |
| Request logs | every field value of a failing request | method, path, field names, id values and the user id; every other value `[omitted]` |

### The token rule

- Signing in to an app revokes the account's earlier tokens for that same app, so an account is signed in on one device per app. Tokens of the account's other apps are left alone: a teacher who is also a parent stays signed in to the teacher app when signing in to the parent side.
- Tokens issued before tokens carried an app name are treated as this app's when the account can use only this app; otherwise they expire (30 days).
- Signing out revokes the account's tokens for that app.
- A web portal in cookie mode holds a session, not a token: signing in or out of a portal leaves the phone apps' tokens (and their FCM id) alone, and signing out ends the session.
- Changing the password in an app revokes every other token of the account; the device that made the change stays signed in. Changing it in the panel, a reset by email, deactivating a teacher and the learner set-up revoke all of them.

## User stories

- **School admin:** I deactivate a teacher who left; they are signed out of the app and the panel at once and cannot sign in until I activate them again.
- **School admin:** when I add a learner (one by one, by import, or by accepting an online application) the guardians get an email with a link to set the learner's password; if a family has no email, I print a sign-in sheet with a one-time code.
- **School admin:** a learner who forgot their password is reset from the reset list; the guardians get a new link and I can print a sheet.
- **Parent or guardian:** I open the link from the school's email, choose a password for my child, and the link stops working.
- **Learner:** I enter my GR number and the code from the printed sheet on the set-up page, choose a password and sign in.
- **Learner whose password is still their date of birth:** the app keeps signing me in; apps that support it then ask me to choose a new password.
- **Teacher, parent, learner:** a password like `password1` or one from a known breach is refused, with the rule shown.
- **Deployer:** I put the secrets in Secret Manager, run `php artisan secrets:audit-settings --purge`, and no key is left in the database.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| Any password endpoint (panel profile, panel reset, app change-password for learner, parent and teacher, learner set-up by link and by code) | a password that is too short, letters only, numbers only, or found in a breach is sent | refused (validation error), password unchanged; a strong one is accepted | `Platform/PasswordPolicyTest`, `Platform/LearnerInvitationTest` |
| The breach check | a password is checked | only the first five characters of its SHA-1 go to the API | `PasswordPolicyTest::test_the_breach_check_sends_only_a_hash_prefix` |
| A teacher with `users.status` 0 | they sign in to the app or the panel | refused with "deactivated", no token | `Platform/TeacherStatusTest` |
| A teacher deactivated after signing in | they call any teacher route with the old token, or open a panel page | 401 with code 401 (apps sign out); panel signs out | `TeacherStatusTest` |
| A teacher (no `teacher-edit`) | they change a teacher's status | refused; the Super Admin can, and deactivation revokes the tokens | `TeacherStatusTest` |
| Any app account | it signs in twice | the first token stops working; one token named for the app remains | `Platform/AppTokensTest` |
| A teacher who is also a parent | they sign in to the parent side, or sign out of the teacher app | the other app stays signed in | `AppTokensTest` |
| A password change | in the app / in the panel | every other token / every token is revoked | `AppTokensTest` |
| A browser on a portal origin / on any other origin | it calls the API | CORS headers / none; native apps without an Origin keep working | `Platform/CorsTest` |
| A learner, parent or teacher on their portal origin | `GET /sanctum/csrf-cookie`, then sign-in with `X-XSRF-TOKEN` | signed in with a new session, `token: ""`, no token row; `/api/v2/me/context` and a v1 read work with the session; the role groups and `checkChild` apply | `Platform/PortalSignInTest` |
| A portal | signs out / its sign-in is refused / the password changes elsewhere | the session ends (401 after) / no session / 401 on the next request; the phone's token and FCM id stay | `PortalSignInTest` |
| A portal write without `X-XSRF-TOKEN` | it is sent | 419 | `PortalSignInTest::test_portal_writes_need_the_csrf_token` |
| A phone app (no Origin) / an unknown origin | signs in | a token as before, no cookie / not treated as a portal | `PortalSignInTest` |
| A portal origin / any other origin | fetches a file under `/storage` | `Access-Control-Allow-Origin` for `GET` and `HEAD` / none; `Vary: Origin` always | `Platform/StorageCorsTest`, `docker/tests/storage-cors.sh` (real Apache, CI) |
| A school admin | adds a learner with two parents | the learner's password is not the date of birth; each parent gets a link; the welcome mail carries no password | `Platform/LearnerInvitationTest` |
| A guardian | uses the link with a strong password | the password is set; the same link and the other guardian's link no longer work | `LearnerInvitationTest` |
| A link | after 7 days, with another token, or with a changed expiry | "This link no longer works" | `LearnerInvitationTest` |
| The school | prints a sheet twice | the older code stops working; the newer works once, case and dash ignored | `LearnerInvitationTest` |
| A teacher without student permissions | prints a sheet or sends links | refused | `LearnerInvitationTest` |
| A learner who asked for a reset | staff reset them | random password, links to the guardians, tokens revoked, sheet offered | `LearnerInvitationTest`, `SecurityTest::test_the_student_password_reset_only_resets_students_and_never_to_a_known_value` |
| An import or an accepted online application | it completes | the guardians get links (after the whole import) | `LearnerInvitationTest`, `DailyWorkflowsTest` |
| Learners with date-of-birth passwords in any format the seed used | `learners:flag-dob-passwords` runs (twice, chunked, dry run) | they are flagged once; others and teachers are not | `Platform/DateOfBirthPasswordTest` |
| The migration | runs | the column is added and the command queued | `DateOfBirthPasswordTest` |
| A learner signing in with their date of birth | v1 `student/login` | works as before, plus `password_change_required: true`; the account is flagged | `DateOfBirthPasswordTest` |
| A flagged account | calls the API with `X-Supports-Password-Change: 1` / without | 403 `PASSWORD_CHANGE_REQUIRED` until the change / unaffected | `DateOfBirthPasswordTest` |
| An upload | `photo.php.jpg`, a PDF named `.jpg`, a name `..` or with control characters | 422 | `Platform/UploadsTest` |
| `UploadService` | stores a file | under `schools/1/<folder>/` with a random name; old paths stay readable and deletable | `UploadsTest` |
| A client file name | `Doctor's note #1 <b>.pdf` uploaded with a leave | stored as `Doctor_s note _1 _b_.pdf` | `UploadsTest`, `Unit/FileNamesTest` |
| A panel or public page | it loads | it carries the report-only policy; an API answer does not | `Platform/ContentSecurityPolicyTest` |
| A CSP report | posted (either format) | logged with ids only; no CSRF token needed; the 31st in a minute gets 429 | `ContentSecurityPolicyTest` |
| A secret set in the environment | the settings pages open or save | the value is not shown or saved; the environment's value is used | `Platform/SecretSettingsTest` |
| `secrets:audit-settings --purge` | runs | rows the environment replaces and the removed gateways' rows are cleared; values never printed; rows only in `settings` kept | `SecretSettingsTest` |
| A failing request | is logged | field names and ids, never other values | `LogRedactionTest`, `SecurityTest::test_request_log_hides_credentials` |

## Data changes

| Change | Migration | down() |
| --- | --- | --- |
| `users.password_change_required` boolean, default false | `2026_10_22_090000_add_password_change_required_to_users_table` (the scheduler then queues `learners:flag-dob-passwords` daily until one run completes) | drops the column |
| `learner_invitations`: `school_id` (nullable, indexed; Phase 1 adds the key), `learner_user_id`, `guardian_user_id` (nullable), `channel` (`link` or `code`), `token_hash` (sha256, unique), `code_hash` (bcrypt), `expires_at`, `used_at`, `revoked_at`, `created_by`, timestamps | `2026_10_22_090100_create_learner_invitations_table` | drops the table |
| `email_tempalates` row `learner_invitation` | `EmailSeeder` (firstOrCreate) | none needed |
| `settings` rows of secrets | cleared by `secrets:audit-settings --purge` (not a migration: it needs the environment values first) | none |

Retention: an invitation row holds no secret in clear and is closed once used, revoked or expired; the Phase 1 retention job should delete closed rows after 90 days.

## Endpoints

| Method and path | Middleware | Notes |
| --- | --- | --- |
| `GET`, `POST learner-setup` | web; POST `throttle:login` | GR number, set-up code, password and confirmation |
| `GET`, `POST learner-setup/{invitation}/{token}` | web, relative signature checked in the controller; POST `throttle:password` | the guardian's link; an invalid, used or expired link shows a page saying so |
| `POST students/{id}/invite-guardians` | `Role`, `auth`; `student-create`, `student-edit` or `student-reset-password` | JSON |
| `POST students/{id}/sign-in-sheet` | the same | printable page with a new code |
| `POST teachers/{id}/status` | `Role`, `auth`; `teacher-edit` | `status` 0 or 1 |
| `POST csp-report` | web, no CSRF, `throttle:csp` | 204 |
| v1 `POST api/{student,parent,teacher}/login` | unchanged, plus `portal.sign-in` | adds top-level `password_change_required` (bool); snapshots updated additively. From a portal origin: a session instead of a token (`token: ""`) |
| v1 `POST api/logout` | unchanged | from a portal: ends the session as well |
| v1 student, parent, teacher groups | adds `password.change`; teacher groups add `teacher.active` | |

No `openapi.yaml` change: these are v1 and panel routes.

## Screens

- Teacher list: a Status column and a Deactivate / Activate button.
- Student list: a key menu with "Email guardians a sign-in link" and "Print sign-in sheet".
- Student reset list: the reset emails the guardians and offers the sheet.
- Public: the learner set-up pages (link and code) and the printable sheet, on the Material 3 library.
- Settings (Email, General, Fees, Firebase): "Set in the environment" instead of a secret the environment provides.
- Panel change-password and reset pages: the password rule shown.

## Notification types and module switch

One email, `learner_invitation` (template in `email_tempalates`, sent through `MailService`). It belongs with the password-reset mail; the Phase 1 notification registry should mark it as always sent. No module switch: sign-in is always on.

## Security notes

- Link tokens and codes are stored as hashes; the link is signed (relative signature, so it starts with `APP_URL`) and expires; a code is case-insensitive, 10 characters from 31 unambiguous symbols, one live per learner.
- Completing a set-up runs in a transaction with a row lock, so a link or code works once even under parallel use.
- The set-up pages say `noindex` and send no referrer, so the token does not leak into search engines or other sites' logs.
- The CSP still allows inline scripts and styles; removing them (nonces) is later panel work before the policy is enforced.
- Parents still receive their own date of birth as their first password; Phase 1's guardian invitations (with email verification) replace that. A parent or teacher who signs in with their date of birth is flagged at sign-in.

## Self-test steps

1. As the Super Admin, deactivate a teacher on the teacher list; in the teacher app (signed in) open any screen: the app signs out; sign in again: "deactivated". Activate them; sign in works.
2. Add a learner with a parent's email; open the email (staging mailbox), set the learner's password with the link; open the link again: "no longer works". Sign in to the learner app with the GR number and the new password.
3. On the student list, print a sign-in sheet; on `/learner-setup` use the GR number and the code; print another sheet and check the first code is refused.
4. In the app's change-password, try `password123` and `Password1234`: refused with the rule.
5. Open the panel with the browser's developer tools: the response has `Content-Security-Policy-Report-Only`, and the console shows no violation on Home, People, Academics and Settings pages.
6. On staging with the secrets mounted, open Email and Fees settings: "Set in the environment"; run `php artisan secrets:audit-settings`, then `--purge`.
7. Open `staging-app.navigateed.co.za` and sign in as the demo learner: in the browser's storage view there is no token, the `navigateed_staging_session` cookie is `HttpOnly` on `.navigateed.co.za`, and pictures load. Sign out: the next API call answers 401.
