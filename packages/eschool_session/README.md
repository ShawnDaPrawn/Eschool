# eschool_session

Who is signed in and what the server says about them, shared by both
NavigateEd apps (`e-school`, `e-school-teacher`). Depend on it by path and
import the one library:

```yaml
dependencies:
  eschool_session:
    path: ../packages/eschool_session
```

```dart
import 'package:eschool_session/eschool_session.dart';
```

## What is here

| Part | Files in `lib/src/` | What it does |
|---|---|---|
| Session store | `auth/session_repository.dart` | `SessionRepository`: signed in or not, the bearer token, whether FCM topics are subscribed. `HiveSessionRepository` keeps them in Hive's `authBox` under the keys both apps have always used (`jwtToken`, `isLogIn`, `hasSubscribedToNotificationTopics`), so an update signs nobody out. `InMemorySessionRepository` is for tests. |
| Auth mode | `auth/auth_mode.dart`, `auth/session_dio*.dart` | `AuthMode.current`: `bearer` on phones; `cookie` on the web, where Sanctum's session cookie for the portal origins identifies the user, no token is stored, requests carry credentials and writes send `X-XSRF-TOKEN`. `--dart-define=WEB_AUTH_MODE=bearer` switches a web build back to tokens. `createSessionDio()` builds the matching `Dio` for `ApiClient`. |
| `/me/context` | `context/` | `MeContext` (the answer as the server sent it, each part decoded with the generated `eschool_api` models: user and roles, schools with roles, current school, modules with `read_only`, notification rules, feature flags, sign-in methods, a guardian's `children` as `MeChild` and their `approval`; unknown keys are ignored and `has()` says whether the server sent a key), `ApiMeContextRepository` for `GET /api/v2/me/context`, `HiveMeContextCache` (with when it was fetched), `MeContextCubit` (`refreshIfStale()`, five minutes) and `MeContextRefresher` (fetches again on resume when stale, or after a `context.changed` push). A server older than API v2 answers 404; the cubit then says `MeContextUnavailable` and the app carries on as before. |
| Child and school switchers | `selection/` | `ChildSelectionCubit` (a parent's children, the one on show) with `ChildSwitcherFrame`, `ChildSwitcherBar`, `ChildSwitcherRail` and `showChildPicker`; `SchoolSelectionCubit` (the user's schools, the one chosen) with `SchoolSwitcherButton` and `showSchoolPicker`, hidden for a user at one school; `SelectionStore` keeps both choices in `authBox` (`selectedChildId`, `selectedSchoolId`); `SchoolHeaderInterceptor` (`withSchoolHeader`) sends `X-School-Id`. |
| Availability | `availability/` | `EndpointAvailability.check()` asks once whether the server has a new endpoint (a 404 means not yet) and remembers it until `forget()`; `AvailableOnly` shows an entry point only when it has. |
| Force update | `update/force_update.dart` | `ForceUpdate.isRequired()` is the apps' version comparison, unchanged (quirks documented on the method); `ForceUpdate.check()` compares with the installed build; `ForceUpdate.forPlatform()` picks the Android or iOS value, nothing on the web. |
| Deep links | `deep_links/` | `DeepLinkParser` maps portal URLs (`https://app.navigateed.co.za/notices`), Flutter web hash URLs and `navigateed://` links to the app's existing route names; `DeepLinkCoordinator` reads them through `app_links` and keeps the link that opened the app until home is shown (`takePendingWhere` lets an invitation open before sign-in). `PushLink` and `openPushLink` open a push's `link` after switching to its `school_id`. Each app lists its links in `lib/app/deepLinks.dart`. |

## Checks

```sh
flutter pub get
flutter analyze --no-fatal-infos
flutter test
```

CI runs the same commands for this package.
