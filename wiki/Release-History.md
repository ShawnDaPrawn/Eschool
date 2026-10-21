The release history of eSchool: a short summary of the upstream versions this fork is based on, what the 3.5.0 app releases changed, and a detailed list of this fork's own changes (security hardening, tests and CI, dependency updates, fixes, translations) with links to their commits and pull requests.

## Current version

| Component | Version | Where it is set |
|---|---|---|
| Admin panel | `3.5.0` | `Settings::updateOrCreate(['type' => 'system_version'], ['message' => '3.5.0'])` in `database/seeders/InstallationSeeder.php`, which the container re-runs on every start (`docker/entrypoint.sh`). Update packages for the in-panel System Update used to raise it; that page was removed in Phase 0 |
| Student/parent app (`e-school`) | `3.5.0+39` | `e-school/pubspec.yaml` |
| Teacher app (`e-school-teacher`) | `3.5.0+32` | `e-school-teacher/pubspec.yaml` |
| Documentation site | Docusaurus `3.10.2` | `documentation/package.json` |

The fork has not changed these version numbers; all of its work so far sits on top of upstream 3.5.0.

## Upstream versions

The end-user changelog is at https://shawndaprawn.github.io/Eschool/changelog/ (source `documentation/changelog/index.md`). In brief:

| Version | Date | Highlights |
|---|---|---|
| 3.5.0 | 20 Jul 2026 | Student reports (attendance, fees, exams, assignments, leaves); teacher ID cards; teacher and staff attendance and reports; four attendance types with leave-aware validation; staff ID card customisation; school-admin teacher/staff leave management; custom email templates for admission and teacher/staff creation; month-wise attendance reports; backup reminder before system updates; reCAPTCHA fixes; Android code moved from Java to Kotlin; Flutter 3.44.6 |
| 3.4.3 | 22 Jun 2026 | Admin panel UI/UX redesign; ID card view/download in the app; ID card customisation with live preview |
| 3.4.2 | 25 May 2026 | Stability fixes |
| 3.4.1 | 15 May 2026 | Session-year-wise semester management; semester-aware modules and apps; semester-wise offline results; Flutter 3.41.9 |
| 3.4.0 | 12 Mar 2026 | Session-year-wise data structure; global session year filter; gallery in both apps; deleting a session year's data; force delete of linked data |
| 3.3.6 | 22 Dec 2025 | Promotion flow; school website "Explore" in the apps; Firebase Crashlytics; Android 16 edge-to-edge fixes |
| 3.3.5 | 21 Nov 2025 | Admin control of student leaves; academic calendar PDF; elective subject assignment by admin |
| 3.3.4 | 17 Oct 2025 | Laravel 12 with PHP 8.3; Firebase topic notifications; Android 16 KB page size; Flutter 3.35 |
| 3.3.3 | 18 Apr 2025 | Flutter 3.29 and Android 15 (SDK 35) |
| 3.3.2 | 6 Feb 2025 | Teacher app home redesign; staff leaves on the teacher dashboard; teachers manage timetable links; Flutter 3.27, AGP 8 |
| 3.3.1 | 13 Nov 2024 | Student leave management; Flutterwave; Paystack web view; multi-gateway selection; captcha on admission and contact forms |
| 3.3.0 | 17 Sep 2024 | Student self-registration from the website; semesters; combined academic calendar |
| 3.2.x | Jun–Jul 2024 | Staff/teacher leave management; fee payment by students and compulsory payment after due date; online class links in the timetable (3.2.0); teacher-app leaves, chat settings, cache clear, attendance report (3.2.1) |
| 3.1.x | Apr–May 2024 | Staff management, events, website section hiding, Firebase HTTP v1 (3.1.0); certificates, ID cards, custom form fields (3.1.1) |
| 3.0.0 | 28 Feb 2024 | eSchool Web (the public school website) with settings in the admin panel |
| 2.x | Aug 2023 – Jan 2024 | Teacher app (2.0.0); bulk attendance, dashboard statistics (2.0.1); dynamic admission fields, custom notifications (2.0.2); chat module (2.1.0); Paystack, demo mode flag served by the backend (2.1.1) |
| 1.0.1 – 1.0.7 | Jun 2022 – Jul 2023 | System validator, RTL, exams and results, fees, online exams, installments and receipts |

### 3.5.0 app file changes

The counts below are from upstream's 3.5.0 file lists. The raw dumps are not kept in the repository. The product changelog is [documentation/changelog](https://github.com/ShawnDaPrawn/Eschool/blob/main/documentation/changelog/index.md).

| App | Entries | Added | Modified | Deleted | Main themes |
|---|---|---|---|---|---|
| Student/parent (`e-school`) | 104 | 34 | 59 | 11 | Android Gradle scripts moved to the Kotlin DSL (`build.gradle` → `build.gradle.kts`, `settings.gradle.kts`) and `FlutterMultiDexApplication.java` removed; new languages `ar`, `es`, `fr`, `id`, `pt-BR`, `tr`; the reports area rebuilt (`lib/ui/screens/reports/`: attendance, exam, fees, leave and assignment report tabs, session-year and subject pickers); attendance summary widgets; `sessionYearCubit`; `lib/utils/api.dart` and label keys updated |
| Teacher (`e-school-teacher`) | 71 | 19 | 49 | 3 | Same Gradle Kotlin DSL move; new languages `ar`, `es`, `id`, `pt-BR`, `tr` and an `er.json`; teacher attendance (`teacherAttendanceCubit`, `teacherAttendanceRepository`, `lib/ui/screens/teacherAttendance/`); ID card download (`downloadIdCardCubit`); `pubspec.yaml` and `pubspec.lock` updated |

The teacher app's `er.json` was an unused copy of `en.json` and the app offered French without an `fr.json`; the fork fixed both in [ef07de7](https://github.com/ShawnDaPrawn/Eschool/commit/ef07de7).

## This fork

The repository's history starts at [44a78d2](https://github.com/ShawnDaPrawn/Eschool/commit/44a78d2) "eSchool 3.5.0" (20 Oct 2026): the admin panel, both apps and the documentation as one commit. Earlier history was replaced so the repository no longer carries the original vendor's code history, licence system or upgrade packages. That commit is the head of [pull request #1](https://github.com/ShawnDaPrawn/Eschool/pull/1) ("Remove WRTeam/Envato licensing and branding"), which was closed without being merged; its content became the new root of `main`.

All fork work below landed on 20 October 2026 (UTC).

### Merged pull requests

| PR | Merged (UTC) | Merge commit | Title | Commits |
|---|---|---|---|---|
| [#2](https://github.com/ShawnDaPrawn/Eschool/pull/2) | 2026-10-20 20:49 | [c2dca89](https://github.com/ShawnDaPrawn/Eschool/commit/c2dca89) | Security hardening, dependency updates, tests and CI | 2de82e8 … 2ff97a2 (10 commits) |
| [#4](https://github.com/ShawnDaPrawn/Eschool/pull/4) | 2026-10-20 22:22 | [e89ee6a](https://github.com/ShawnDaPrawn/Eschool/commit/e89ee6a) | Fix broken admin pages, remove unprotected users code, replace CKEditor 4 | a47f048 … 4b967ff (5 commits) |
| [#5](https://github.com/ShawnDaPrawn/Eschool/pull/5) | 2026-10-20 23:01 | [d35c2f4](https://github.com/ShawnDaPrawn/Eschool/commit/d35c2f4) | Phase 1: Remove stray translation files and complete app language files | ab36b16, ef07de7 |
| [#3](https://github.com/ShawnDaPrawn/Eschool/pull/3) | 2026-10-20 23:04 | [b3d925d](https://github.com/ShawnDaPrawn/Eschool/commit/b3d925d) | Security audit fixes across the admin panel, API and both apps | ee49250 … 05b97a9 (16 commits, plus merge [0ce6bac](https://github.com/ShawnDaPrawn/Eschool/commit/0ce6bac)) |
| [#6](https://github.com/ShawnDaPrawn/Eschool/pull/6) | 2026-10-20 23:22 | [a1a671b](https://github.com/ShawnDaPrawn/Eschool/commit/a1a671b) | Phase 2: Read admin settings from the database so config caching works | e13ac7d, 7af6b74 |
| [#9](https://github.com/ShawnDaPrawn/Eschool/pull/9) | 2026-10-20 23:28 | [ff82b30](https://github.com/ShawnDaPrawn/Eschool/commit/ff82b30) | Add a project wiki and publish it to the Wiki tab | dce1a32 … 957a95a (6 commits) |

PR #3 was opened before #4 but merged after #4 and #5. Its merge of `main` ([0ce6bac](https://github.com/ShawnDaPrawn/Eschool/commit/0ce6bac)) dropped the branch's hardened `UserController` (main had already removed the users demo code in [97e6bff](https://github.com/ShawnDaPrawn/Eschool/commit/97e6bff)), kept main's `DB_PERSISTENT` handling, and kept both main's removal of the `video-gallery` route and the branch's contact-form throttle.

### Security hardening

Details of the resulting controls are on [Security-Model](Security-Model).

| Commit | PR | Change |
|---|---|---|
| [2de82e8](https://github.com/ShawnDaPrawn/Eschool/commit/2de82e8) | #2 | `.env.example` defaults to `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=error`; Sanctum tokens expire after 30 days (`SANCTUM_EXPIRATION`) and expired tokens are pruned daily |
| [2eceb8b](https://github.com/ShawnDaPrawn/Eschool/commit/2eceb8b) | #2 | `/clear` needs login; `/storage-link`, `/migrate`, `/seeder_install` need Super Admin; `migrate` and `seeder_install` pass `--force` |
| [420df6d](https://github.com/ShawnDaPrawn/Eschool/commit/420df6d) | #2 | TLS certificate and host verification turned back on for FCM and Flutterwave requests |
| [767873a](https://github.com/ShawnDaPrawn/Eschool/commit/767873a) | #2 | Student/parent app release builds use HTTPS with system certificates only (debug keeps HTTP and user CAs) |
| [97e6bff](https://github.com/ShawnDaPrawn/Eschool/commit/97e6bff) | #4 | Removed the unprotected `UserController` and `resources/views/users` (Spatie tutorial leftovers) |
| [ee49250](https://github.com/ShawnDaPrawn/Eschool/commit/ee49250) | #3 | API errors include exception details only when `APP_DEBUG` is on; logs redact tokens, cookies and passwords; `SubjectController::index` returns after its no-permission redirect |
| [9f8911b](https://github.com/ShawnDaPrawn/Eschool/commit/9f8911b) | #3 | New `RejectDangerousUploads` middleware on the `web` and `api` groups; `storage/app/public/.htaccess` stops uploaded files from running |
| [60615fc](https://github.com/ShawnDaPrawn/Eschool/commit/60615fc) | #3 | SVG uploads sanitised with `enshrined/svg-sanitize`; uploads also refused by detected MIME type; `UploadSecurityTest` |
| [ef7df75](https://github.com/ShawnDaPrawn/Eschool/commit/ef7df75) | #3 | Email templates need `email-setting-create` and are filled by `EmailTemplateService` instead of `Blade::render`; `SecurityHeaders` middleware; `APP_KEY` blank in `.env.example` |
| [439395d](https://github.com/ShawnDaPrawn/Eschool/commit/439395d) | #3 | `Parents::children()` rewritten as one OR query and `CheckChild` hardened; student/parent/teacher API groups require their role; `throttle:login` on app logins and resets; `/register` removed; admin panel requires a staff role |
| [3005b52](https://github.com/ShawnDaPrawn/Eschool/commit/3005b52) | #3 | Unauthenticated `delete-chat-message/cron-job` URL removed; contact form and online registration rate limited; `changeEnv()` strips line breaks (the helper itself was removed later in e13ac7d); secure session cookies by default on `https://`; root `.htaccess` denies dotfiles and metadata |
| [201b863](https://github.com/ShawnDaPrawn/Eschool/commit/201b863) | #3 | Role and staff management limited (no Super Admin assignment, built-in roles locked, assignable permissions only); language codes validated; sliders and parent search need permissions; password change checks the current password; Firebase key moved to the private disk; settings uploads type-checked; staff and teacher emails use `EmailTemplateService`. (Its `UserController` changes were dropped in the merge, see above.) |
| [77fe8ed](https://github.com/ShawnDaPrawn/Eschool/commit/77fe8ed) | #3 | Teacher API limited to the teacher's own classes, subjects, students, files, timetable and chat contacts |
| [a09dfe8](https://github.com/ShawnDaPrawn/Eschool/commit/a09dfe8) | #3 | Lesson, topic and submission scopes match the exact class-section and subject pair |
| [c890813](https://github.com/ShawnDaPrawn/Eschool/commit/c890813) | #3 | Parent API limited to the parent's own children and their records; teacher dates of birth removed from payloads; parent leave deletion fixed |
| [3627cf8](https://github.com/ShawnDaPrawn/Eschool/commit/3627cf8) | #3 | Both apps: certificate checks restored (global `HttpOverrides` removed), no token or body logging in release, no Android backups, Hive moved to Application Support on iOS, safe download names, http(s)-only links, payment WebView restricted, online-exam answers and key no longer needed client-side |
| [6b9f7dd](https://github.com/ShawnDaPrawn/Eschool/commit/6b9f7dd) | #3 | Missing permission checks added across academic pages; teacher marks limited to their own sections; eight list searches moved to bound parameters; student emails use `EmailTemplateService` |
| [27a3b11](https://github.com/ShawnDaPrawn/Eschool/commit/27a3b11) | #3 | `escapeHtml()`, `safeUrl()`, `escapeToastText()` in `function.js`; table formatters, toasts and flash messages escape user text |
| [839c9eb](https://github.com/ShawnDaPrawn/Eschool/commit/839c9eb) | #3 | Payment webhooks fail closed, check amount and currency, and record idempotently from stored data; `FeesPaymentService` prices payments on the server; `/api/settings` no longer returns the Razorpay webhook secret; online-exam answers and key no longer sent, submissions limited, exact-set scoring (`OnlineExamScoringService`); student API scoping, uniform forgot-password answers, sign-out of other devices on password change |
| [05b97a9](https://github.com/ShawnDaPrawn/Eschool/commit/05b97a9) | #3 | Flutterwave webhook secret hash required when Flutterwave is enabled, and labelled correctly on the fees settings page |

### Settings and configuration caching

| Commit | PR | Change |
|---|---|---|
| [e13ac7d](https://github.com/ShawnDaPrawn/Eschool/commit/e13ac7d) | #6 | Admin settings are stored only in the `settings` table: `changeEnv()` (which rewrote `.env` from the settings, mail, notification and fees pages) is gone, and the 23 files that read those values back with `env()` now use `config()` or the new `schoolSetting()` / `schoolLogoUrl()` helpers. New `SettingsConfigService` applies the school name, timezone, mail server and Paystack keys over the `.env` config on every boot; new `config('app.demo_mode')`. The duplicate route name `events.update` on `POST events-update` became `events.update-schedules` so `route:cache` works. CI builds the config, route and view caches, and `SettingsConfigTest` fails on any `env()` call outside `config/`. The installation docs gained an optional "Cache Configuration for Production" step (`php artisan optimize`) |
| [7af6b74](https://github.com/ShawnDaPrawn/Eschool/commit/7af6b74) | #6 | The mail sender name keeps following the school name when `MAIL_FROM_NAME` was `"${APP_NAME}"` (as in `.env.example`), and keeps any sender name of its own otherwise |

Before this change, rewriting `.env` also changed the session cookie name (derived from `APP_NAME`) whenever the school name changed, signing everyone out, and could corrupt `.env` when a value contained a space or `#`.

### Wiki

| Commit | PR | Change |
|---|---|---|
| [dce1a32](https://github.com/ShawnDaPrawn/Eschool/commit/dce1a32) | #9 | Started this wiki in `wiki/` and added `.github/workflows/wiki.yml`, which publishes it to the Wiki tab (see [Contributing](Contributing#editing-this-wiki)) |
| [a998d3b](https://github.com/ShawnDaPrawn/Eschool/commit/a998d3b), [d323244](https://github.com/ShawnDaPrawn/Eschool/commit/d323244), [9a41c5d](https://github.com/ShawnDaPrawn/Eschool/commit/9a41c5d), [56aa690](https://github.com/ShawnDaPrawn/Eschool/commit/56aa690), [957a95a](https://github.com/ShawnDaPrawn/Eschool/commit/957a95a) | #9 | First wiki pages |

### Admin panel fixes and editor replacement

| Commit | PR | Change |
|---|---|---|
| [43323f2](https://github.com/ShawnDaPrawn/Eschool/commit/43323f2) | #4 | Fixed the 42 admin pages that returned 500 on a fresh install: resource routes for missing actions removed with `->except()`, `$_GET` filters given defaults, empty privacy/terms pages handled, unused `fees/fees-receipt` route removed, edit buttons use `href="#"`, teachers without custom-field values load. `AdminPagesTest` now fails on any server error |
| [79a0441](https://github.com/ShawnDaPrawn/Eschool/commit/79a0441) | #4 | CKEditor 4.20.1 (end of life) replaced by Jodit 4.18.6 in the online exam question and option editors; formulas kept as `<span class="math-tex">`; previews rendered with KaTeX 0.19.0 served locally instead of MathJax from a CDN; jQuery Validate skips contenteditable elements |
| [4b967ff](https://github.com/ShawnDaPrawn/Eschool/commit/4b967ff) | #4 | Exam question editor content is copied directly on submit (Jodit's throttled sync could drop the last characters); the empty-content check parses HTML with `DOMParser` |

### Tests and CI

| Commit | PR | Change |
|---|---|---|
| [b061179](https://github.com/ShawnDaPrawn/Eschool/commit/b061179) | #2 | First admin panel tests: `InstallationTest`, `SecurityTest`, `SystemUpdateTest`, `AdminPagesTest`; tests run on MySQL/MariaDB and refuse databases not ending in `_test` |
| [2ff97a2](https://github.com/ShawnDaPrawn/Eschool/commit/2ff97a2) | #2 | `.github/workflows/ci.yml`: admin panel tests on MariaDB, Flutter analyze for both apps with lock files enforced, documentation build, weekly dependency audit |
| [a47f048](https://github.com/ShawnDaPrawn/Eschool/commit/a47f048) | #4 | `DB_PERSISTENT` setting (default true); tests turn persistent connections off and set `CACHE_DRIVER=array` |
| [bfb73dd](https://github.com/ShawnDaPrawn/Eschool/commit/bfb73dd) | #3 | Same persistent-connection fix on the security branch (merged into main's version) |
| [ab36b16](https://github.com/ShawnDaPrawn/Eschool/commit/ab36b16) | #5 | The `php -S` test server moved out of `AdminPagesTest` into `Tests\Concerns\RunsAppServer` with `appClient()` / `apiClient()` |
| [ef07de7](https://github.com/ShawnDaPrawn/Eschool/commit/ef07de7) | #5 | `test/translations_test.dart` in both apps, run in CI with `flutter test`; `flutter_test` added to the student app |
| [e13ac7d](https://github.com/ShawnDaPrawn/Eschool/commit/e13ac7d) | #6 | CI step "Config, routes and views cache" (`config:cache`, `route:cache`, `view:cache`, then `optimize:clear`); `SettingsConfigTest` |

Security commits in #3 each added their own feature tests (`AccessControlTest`, `AdminAuthorizationTest`, `AcademicAuthorizationTest`, `ParentApiSecurityTest`, `TeacherApiSecurityTest`, `StudentApiSecurityTest`, `PaymentWebhookTest`, `UploadSecurityTest`). See [Testing-and-CI](Testing-and-CI).

### Dependencies and repository housekeeping

| Commit | PR | Change |
|---|---|---|
| [ea67e4d](https://github.com/ShawnDaPrawn/Eschool/commit/ea67e4d) | #2 | Stopped tracking the generated `bootstrap/cache/packages.php` and `services.php` |
| [6adcbe4](https://github.com/ShawnDaPrawn/Eschool/commit/6adcbe4) | #2 | `composer update -W` within existing constraints: Laravel 12.35 → 12.70, Guzzle, Dompdf, PhpSpreadsheet, league/commonmark, Symfony, google/apiclient and others; `composer audit` from 70 advisories (2 critical, 22 high) to none |
| [dd3df94](https://github.com/ShawnDaPrawn/Eschool/commit/dd3df94) | #2 | `pubspec.lock` committed for both apps, pinned to the versions analysed with Flutter 3.44.9 (an unpinned `file_picker` 12.3 had broken analysis) |
| [03f9bbb](https://github.com/ShawnDaPrawn/Eschool/commit/03f9bbb) | #2 | Docusaurus 3.7.0 → 3.10.2 (Node 20 required), `npm audit fix` plus overrides for `tinypool` and `serialize-javascript`; critical advisories 3 → 0 |

### Translations

| Commit | PR | Change |
|---|---|---|
| [ef07de7](https://github.com/ShawnDaPrawn/Eschool/commit/ef07de7) | #5 | Admin panel: deleted `er.json`, `ert.json`, `rt.json`, `w3.json` (copies of an old `en.json`), `eng-rtl.json` and the unused `lang/en/` duplicate; `/common/js/lang` falls back to `en.json` when the session language has no file. Teacher app: added `fr.json` (229 labels reused from the student app, 198 new), removed `er.json`. Student app: 6 missing labels added to `hi.json` and `ur.json` |

See [Localization](Localization).

## Upgrade notes for existing installs

If you run an install made from the original 3.5.0 code, these fork changes need action after updating:

| Change | Action |
|---|---|
| `/api/settings` used to return the Razorpay webhook secret | Rotate the Razorpay webhook secret in the Razorpay dashboard and on the fees settings page |
| The Firebase service account key used to be on the public disk | Rotate the key in Google Cloud, then save the notification settings again (an existing copy is moved to the private disk) |
| Webhooks now fail closed | Make sure every enabled gateway has its webhook secret set; for Flutterwave, the field is the dashboard's secret hash, not the encryption key |
| `APP_KEY` is blank in `.env.example` | Installs that skipped `key:generate` and use the old shipped key must generate a new one (this signs everyone out and invalidates encrypted cookies) |
| Sanctum tokens expire | App users are signed out 30 days after logging in (adjust with `SANCTUM_EXPIRATION`); add the `schedule:run` cron so expired tokens are pruned |
| `/register` removed and staff role required | Accounts without a staff role can no longer use the admin panel |
| The chat cleanup URL was removed | Old chat messages are only deleted by the scheduler (`app:cleanup-old-chat-messages`); replace any external cron that called `delete-chat-message/cron-job` with `schedule:run` |
| Online exam answers and key no longer sent | Old app builds that relied on them keep working through placeholders, but students must type the exam key, which the server checks |
| Release builds of the student app require HTTPS | The admin panel must be on HTTPS with a valid certificate chain |
| Settings are no longer copied into `.env` | The settings, mail, notification and fees pages save to the database only and no longer write `LOGO1`, `LOGO2`, `FAVICON`, `LOGIN_IMAGE`, `APP_NAME`, `TIMEZONE`, `SITE_KEY`, `SECRET_KEY`, `SENDER_ID`, `FIREBASE_PROJECT_ID`, `MAIL_*`, `RAZORPAY_*`, `STRIPE_*`, `PAYSTACK_*` or `FLW_*` into `.env`. Code that read `APP_NAME`, `APP_URL`, `DEMO_MODE`, `LOGO1`, `LOGO2`, `FAVICON` or `MAIL_*` with `env()` now uses `config()` or the `settings` table; `.env` mail and Paystack values only apply when the matching setting is empty. `php artisan optimize` (config, route and view caching) now works; re-run it after editing `.env` or updating |
| System Update and the `storage-link`, `migrate` and `seeder_install` URLs removed (Phase 0) | Release by deploying a new image (or new code). Deploy time links storage, runs `migrate --force`, `InstallationSeeder` and `EmailSeeder`: `docker/entrypoint.sh` does this on every container start; on a server without the image run those commands after each update. The old URLs answer 404 |
| Route name `events.update` on `POST events-update` | Renamed to `events.update-schedules`; customisations that call `route('events.update')` for that URL must be updated |

## See also

- [Security-Model](Security-Model)
- [Testing-and-CI](Testing-and-CI)
- [Contributing](Contributing)
- [Documentation-Site](Documentation-Site)
- [Localization](Localization)
