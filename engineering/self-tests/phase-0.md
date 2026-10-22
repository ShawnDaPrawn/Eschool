# Phase 0 self-test record

The Phase 0 self-test matrix (Build Bible section 6, "Tests and self-testing"): admin (navigation, search, checklist, year switcher, wizard, five existing pages), teacher (sign in, timetable, attendance, marks), learner (sign in, timetable, assignments, fees view) and parent (sign in, child screens, fees), in a browser and at phone width. Recorded in the Appendix C format; the template and the profiles are in [README.md](README.md).

**This run is local, not on staging.** Staging is not provisioned yet (no cloud credentials in Phase 0; see the [build log](../bible/build-log.md)), so every row was run on 2026-10-22 against the merged phase branch on one machine and says "local, staging not provisioned". The matrix must be run again on staging, in a browser and on a real phone, before the gate.

## How it was run

- **Server:** the panel from the phase branch after stream S6 merged, served with `php artisan serve` on `http://127.0.0.1:8000` (`APP_ENV=local`, `CACHE_DRIVER=array`, `PHP_CLI_SERVER_WORKERS=4`) on a fresh MariaDB database: `migrate:fresh`, `InstallationSeeder`, `DemoSchoolSeeder`, and `storage:link`.
- **Data added by hand** (the demo seed has none; follow-up F3): one exam, "Term 4 test", with a Mathematics paper for Grade 5 and Grade 6 dated yesterday (for marks capture), and the school setting "learners can pay their fees" switched on (for the learner's fees view).
- **Admin:** Chromium (Playwright 1.56) at 1280 and 1024 px, signed in as `admin.a@demo.navigateed.test`, driven by a script kept outside the repository.
- **Teacher, learner, parent:** both apps built for the web with `flutter build web --release --dart-define=API_BASE_URL=http://127.0.0.1:8000 --dart-define=WEB_AUTH_MODE=bearer --dart-define=ENV=local --dart-define=FLAVOR=learner|teacher`, `build/web` served on `127.0.0.1:8101` (learner and parent) and `127.0.0.1:8102` (teacher), and driven in Chromium at 390 px (phone) and 1280 px (desktop) through Flutter's accessibility tree, as `teacher.a@demo.navigateed.test`, learner `DEMO-A-0001` and `parent.a@demo.navigateed.test` (password `NavigateDemo2026`). Where a screen's text is not in the accessibility tree, the step also checked the API answer the screen received.
- **Local workarounds, all recorded:**
  1. The portals could not sign in from their own origin (defect D1). For the run, the panel was started with `SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8000`, so only the panel itself is a stateful origin, as it was before S6. Without it every row below for teacher, learner and parent fails at sign-in.
  2. Headless Chromium in this container has no language set (`LANG` is empty), which makes Flutter's text layout throw "Incorrect locale information provided" and leaves a white page. The browser was given `en-ZA`, as every real browser has a language.
  3. Flutter loads CanvasKit from `www.gstatic.com`, which this machine cannot reach; the same files were served from `build/web/canvaskit`.
- Screenshots are viewport-only PNGs in [`evidence/`](evidence/), named `p0-<profile>-<step>-<width>.png`.

## Matrix

| Feature | Profile | Device or browser | Steps | Result | Evidence | Defect |
|---|---|---|---|---|---|---|
| Navigation by job (0.11) | School admin | Chromium 1280 px; local, staging not provisioned | From Home: People > Add a learner (0.8 s), Academics > Terms (0.9 s), Communication > Notices (0.9 s) | Pass | [add a learner](evidence/p0-admin-nav-add-learner-1280.png), [terms](evidence/p0-admin-nav-terms-1280.png), [notices](evidence/p0-admin-nav-notice-1280.png) | |
| Navigation by job (0.11) | School admin | Chromium 1024 px; local, staging not provisioned | The same three paths (0.8, 0.8, 0.8 s) | Pass | | |
| Home setup checklist (0.11) | School admin | Chromium 1280 px; local, staging not provisioned | Open Home: "Your school is ready for learners", 7 of 7 steps done, today's numbers | Pass | [checklist](evidence/p0-admin-checklist-1280.png) | |
| Home setup checklist (0.11) | School admin | Chromium 1024 px; local, staging not provisioned | The same | Pass | [checklist](evidence/p0-admin-checklist-1024.png) | |
| Global search (0.11) | School admin | Chromium 1280 px; local, staging not provisioned | Press "/", type "Lerato", arrow down, Enter: Lerato Dlamini's page opens (1.7 s) | Pass | [learner page](evidence/p0-admin-search-1280.png) | |
| Global search (0.11) | School admin | Chromium 1024 px; local, staging not provisioned | The same (1.8 s) | Pass | | |
| Year switcher (0.11) | School admin | Chromium 1280 px; local, staging not provisioned | On Learners, pick last year in the header: banner "… is a past year, so it is read-only"; Notices form's submit disabled; "Back to 2026" removes the banner | Pass | [banner](evidence/p0-admin-year-switcher-1280.png) | |
| Year switcher (0.11) | School admin | Chromium 1024 px; local, staging not provisioned | The same | Pass | [banner](evidence/p0-admin-year-switcher-1024.png) | |
| New-year wizard (0.11) | School admin | Chromium 1280 px; local, staging not provisioned | Open "Start the new school year", create 2027, Next through carry forward, year-end checks and preview, "Make 2027 current", confirm: Home shows 2027 (current), 4.2 s in all; 2026 made current again afterwards for the rest of the run | Pass | [wizard](evidence/p0-admin-new-year-wizard-1280.png) | |
| Existing pages (0.11) | School admin | Chromium 1280 px; local, staging not provisioned | Learners, Build timetable, Take attendance, Exams, Fees paid (and Staff) load with the new layout, no server error, no horizontal scroll | Pass | [learners](evidence/p0-admin-page-learners-1280.png), [timetable](evidence/p0-admin-page-timetable-1280.png), [attendance](evidence/p0-admin-page-attendance-1280.png), [exams](evidence/p0-admin-page-exams-1280.png), [fees](evidence/p0-admin-page-fees-1280.png) | D3 (fixed) |
| Existing pages (0.11) | School admin | Chromium 1024 px; local, staging not provisioned | The same six pages | Pass | [learners](evidence/p0-admin-page-learners-1024.png), [fees](evidence/p0-admin-page-fees-1024.png) | |
| Apps in a browser (0.9, 0.10) | Teacher (homeroom 5 A, subject teacher) | Chromium 390 px; local, staging not provisioned | Sign in with e-mail and password: Home shows the homerooms and the teacher menu | Pass | [home](evidence/p0-teacher-sign-in-390.png) | D1 (workaround), D2 |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 390 px; local, staging not provisioned | Schedule tab: day tabs and today's lessons (10 lessons this week) | Pass | [schedule](evidence/p0-teacher-timetable-390.png) | D4 |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 390 px; local, staging not provisioned | Home > Grade 5 - A > take attendance > Submit: today's register saved | Pass | [register](evidence/p0-teacher-attendance-390.png) | D4 |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 390 px; local, staging not provisioned | Home > Add Result > Term 4 test, Mathematics: marks for the three learners of Grade 5 A, Submit Result: saved | Pass | [marks](evidence/p0-teacher-marks-390.png) | |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 1280 px; local, staging not provisioned | Sign in: navigation rail and centred content | Pass | [home](evidence/p0-teacher-sign-in-1280.png) | D1 (workaround), D2 |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 1280 px; local, staging not provisioned | Schedule from the rail | Pass | [schedule](evidence/p0-teacher-timetable-1280.png) | D4 |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 1280 px; local, staging not provisioned | Take attendance for Grade 5 A and submit | Pass | [register](evidence/p0-teacher-attendance-1280.png) | D4 |
| Apps in a browser (0.9, 0.10) | Teacher | Chromium 1280 px; local, staging not provisioned | Capture and submit the Term 4 test Mathematics marks (the three rows in the database afterwards) | Pass | [marks](evidence/p0-teacher-marks-1280.png) | |
| Apps in a browser (0.9, 0.10) | Learner (approved, Grade 5 A) | Chromium 390 px; local, staging not provisioned | Login as Student, GR number and password: Home shows the subjects and today's timetable | Pass | [home](evidence/p0-learner-sign-in-390.png) | D1 (workaround), D2 |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 390 px; local, staging not provisioned | Menu > Timetable: Grade 5 A's lessons | Pass | [timetable](evidence/p0-learner-timetable-390.png) | |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 390 px; local, staging not provisioned | Assignments tab: Assigned and Submitted with the designed empty state (the demo school has no assignments) | Pass | [assignments](evidence/p0-learner-assignments-390.png) | F3 |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 390 px; local, staging not provisioned | Menu > Report > Fees: R 18500 payable, R 0 paid, next due 2026-03-31 | Pass | [fees](evidence/p0-learner-fees-390.png) | |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 1280 px; local, staging not provisioned | Sign in | Pass | [home](evidence/p0-learner-sign-in-1280.png) | D1 (workaround), D2 |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 1280 px; local, staging not provisioned | Menu > Timetable | Pass | [timetable](evidence/p0-learner-timetable-1280.png) | |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 1280 px; local, staging not provisioned | Assignments from the rail | Pass | [assignments](evidence/p0-learner-assignments-1280.png) | F3 |
| Apps in a browser (0.9, 0.10) | Learner | Chromium 1280 px; local, staging not provisioned | Menu > Report > Fees | Pass | [fees](evidence/p0-learner-fees-1280.png) | |
| Apps in a browser (0.9, 0.10) | Parent (two children at School A) | Chromium 390 px; local, staging not provisioned | Login as Parent, e-mail and password: Home lists Lerato (Grade 5 A) and Kabelo (Grade 6 A) | Pass | [home](evidence/p0-parent-sign-in-390.png) | D1 (workaround), D2 |
| Apps in a browser (0.9, 0.10) | Parent | Chromium 390 px; local, staging not provisioned | Tap Lerato: her page; Attendance and Timetable open | Pass | [timetable](evidence/p0-parent-child-390.png) | |
| Apps in a browser (0.9, 0.10) | Parent | Chromium 390 px; local, staging not provisioned | Lerato > Fees: status Pending and the due date | Pass | [fees](evidence/p0-parent-fees-390.png) | D5 |
| Apps in a browser (0.9, 0.10) | Parent | Chromium 1280 px; local, staging not provisioned | Sign in | Pass | [home](evidence/p0-parent-sign-in-1280.png) | D1 (workaround), D2 |
| Apps in a browser (0.9, 0.10) | Parent | Chromium 1280 px; local, staging not provisioned | Lerato's page, Attendance and Timetable | Pass | [timetable](evidence/p0-parent-child-1280.png) | |
| Apps in a browser (0.9, 0.10) | Parent | Chromium 1280 px; local, staging not provisioned | Lerato > Fees | Pass | [fees](evidence/p0-parent-fees-1280.png) | D5 |
| Web portal sign-in from the portal's own origin (0.10) | Learner, parent, teacher | Chromium and HTTP checks; local, staging not provisioned | Without the workaround: `POST /api/student/login` from origin `http://127.0.0.1:8101` with a bearer build; and the cookie-mode sequence (`/sanctum/csrf-cookie`, sign-in with `X-XSRF-TOKEN`, `GET /api/v2/me/context` with the session cookie) | **Fail** | | D1 |
| Pictures in the web portals (0.10) | Learner, parent, teacher | Chromium 390 and 1280 px; local, staging not provisioned | Avatars and subject pictures from `/storage` | **Fail** | the blank tiles in [learner timetable](evidence/p0-learner-timetable-390.png) | D2 |
| Apps on a phone (0.9) | Teacher, learner, parent | none | Not run: there is no Android emulator or device here (CI's `apps-smoke-android` covers sign-in and one screen per profile) | Not run | | |

## Defects

| # | Defect | Where | Status |
|---|---|---|---|
| D1 | The web portals cannot sign in from their own origin. Portal origins are Sanctum stateful domains (S6's `config/sanctum.php` default and `SANCTUM_STATEFUL_DOMAINS` in `infra/gcp/env/*.env`), so every POST from a portal goes through the CSRF check: a bearer build gets 419 "CSRF token mismatch" on sign-in. Cookie mode, the build default, passes the CSRF check, but the sign-in endpoints issue a token and start no web session, so the next call (`GET /api/v2/me/context`) answers 401. | S6 and S2 (server side of cookie mode, S2's open follow-up) | **Open, blocks the gate.** Build the cookie-mode sign-in (the Bible's rule for portals), or until then take the portal origins out of the stateful list and build the portals with `WEB_AUTH_MODE=bearer` in `deploy-environment.yml`. Also in [security/phase-0.md](../security/phase-0.md). |
| D2 | Avatars and subject pictures do not load in the web portals: they come from the panel's `/storage`, which sends no CORS headers, and the browser blocks the cross-origin fetch (blank tiles). | Hosting of `/storage` (web server or bucket CORS); the same cause as S2's PDF-viewing follow-up | Open |
| D3 | `/staff` answered 500 for its table when a staff member has no date of birth (found by S3). | `StaffController::show` | **Fixed** in this stream with `StaffListTest` |
| D4 | Teacher app accessibility: "Take attendance" is an icon-only floating button with no label, and the lesson cards on Schedule are not in the accessibility tree. | `e-school-teacher` (legacy screens) | Open, for the screen redesigns |
| D5 | The fees list shows "Class : Grade" because it keeps only the first word of the homeroom name (`feesStatusScreen.dart`, `className[0]`), which assumed names like "10 A". | `e-school` (legacy screen) | Open |

Follow-ups found on the way (not defects in Phase 0 code):

- **F1.** Flutter web throws on a browser whose language tag is invalid (seen only in a headless browser without `LANG`). Real browsers always send one; worth a guard when the apps read the platform locale.
- **F2.** The portals load CanvasKit from `www.gstatic.com` (and PDF.js from jsDelivr). Hosting the files with the portal, or allowing them in the portal CSP, is part of S2's Hosting CSP follow-up.
- **F3.** `DemoSchoolSeeder` has no exam paper and no assignment, so marks capture and the assignments list cannot be self-tested from the seed alone (this run inserted the exam by hand). Add both.

## UX scripts

The Appendix C starter scripts need new users timed with no help, so they cannot be run here. **To run on staging with the product owner**, four of five tasks per profile in under two minutes without a wrong turn:

| Profile | Tasks | Status |
|---|---|---|
| Admin | Add a learner; set up terms; send a notice; switch to last year; create next year | To run on staging with the product owner |
| Teacher | Today's timetable; take a register; capture a mark; see a learner's page; message a parent | To run on staging with the product owner |
| Learner | Next lesson and room; assignments due; results; a notice; change language | To run on staging with the product owner |
| Parent | Child's attendance today; fees balance; a report card; a notice; switch child | To run on staging with the product owner |

The scripted timings above (finding each admin task in under a second, the learner page from search in under two seconds, the wizard in about four seconds) show the paths exist and are short; they are not a substitute for new users.

## See also

- [README.md](README.md): the template, the profiles and the UX script rules
- [Phase 0 security record](../security/phase-0.md)
- [Build log](../bible/build-log.md)
