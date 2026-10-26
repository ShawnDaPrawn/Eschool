# Web builds of both apps

Phase 0, feature 0.10. Owner role: App. Build Bible section 6 and its UX check for the apps in a browser; [ADR 0003](../../adr/0003-app-packages-and-web.md). Work stream S2.

Both apps now build for the web: the learner and parent app as the portal at `app.navigateed.co.za` (Firebase Hosting site `navigateed-app`), the teacher app at `teach.navigateed.co.za` (`navigateed-teach`). They are the same code as the phone apps, laid out for the browser's width (see [foundations.md](foundations.md)).

## What the web build does differently

| Area | Phone | Web | How |
| --- | --- | --- | --- |
| Signing in | Bearer token kept in Hive | Sanctum session cookie for the portal origin; no token is stored; requests go with credentials and writes send `X-XSRF-TOKEN` (a 419 fetches a fresh token and retries once) | `AuthMode.cookie` and `XsrfInterceptor` in `eschool_session` (one switch: `--dart-define=WEB_AUTH_MODE=bearer` goes back to tokens) |
| Browser language | n/a | A language tag the browser reports that is not valid (a browser started without a system language) is dropped, English when none is left, before Flutter starts | `web/locale_guard.js` in both apps |
| Storage | Hive in the app's private support directory | Hive on IndexedDB | `initAppStorage()` (`app_storage_io.dart` / `app_storage_web.dart`) |
| Push notifications | FCM topics and local notifications (awesome_notifications) | None: the Notifications screen lists them from the API (in-app notices) | `NotificationUtility.isActive` is false on the web; sign-in sends no `fcm_id` or `device_type`, so the server keeps the user's phone |
| Crash reports | Firebase Crashlytics | None (no Firebase app on the web); errors go to the browser console | `prepareApp()` skips `Firebase.initializeApp()` on the web |
| PDFs | Downloaded to a file and shown with flutter_pdfview | Downloaded into memory and shown with pdfx (pdf.js, loaded by `web/index.html`) | `PdfDocumentView` (`pdf_document_view_io.dart` / `_web.dart`) |
| Receipts, ID cards, results, calendars | Saved under the app's documents folder and opened with open_filex | Downloaded by the browser | `DeviceFiles.saveDocument()` and `DeviceFiles.open()` (`device_files_io.dart` / `_web.dart`) |
| Study material downloads | Saved to Downloads (external_path) or documents | Opened in a new tab, where the browser shows or downloads it | `DownloadFileCubit` on the web |
| Picked images | `File` | The picker's `blob:` address | `localImage()` in `CustomImageWidget` |
| Attaching files (assignments, chat, leave, announcements, study materials) and paying fees | Work as before | Show "This works in the NavigateEd app on your phone for now." | `UiUtils.showMobileOnlyNotice()`; uploads from the browser come in Phase 1 |
| Addresses | n/a | Path URLs (`/notices`, no `#`); every visit starts at the splash screen, and an address the app understands opens its screen after sign-in | `usePathUrlStrategy()`, `onGenerateInitialRoutes`, the deep link table |
| Platform checks | `dart:io` `Platform` | `kIsWeb` and `defaultTargetPlatform`; no `dart:io` call runs on the web | |

## Hosting

`firebase.json` has three Hosting targets: `docs` (the documentation site, unchanged, with its npm predeploy), `app` (`e-school/build/web`) and `teach` (`e-school-teacher/build/web`). The app targets rewrite every path to `/index.html`, send `Cache-Control: no-cache` (Flutter's file names carry no hash) and `X-Content-Type-Options`, `X-Frame-Options: DENY` and `Referrer-Policy`. No predeploy builds Flutter: the deploy workflow (S4) runs `flutter build web --release` with `API_BASE_URL`, `ENV` and `FLAVOR` and then `infra/firebase/deploy-hosting.sh`, which maps each target to the site in `FIREBASE_SITE_<TARGET>`. `.firebaserc` maps the targets for the production project (`navigateed-d8c06`, `navigateed-app`, `navigateed-teach`) for a manual `firebase deploy`.

Each app's `web/manifest.json` and `web/index.html` carry its name (NavigateEd, NavigateEd Teacher), the brand primary as theme colour, white as background colour, and icons made from its launcher icon.

Previews: `.github/workflows/web-preview.yml` deploys the same builds, named as previews and in bearer mode, to the `preview` channel of each portal site (a pull request that changes the workflow gets `pr-<number>`), so the product owner can open and install both apps on a phone before a store or production release. The live channels are untouched. After deploying it checks the chosen server's CORS answer for the preview's origin and that `/api/v2/me/context` exists, and reports both in the job summary. A server running this code accepts a preview only once `CORS_ALLOWED_ORIGINS` lists its address with the portals ([wiki](../../../wiki/Building-and-Releasing-Apps.md#preview-on-a-phone)).

## User stories

- **Learner:** I open `app.navigateed.co.za` on a laptop, sign in with my student number, and see my timetable, assignments and fees as in the app.
- **Parent:** I sign in at `app.navigateed.co.za` and see each child's details, results and fees; my phone keeps getting push notifications.
- **Teacher:** I sign in at `teach.navigateed.co.za` and take attendance and enter marks on a desktop screen.
- **Any portal user:** a link like `app.navigateed.co.za/notices` opens the notice board after I sign in; reloading the page does not break the app.
- **Any portal user:** when I try to attach a file or pay in the browser, the portal tells me to use the phone app, instead of failing.
- **Release engineer:** the same commit gives the phone apps and both web builds; CI proves `flutter build web --release` works for both apps.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| Either app | `flutter build web --release` runs with the staging or production settings | it succeeds | CI `apps-web` |
| A learner, a parent, a teacher in Chrome, against the fake API | they sign in | they land on home and open one screen (profile, child, timetable) | CI `apps-smoke-chrome`, `integration_test/*_smoke_test.dart` |
| The web build | sign-in succeeds | no token is stored; the next request has no `Authorization` header and is sent with credentials | `session_repository_test`, `api_client_test` |
| The web build in cookie mode | it writes without an XSRF cookie / gets 419 | it fetches `/sanctum/csrf-cookie` first / fetches a fresh token and retries once | `xsrf_interceptor_test` |
| The learner portal built in cookie mode on another local port | the demo learner signs in in Chromium with no system language | home opens; sign-in sent `X-XSRF-TOKEN` and no `Authorization`; `/api/v2/me/context` 200; pictures load with no CORS error | S9 browser check ([evidence](../../self-tests/evidence/p0-learner-cookie-sign-in-1280.png)) |
| The web build | a learner, parent or teacher signs in | the request has no `fcm_id` or `device_type` | `NotificationUtility.signInDeviceFields()`, smoke flows (`NotificationUtility.enabled` off) |
| The web build at 390, 820 and 1280 px | any screen opens | no horizontal scroll, nothing cut off: phone layout, rail and column, extended rail | `layout_test`; self-test step 3 |
| A PDF study material in the browser | the learner opens it | it shows in the app (pdf.js); when pdf.js cannot load, only the PDF view fails | self-test step 4 |
| A fees receipt in the browser | the parent downloads it | the browser saves `<name>_fees_payment_receipt_on_<date>.pdf` | self-test step 5 |
| The web build | the user taps "add files" or pays | the mobile-only notice shows; nothing is uploaded | self-test step 6 |
| Any deep path, for example `/home` | the page is reloaded | the splash screen opens and leads home | self-test step 7 |

## Data changes

None on the server. In the browser: Hive boxes (`authBox` without a token, `settings`, `showCaseBox`) in IndexedDB for the portal's origin.

## Endpoints

Unchanged. The web build calls the same v1 endpoints and `GET /api/v2/me/context`, with the session cookie instead of a token. The server side of cookie mode (S6, S9): the portal origins in `SANCTUM_STATEFUL_DOMAINS` and `CORS_ALLOWED_ORIGINS` with credentials, the session and `XSRF-TOKEN` cookies on `.navigateed.co.za` (`SESSION_DOMAIN`), and sign-in endpoints that start a web session for stateful requests and answer `token: ""` (`PortalSession`). Cookie mode needs the panel under the portals' parent domain; on the `run.app` address the deploy workflow builds the portals with `WEB_AUTH_MODE=bearer` and `provision.sh` keeps their origins out of the stateful list ([environments runbook](../../runbooks/environments.md), "Web portal sign-in").

Pictures and PDFs are fetched from the panel's `/storage` with no credentials; Apache answers the portal origins with CORS headers there (`docker/storage-cors.conf`, S9).

## Screens

Every existing screen, in the browser. New: the mobile-only notice (a snackbar). Loading, empty, error and offline states are the existing ones; offline the browser shows the app shell and each screen's error state.

## Notification types

None. Web push (which needs a service worker) is deferred: FCM topics cannot be subscribed from a browser, so it needs a Firebase web app, a VAPID key and server-side sending to browser tokens. Until then the portals show notifications as in-app notices (the Notifications screen).

## Module switch

None.

## Security notes

- No token in browser storage or in the portal's JavaScript (cookie mode): a portal sign-in answers with an empty `token`; the session cookie is the server's (`HttpOnly`, `Secure`, `SameSite=Lax`, on `.navigateed.co.za`), it is regenerated at sign-in and ended at sign-out, and writes need the XSRF header.
- `locale_guard.js` is a file of the build (not inline), so a future content security policy can allow it with `'self'`.
- The hosting headers forbid framing (`X-Frame-Options: DENY`) and MIME sniffing. A content security policy is not set yet: Flutter web needs `wasm-unsafe-eval`, and the build loads CanvasKit from `www.gstatic.com` and pdf.js from `cdn.jsdelivr.net`; add one (report-only first) with those sources.
- pdf.js is loaded from jsDelivr at a pinned version (4.6.82, the one pdfx 2.11 is built for).
- No key or secret is in the web builds; the only settings are the server address, environment and flavour.

## Self-test steps

1. Deploy to staging (or `flutter build web --release --dart-define-from-file=config/staging.json` and serve `build/web`), open the portal in Chrome and Safari.
2. Sign in as a learner, a parent and a teacher (`DemoSchoolSeeder` users): each lands on home; the teacher takes attendance and enters marks; the learner opens the timetable, assignments and fees.
3. Resize the window to 390, 820 and 1280 px on home, the notice board and the fees screen: no horizontal scroll, nothing cut off.
4. Open a PDF study material: it shows in the app.
5. As a parent, download a fees receipt: the browser saves the PDF.
6. Tap "add files" on an assignment and "Pay" on fees: the mobile-only notice shows.
7. Open `<portal>/notices` while signed out: sign in, then the notice board opens. Reload `<portal>/home`: the app starts at the splash screen and goes home.
8. In the browser's storage view, check no `jwtToken` value is stored.

Preview review: pull requests produce artifacts without Firebase credentials. Publishing requires an explicit `deploy_preview` dispatch from `main`. The API defaults to staging and rejects the configured production endpoint.
