# App foundations

Phase 0, feature 0.9 (and the app part of 0.13). Owner role: App. Build Bible section 3.4 and Phase 0 step 9; [ADR 0003](../../adr/0003-app-packages-and-web.md). Work stream S2.

Nothing changes on a phone: same screens, same look, same behaviour. Underneath, the two apps get their settings from the build, share their code through packages, open screens with typed arguments, lay out for tablets and desktops, and stop shipping Razorpay.

## What changes

| Part | Before | After |
| --- | --- | --- |
| Build settings | `baseUrl` and friends hard-coded in each app's `lib/utils/constants.dart` | `AppConfig` in `eschool_core` reads `--dart-define` `API_BASE_URL`, `ENV` (`production`, `staging`, `local`) and `FLAVOR` (`learner`, `teacher`); `config/production.json` and `config/staging.json` in each app. A build with no define talks to the production Cloud Run address, as before. A debug build with unusable values stops with the reason (`AppConfig.assertValid()`) |
| Shared code | 33 byte-identical files and about 30 near-identical widgets copied in both apps | Moved into `packages/eschool_core` (models, cubits, repositories, widgets, `SharedUiUtils`, `SharedAssets`, `SharedRoutes`, label keys) and imported by both apps. Where the apps differ (snackbars, routes, assets) each app passes its own through `AppShell` |
| Session | Each app reads and writes Hive's `authBox` itself | `packages/eschool_session`: `SessionRepository` over the same `authBox` keys (`jwtToken`, `isLogIn`, `hasSubscribedToNotificationTopics`), `AuthMode` (bearer token on phones, Sanctum cookie on the web), `createSessionDio`, `MeContextCubit` for `GET /api/v2/me/context`, `ForceUpdate`, `DeepLinkParser` and `DeepLinkCoordinator` (`app_links`), `initAppStorage` |
| Notifications | `NotificationUtility`, chat notification helpers and their constants copied in both apps | `packages/eschool_notifications`, configured once per app with its `NotificationConfig` (audience, routes, background handler) |
| Route arguments | `Map<String, dynamic>` read with string keys | One small class per route that takes several values (`lib/app/routeArguments.dart`, 11 in each app); `Routes` and `onGenerateRoute` stay |
| Layout | Phone only | `Breakpoints` (phone below 600 px, tablet to 1024 px, desktop above), `ContentColumn` (a centred 640 px column on wider screens), `AdaptiveScaffold` (the bottom bar on a phone, a navigation rail on a tablet, an extended rail on a desktop) and `AdaptivePageRoute` (every pushed screen in a `ContentColumn`) in `eschool_core`, used by every screen |
| Razorpay | `razorpay_flutter` in the learner app, its WebView callbacks and ProGuard rules | Removed. Stripe stays; the WebView payment screen stays dormant |

Nine shared widgets that still differ visually between the apps (for example the app bars) stay in each app; merging them would change how one app looks. They are listed in the wiki's Mobile-Apps-Overview page.

## User stories

- **Learner, parent and teacher:** after updating the app I am still signed in, my language is kept and every screen looks and works as before.
- **Learner, parent and teacher on a tablet or desktop:** the app uses the width: navigation on the side, content in a readable column, nothing stretched across the screen.
- **Learner, parent and teacher:** a link to a portal page (`https://app.navigateed.co.za/notices`, `https://teach.navigateed.co.za/notifications`) opens the same screen in the app once I am signed in.
- **Release engineer:** I build staging and production from the same commit by changing only `--dart-define-from-file`; no address or key is in the code.
- **App developer:** a fix to a shared widget, model or the session is made once and both apps get it; the session cubit and deep link parser have tests.
- **School admin:** the app shows the server's `/me/context` (branding, roles, modules) when the server has it, and carries on without it when it does not.

## Acceptance tests

| Given | When | Then | Test |
| --- | --- | --- | --- |
| A build with no defines | it starts | it talks to the production address, `ENV` is `production`, the flavour is the app's own | `app_config_test` |
| `API_BASE_URL` with a trailing slash, no scheme, or `http` outside `ENV=local`; an unknown `ENV` | a debug build starts | it stops with the reason | `app_config_test` |
| A signed-in phone app from before this change | it updates | the user is still signed in: the token is read from the same `authBox` keys | `session_repository_test` |
| A web build (`AuthMode.cookie`) | sign-in succeeds | no token is written; requests go with credentials and no `Authorization` header | `session_repository_test`, `api_client_test` |
| A server without `/api/v2/me/context` (404) | the app reads the context | it carries on with no context; a later error keeps the last good answer | `me_context_cubit_test` |
| A cached context and a sign-out | the user signs out | the cached context is removed | `me_context_cubit_test` |
| `https://app.navigateed.co.za/notices`, `navigateed://notices`, the hash form `/#/notices`, an unknown host or path | the parser reads it | the first three give the notice board route; the others give nothing | `deep_link_parser_test` |
| A link while the user is on the splash or sign-in screens | the user reaches home | the linked screen opens then, once | `deep_link_parser_test`, `routes_test` |
| Server version above the installed one with force update on | the app starts | the force-update dialog shows | `force_update_test` |
| Every route name | it is generated with no or the wrong arguments | a screen opens (with defaults) instead of a crash | `routes_test` (both apps) |
| Widths 390, 820 and 1280 px | a shared widget, `ContentColumn`, `AdaptiveScaffold` or `AdaptivePageRoute` is drawn | phone unchanged; tablet a rail with labels and a 640 px column; desktop an extended rail | `layout_test`, `shared_widgets_test` |
| Every label the shared code uses | the apps' tests run | each is in each app's language files | `shared_label_keys_test`, `translations_test` |
| A learner, a parent, a teacher | they sign in against the fake API | they land on home and open one screen, on an Android emulator and in Chrome | `integration_test/*_smoke_test.dart` |

## Data changes

None on the server. On the device: no Hive box or key is renamed. New keys: `meContext` in `authBox` (the last `/me/context` answer, removed at sign-out). Hive's boxes stay in the app's private support directory (moved there from Documents earlier); `initAppStorage` keeps moving boxes an older build left in Documents.

## Endpoints

Read, not changed: `GET /api/v2/me/context` (S7), with `Authorization: Bearer` on phones and the session cookie on the web; a 404 is treated as "not available on this server". Every v1 call is unchanged. Sign-in requests send `fcm_id` and `device_type` as before on phones (now through `NotificationUtility.signInDeviceFields()`).

## Screens

No new screen and no visual change on a phone. On a tablet or desktop the home screens show a navigation rail instead of the bottom bar, and every other screen sits in a centred column on the `surfaceContainerLow` background. Loading, empty, error and offline states are the existing ones.

## Notification types

None new. The FCM topics (`studentsAndroid`, `parentsIOS`, `teachersAndroid`, `allAndroid` and so on) and the payload `type` values are unchanged; they moved into `eschool_notifications`.

## Module switch

None. `MeContext.modules` is read and kept for Phase 1's `ModuleGate`; it is empty in Phase 0.

## Security notes

- No secret or key is compiled into the apps: the only build settings are the server address, the environment and the flavour.
- The token stays in the app's private Hive box on phones; the web build never stores it (cookie mode, see [web-builds.md](web-builds.md)).
- Deep links only open screens that already exist and only after sign-in; unknown hosts and paths are ignored, and a link never carries a token.
- Removing `razorpay_flutter` drops a payment SDK, its WebView callbacks and its ProGuard rules from the learner app.

## Self-test steps

1. Install the previous release on an Android phone, sign in as a learner, then install this build over it: still signed in, same language, home looks the same.
2. Build with `--dart-define-from-file=config/staging.json` and confirm in the server log that requests reach staging.
3. On a tablet (or a resized emulator, about 820 px wide) open home: a navigation rail with labels; open the notice board: a centred column. At 1280 px: an extended rail.
4. With the app signed in, open `navigateed://notices` (`adb shell am start -a android.intent.action.VIEW -d navigateed://notices`): the notice board opens.
5. As a parent, open Fees: Stripe is offered when the school has it on; Razorpay never appears.
6. Run `flutter test` in both apps and the three packages, and `flutter test integration_test` in both apps on an emulator.
