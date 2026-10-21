This page describes the architecture the two Flutter apps share: the student/parent app in `e-school/` (Dart package `eschool`) and the teacher app in `e-school-teacher/` (Dart package `eschool_teacher`). Both are thin clients of the Laravel REST API under `/api` in `admin-panel/`; they are built the same way, so almost everything below applies to both. App-specific details are on [Student-Parent-App](Student-Parent-App) and [Teacher-App](Teacher-App).

## At a glance

| | Student/parent app | Teacher app |
|---|---|---|
| Folder | `e-school/` | `e-school-teacher/` |
| `pubspec.yaml` `name` | `eschool` (do not rename: every import uses it) | `eschool_teacher` (same warning) |
| `version` | `3.5.0+39` | `3.5.0+32` |
| Dart SDK constraint (`environment.sdk`) | `>=3.8.0 <4.0.0` | `>=3.8.0 <4.0.0` |
| SDK floor recorded in `pubspec.lock` | `dart: >=3.12.0 <4.0.0`, `flutter: >=3.44.0` | same |
| Flutter used by CI | 3.44.9 (stable) | 3.44.9 (stable) |
| Entry point | `lib/main.dart` → `initializeApp()` in `lib/app/app.dart` | same |
| Android `applicationId` | `tech.tokensoft.navigateed` | `tech.tokensoft.navigateed.teacher` |
| iOS bundle id | `tech.tokensoft.navigateed` | `tech.tokensoft.navigateed.teacher` |
| Logs in as | student (GR number) or parent (email) | teacher (email) |
| Server config keys read | `app_link`, `ios_app_link`, `app_version`, `ios_app_version`, `force_app_update`, `app_maintenance` | `teacher_app_link`, `teacher_ios_app_link`, `teacher_app_version`, `teacher_ios_app_version`, `teacher_force_app_update`, `teacher_app_maintenance` |

The lockfile records `dart >=3.12.0` and `flutter >=3.44.0`, so in practice you need Flutter 3.44 or newer even though `pubspec.yaml` allows Dart 3.8. The FAQ on the docs site also lists Flutter 3.44 (https://shawndaprawn.github.io/Eschool/faqs/).

## Source layout

Both apps use the same folder structure under `lib/`:

| Path | Contents |
|---|---|
| `lib/main.dart` | Calls `initializeApp()`; carries the version comment `///v-3.5.0`. |
| `lib/app/app.dart` | `initializeApp()` (Firebase, Crashlytics, notifications, Hive), `MyApp` with the global `MultiBlocProvider` and `MaterialApp`. |
| `lib/app/routes.dart` | `Routes` class: route-name constants and `onGenerateRouted()` (a `switch` over route names, unknown names give an empty `Scaffold`). |
| `lib/cubits/` | Feature Cubits (one file per Cubit, state classes in the same file). Sub-folders `chat/` and `gallery/`. Some screen-private Cubits live next to their screen, e.g. `e-school/lib/ui/screens/reports/cubits/`. |
| `lib/data/models/` | Plain Dart model classes with `fromJson` / `toJson`. |
| `lib/data/repositories/` | One class per domain (`AuthRepository`, `StudentRepository`, `SystemRepository`, ...). Repositories are the only layer that calls `Api`. |
| `lib/ui/screens/` | Screens. Most screens expose `static Route route(RouteSettings)`, which builds a `CupertinoPageRoute` and provides the screen-local Cubits; a few are wrapped in a `CupertinoPageRoute` directly in `routes.dart`. |
| `lib/ui/widgets/` | Reusable widgets (error containers, shimmer loaders, bottom sheets, force-update dialog, ...). |
| `lib/utils/api.dart` | `Api`: every endpoint URL, and static `post` / `get` / `download` that hand the call to the shared `ApiClient`. Also what the app does on a 401 (`logoutUser`). `ApiException` comes from `eschool_core` and is re-exported here. |
| `lib/utils/constants.dart` | `baseUrl`, `databaseUrl`, `storageUrl` (from the build defines, see [Mobile-App-Configuration](Mobile-App-Configuration#build-settings)), notification channel keys, pagination limit, demo-credential switches. |
| `lib/utils/hiveBoxKeys.dart` | Hive box names and keys, SharedPreferences keys. |
| `lib/utils/appLanguages.dart` | `defaultLanguageCode` and the `appLanguages` list. |
| `lib/utils/labelKeys.dart` | Constants for every translation key; re-exports the keys the shared widgets use from `eschool_core`. |
| `lib/utils/errorMessageKeysAndCodes.dart` | `ErrorMessageKeysAndCode`: maps API error codes to translation keys. |
| `lib/utils/uiUtils.dart` | `UiUtils`: `rootNavigatorKey`, `getTranslatedLabel()`, snack bars, date helpers, `forceUpdate()`, `launchWebUrl()`. The sizes and helpers that were the same in both apps forward to `SharedUiUtils` in `eschool_core`. |
| `lib/utils/notificationUtils/` | `generalNotificationUtility.dart` (`NotificationUtility`) and `chatNotificationsUtils.dart` (`ChatNotificationsUtils`). |
| `lib/utils/fileNameUtils.dart` | `sanitizeFileName()` used before saving server-supplied file names. |
| `lib/utils/assets.dart` | `Assets` class with every image/animation path; the files the shared widgets draw point at `SharedAssets`. |
| `test/translations_test.dart` | Language file consistency. |
| `test/theme_colors_test.dart` | Fails on colours hard-coded outside the shared theme. |

The student app also has `lib/utils/stripeService.dart` (Stripe payment sheet) and `lib/utils/homeBottomsheetMenu.dart` (the "more" menu list).

### What the apps share through `eschool_core`

Files that were the same in both apps live once in [`packages/eschool_core`](https://github.com/ShawnDaPrawn/Eschool/tree/main/packages/eschool_core/lib/src) and both apps import them through `package:eschool_core/eschool_core.dart`:

| Folder in `eschool_core/lib/src/` | What moved there |
|---|---|
| `l10n/` | `AppLocalization` and `AppLocalizationDelegate` (each app passes its own `appLanguages`), the shared label keys |
| `models/` | `StudyMaterial`, `Announcement`, `Topic`, `SubjectMark`, `Result`, `ChatMessage`, `DynamicField`, the leave day types (`LeaveType`, `LeaveDateWithType`), plus the earlier gallery, calendar, chat-settings and event models |
| `cubits/` | `GalleryAlbumCubit`, `GalleryVideosCubit`, `GalleryAlbumPhotosCubit`, `EventDetailsCubit`, `AppSettingsCubit`, each taking an interface from `repositories/` (`GalleryRepository`, `EventScheduleRepository`, `AppSettingsRepository`) that the app's `SystemRepository` implements |
| `widgets/` | 21 of the 30 widget files the apps had in common (error container, shimmer loaders, back button, image and gallery widgets, bottom sheet parts, search field, refresh indicator, ...) and four screen parts (semester break, gallery videos tab, leave day picker, drop-down) |
| `ui/` | `SharedUiUtils` (the sizes and helpers both `UiUtils` had), `SharedAssets` (paths of the images the shared widgets draw, which each app ships), `SharedRoutes` |
| `utils/` | `extensions.dart` and the list animations with their switches (`isApplicationItemAnimationOn` and the durations) |
| `shell/` | `AppShell`, which each app puts above its navigator in `MaterialApp.builder` |

`AppShell` carries the few things the shared widgets do differently per app: the error code to label mapping (`ErrorMessageKeysAndCode`, code 113 means something else in each app), how an attachment opens (`UiUtils.viewOrDownloadStudyMaterial`) and the button drawn next to it (`DownloadFileButton`), and the bottom bar's height. Nine shared widget files still differ in how they look between the apps and stay in each app until the screens that use them are redesigned: `customAppbar`, `customTabBarContainer`, `tabBarBackgroundContainer`, `downloadFileButton`, `downloadFileBottomsheetContainer`, `dynamicFieldListContainer`, `listItemForExamAndResult`, `noDataContainer` and `notificationIconWidget`.

## Startup sequence

`initializeApp()` in [e-school/lib/app/app.dart](https://github.com/ShawnDaPrawn/Eschool/blob/main/e-school/lib/app/app.dart) (the teacher copy has the same steps, with the orientation lock after the notification setup and without the `showCaseBox`):

1. `WidgetsFlutterBinding.ensureInitialized()`; in release mode `debugPrint` is replaced with a no-op.
2. Registers `google_fonts/OFL.txt` with `LicenseRegistry`.
3. Locks orientation to `DeviceOrientation.portraitUp`.
4. `Firebase.initializeApp()` with no options, so Firebase is configured from the native files (`google-services.json`, `GoogleService-Info.plist`). There is no `firebase_options.dart`.
5. Routes `FlutterError.onError` and `PlatformDispatcher.instance.onError` to Firebase Crashlytics.
6. `NotificationUtility.initializeAwesomeNotification()` creates the two Android channels `basic_channel` and `chat_notifications_channel`.
7. `initHiveStorage(...)` initialises Hive in `getApplicationSupportDirectory()` (not Documents, which iOS exposes in the Files app because `UIFileSharingEnabled` is on) and moves boxes left in Documents by older versions; then opens the boxes (`showCaseBox`, `authBox`, `settings` in the student app; `authBox`, `settings` in the teacher app).
8. `runApp(const MyApp())`.

`MyApp` creates the app-wide Cubits in a `MultiBlocProvider` and builds a `MaterialApp` with `initialRoute: Routes.splash`, `onGenerateRoute: Routes.onGenerateRouted`, `navigatorKey: UiUtils.rootNavigatorKey`, and the shared Material 3 theme `AppTheme.light(font: GoogleFonts.poppinsTextTheme)` from `eschool_core` (see [UI-Standards](UI-Standards)).

The splash screen (`lib/ui/screens/splashScreen.dart`) then:

1. Calls `AppConfigurationCubit.fetchAppConfiguration()` (GET `settings?type=app_settings`).
2. On success, calls `UserProfileCubit.fetchAndSetUserProfile()` to refresh the stored profile if someone is logged in. In the student app this is called with `autoLogout: true`, so a failed profile fetch signs the user out. A signed-in parent or teacher whose first sign-in is still waiting on the terms skips this fetch, because the profile call would be HTTP 403, and the splash opens the terms screen instead. Once that acceptance is stored, the splash refreshes the profile and opens home.
3. Waits at least 2 seconds, then routes to the login screen when `AuthCubit` is `Unauthenticated`, otherwise to the home screen. The student app has extra branches (a parent's first sign-in, parent home, compulsory-fee screen), see [Student-Parent-App](Student-Parent-App#login-and-the-two-user-types).

If the configuration request fails the splash shows an `ErrorContainer` with a retry button; the app cannot get past the splash screen without reaching the server.

## State management

All state is held in `flutter_bloc` Cubits (`flutter_bloc` 9.1.1, `bloc` 9.2.1); there are no `Bloc` event classes. Conventions used throughout:

- A Cubit takes its repository in the constructor, e.g. `FeesPaymentCubit(StudentRepository())`.
- States are small classes named `<Feature>Initial`, `<Feature>FetchInProgress`, `<Feature>FetchSuccess`, `<Feature>FetchFailure` (some extend `Equatable`). Failure states carry an `ApiException` or its message.
- Long-lived Cubits that several screens read are provided once in `MyApp` (for example `AuthCubit`, `AppConfigurationCubit`, `AppLocalizationCubit`, chat user lists, gallery). Screen-specific Cubits are provided inside each screen's `route()` factory, so they are created and closed with the route.
- Helper getters on Cubits hide the state type from widgets, e.g. `AppConfigurationCubit.getPaymentOptions()`, `AuthCubit.isParent()`, `DashboardCubit.primaryClass()`.

## How an API call flows

```mermaid
sequenceDiagram
    participant S as Screen (BlocConsumer)
    participant C as Cubit
    participant R as Repository
    participant A as Api (lib/utils/api.dart)
    participant L as Laravel /api
    S->>C: e.g. fetchAppConfiguration()
    C->>C: emit(...InProgress)
    C->>R: fetchSettings(type)
    R->>A: Api.get(url, queryParameters, useAuthToken)
    A->>L: HTTP GET with Bearer token header
    L-->>A: JSON with error, message, code, data
    A-->>R: Map or ApiException
    R-->>C: model objects
    C->>C: emit(...Success / ...Failure)
    C-->>S: rebuild / listener
```

`Api` ([e-school/lib/utils/api.dart](https://github.com/ShawnDaPrawn/Eschool/blob/main/e-school/lib/utils/api.dart), [e-school-teacher/lib/utils/api.dart](https://github.com/ShawnDaPrawn/Eschool/blob/main/e-school-teacher/lib/utils/api.dart)) is a static class. The calls themselves are made by `ApiClient` in the shared [`eschool_core`](Repository-Layout#packageseschool_core) package (`packages/eschool_core/lib/src/api/api_client.dart`), which both apps use, so the behaviour below is the same in both and has its own tests. Each app builds one `ApiClient` in `Api`, passing in how to find the token and what to do on a 401.

- Every endpoint is a `static String` built from `databaseUrl` (`'$baseUrl/api/'`), for example `studentLogin = '${databaseUrl}student/login'`.
- `Api.post({body, url, useAuthToken, queryParameters, cancelToken, onSendProgress, onReceiveProgress})` sends the body as multipart `FormData` (`FormData.fromMap(body, ListFormat.multiCompatible)`), so file uploads and nested arrays go through the same call.
- `Api.get({url, useAuthToken, queryParameters})` sends query parameters only.
- `Api.download({url, cancelToken, savePath, updateDownloadedPercentage})` streams a file to disk with progress.
- `ApiClient` creates a new `Dio()` per call; there are no interceptors, base options or timeouts configured.
- `headers()` reads the token from Hive (`authBox` / `jwtToken`) and returns `{'Authorization': 'Bearer <token>'}` when `useAuthToken` is true.

Response and error handling:

| Situation | Result |
|---|---|
| HTTP 2xx and `error == false` | `Map` of the response body returned to the repository. |
| HTTP 2xx and `error == true`, `code` is null and `message` is set | `ApiException(message, isApiMessage: true)`; the message is shown as is. |
| HTTP 2xx and `error == true` with a `code` | `ApiException(code)`; `UiUtils.getErrorMessageFromErrorCode()` maps the code to a translated label. |
| HTTP 401 | `Api.logoutUser()`: removes notification listeners, calls `AuthCubit.signOut()` and resets the navigator to the login route; throws code `401`. |
| HTTP 500 or 503 | code `500` (`internetServerError`). |
| `SocketException` | code `301` (`noInternet`). |
| anything else | code `302` (`defaultErrorMessage`). |
| download 404 | code `404` (`fileDownloadingFailed`). |

API error codes mapped in `ErrorMessageKeysAndCode.getErrorMessageKeyFromCode()`: `101` invalid login credentials, `104` assignment already submitted, `105` online exam already attended (student app), `106` online exam not started (student app), `107` invalid user details, `108` cannot send reset-password request, `109` invalid password, `112` feature disabled in demo mode, `113` student not in the current session year. Any other code shows the default error message. The server side of this envelope is described on [API-Overview](API-Overview).

Most repository methods catch exceptions and rethrow `ApiException(e.toString())`, and Cubits wrap anything else with `ApiException.fromException()`, so failure states carry an `ApiException`.

## Session and token storage

Login (`student/login`, `parent/login`, `teacher/login`) returns a Laravel Sanctum personal access token in `token` (the key in the app is still called `jwtToken`). The app also sends `fcm_id` (from `FirebaseMessaging.instance.getToken()`, empty string if it fails, e.g. on simulators) and `device_type` (`android` or `ios`), which the server uses for push notifications.

Hive boxes and keys (`lib/utils/hiveBoxKeys.dart`):

| Box | Key | Meaning |
|---|---|---|
| `authBox` | `jwtToken` | Bearer token sent by `Api.headers()`. |
| `authBox` | `isLogIn` | Whether a session exists; read by `AuthCubit` at startup. |
| `authBox` | `hasSubscribedToNotificationTopics` | Prevents re-subscribing to FCM topics on every login. |
| `authBox` | `isStudentLogIn`, `studentDetails`, `parentDetails` | Student app only: user type and cached profile JSON. |
| `authBox` | `teacherDetails` | Teacher app only: cached profile JSON. |
| `settings` | `currentLanguageCode` | Selected language (falls back to `defaultLanguageCode`). |
| `settings` | `allowNotification` | Student app only. |
| `showCaseBox` | `showHomeScreenGuide` | Student app only. |

SharedPreferences keys `notificationCount` and `chatNotificationsBackgroundItem` hold data that is also written by the FCM background handler `onBackgroundMessage()`, which runs in its own isolate; `SettingsRepository` calls `prefs.reload()` before every read and write.

Sign-out (`AuthCubit.signOut()`) unsubscribes from the FCM topics and calls `AuthRepository.signOutUser()`, which posts to `logout` (fire and forget; the server deletes the current token) and clears `isLogIn`, `jwtToken` and the cached profiles. Tokens expire on the server after `SANCTUM_EXPIRATION` minutes (default `60 * 24 * 30`, i.e. 30 days, in `admin-panel/config/sanctum.php`); the next request then gets a 401 and the app logs out.

Data protection on Android: both apps set `android:allowBackup="false"` and use `res/xml/data_extraction_rules.xml` to exclude all app data from cloud backup and device transfer, so the token is not copied to other devices. The student app's release `network_security_config.xml` allows HTTPS only; see [Mobile-App-Configuration](Mobile-App-Configuration#https-requirement).

## App configuration from the server

`AppConfigurationCubit` (`lib/cubits/appConfigurationCubit.dart`) calls `SystemRepository.fetchSettings(type: 'app_settings')`, i.e. `GET /api/settings?type=app_settings` without a token, and parses the result into `AppConfiguration` (`lib/data/models/appConfiguration.dart`). The response is built in `ApiController::getSettings()` in `admin-panel/app/Http/Controllers/Api/ApiController.php` from the `settings` table. Fields the apps use:

| JSON field | Used for | App |
|---|---|---|
| `app_link`, `ios_app_link` (`teacher_app_link`, `teacher_ios_app_link`) | Store link opened by the force-update dialog and by the share/rate entries in settings. | both |
| `app_version`, `ios_app_version` (`teacher_*`) | Version compared with the installed one; also shown as "app version" in settings. | both |
| `force_app_update` (`teacher_force_app_update`) | `'1'` enables the force-update check. | both |
| `app_maintenance` (`teacher_app_maintenance`) | `'1'` replaces the home screen with `AppUnderMaintenanceContainer`. | both |
| `school_name`, `school_tagline` | Login screen text, Stripe merchant name. | both |
| `session_year`, `current_semester`, `semester_breaks`, `holiday_days` | Calendars, leave forms, filters. | both |
| `chat_settings` | `max_files_or_images_in_one_message` (default 10), `max_file_size_in_bytes` (default 10000000), `max_characters_in_text_message` (default 500), `automatically_messages_removed_days`. | both |
| `is_demo` | Value of the server's `DEMO_MODE` env; `UiUtils.isDemoVersionEnable()` is checked on the login screens and before changing the password. | both |
| `online_payment` | `'1'` shows the "pay" buttons. | student |
| `compulsory_fee_payment_mode`, `is_student_can_pay_fees` | Compulsory-fee gate and whether students may pay their own fees. | student |
| `payment_options` | Currency code/symbol, due date/charges and the public keys of each enabled gateway (`stripe`, `paystack`, `flutterwave`; a `razorpay` entry from an older server is ignored). Secret keys are never sent. | student |
| `online_exam_terms_condition` | Rules shown before an online exam. | student |

These values are edited in the admin panel under App Settings (`SettingController::app_index` / `app_update`, route `app-settings`); see https://shawndaprawn.github.io/Eschool/admin-panel/app-settings/. Other `type` values of the same endpoint (`privacy_policy`, `contact_us`, `about_us`, `terms_condition`) feed the static pages through `AppSettingsCubit`.

### Maintenance mode and force update

Both home screens check the configuration on every build:

- If `appUnderMaintenance()` is true, the body is `AppUnderMaintenanceContainer` (image `assets/images/branding/maintenance.svg`, label `appUnderMaintenance`) instead of the normal UI. The splash screen and login screens are not affected.
- If `forceUpdate()` is true, `UiUtils.forceUpdate(getAppVersion())` compares the server version with `PackageInfo` (`version+buildNumber`). If it returns true, `ForceUpdateDialogContainer` is drawn over the home screen with a single "update" button that opens the store link. The dialog cannot be dismissed.

`UiUtils.forceUpdate()` details worth knowing:

- An empty server version disables the check.
- The `x.y.z` parts are compared one position at a time, and any position where the server number is larger triggers an update. This means a server value of `3.4.1` triggers the dialog for an installed `3.5.0`. Always set the server value to the exact version you released.
- If both sides have a `+build` suffix, a larger server build number also triggers an update, independently of the version part.
- Each part must be an integer; a two-part value such as `3.5` makes the check throw and no dialog is shown.
- On iOS `PackageInfo` reads `CFBundleShortVersionString`/`CFBundleVersion`. The Xcode projects hard-code `FLUTTER_BUILD_NUMBER = 3` (see [Building-and-Releasing-Apps](Building-and-Releasing-Apps#versioning)), so with force update on, an `ios_app_version` with a `+39` suffix makes the dialog appear on every launch.

## Push notifications

Both apps use `firebase_messaging` for delivery and `awesome_notifications` (not `flutter_local_notifications`) to display notifications on Android.

- After login the apps subscribe to FCM topics: student app `studentsAndroid`/`studentsIOS` or `parentsAndroid`/`parentsIOS` plus `allAndroid`/`allIOS`; teacher app `teachersAndroid`/`teachersIOS` plus `allAndroid`/`allIOS`. These match the topic map in `sendNotificationToTopic()` in `admin-panel/app/Helpers/notification_helper.php`. Individual notifications are sent to the stored `fcm_id` instead.
- Android messages are data-only; `NotificationUtility.createLocalNotification()` builds the visible notification (big picture when `image` is set). iOS messages carry a `notification` block and are shown by the system.
- The payload `type` decides the tap action (`noticeboard`, `assignment`, `custom`, `Online`, `fees-due`, `chat`, ...). Chat messages go through `ChatNotificationsUtils` and the `chat_notifications_channel`.
- The unread badge count is kept in SharedPreferences (`notificationCount`); types in `notificaitonTypesToNotIncrementCount` (notice board, class, chat and, in the student app, fee-due) do not increment it.

Server-side sending is covered on [Notifications](Notifications); chat on [Chat](Chat).

## Key dependencies

Constraint from `pubspec.yaml`, resolved version from `pubspec.lock`.

| Package | Constraint | Locked | Student | Teacher | Used for |
|---|---|---|---|---|---|
| `flutter_bloc` | `^9.1.1` | 9.1.1 | yes | yes | Cubits, `BlocProvider`, `BlocConsumer`. |
| `bloc` | `any` | 9.2.1 | yes | yes | `Cubit` base class. |
| `dio` | `^5.9.2` | 5.11.1 | yes | yes | All HTTP calls (`Api`). `http` is only a transitive dependency. |
| `hive` / `hive_flutter` | `^2.2.3` / `^1.1.0` | 2.2.3 / 1.1.0 | yes | yes | Token, profile and settings storage. |
| `shared_preferences` | `^2.5.5` | 2.5.6 | yes | yes | Notification count and background chat items. |
| `firebase_core` | `^4.7.0` | 4.15.0 | yes | yes | Firebase initialisation. |
| `firebase_messaging` | `^16.2.0` | 16.7.0 | yes | yes | FCM token, topics, message streams. |
| `firebase_crashlytics` | `^5.2.0` | 5.4.0 | yes | yes | Crash reporting. |
| `firebase_analytics` | `^12.3.0` | 12.6.0 | yes | yes | Declared only; not referenced in Dart code. |
| `awesome_notifications` | `^0.11.0` | 0.11.0 | yes | yes | Local notification display and tap handling. |
| `permission_handler` | `^12.0.1` | 12.0.3 | yes | yes | Notification and storage permissions. |
| `google_fonts` | `^8.1.0` | 8.2.1 | yes | yes | Poppins text theme (font files bundled in `google_fonts/`). |
| `flutter_svg` | `^2.2.4` | 2.3.0 | yes | yes | SVG icons and branding images. |
| `lottie` | `^3.3.3` | 3.6.1 | yes | yes | `assets/animations/*.json`. |
| `flutter_animate` | `^4.5.2` | 4.5.2 | yes | yes | List/item appearance effects. |
| `cached_network_image` | `^3.4.1` | 3.4.1 | yes | yes | Remote images. |
| `flutter_html` | `^3.0.0` | 3.0.0 | yes | yes | HTML from the server (about/privacy/terms/contact pages, online exam rules). |
| `url_launcher` | `^6.3.2` | 6.3.3 | yes | yes | External links (only http/https via `UiUtils.launchWebUrl`). |
| `webview_flutter` | `^4.13.1` | 4.14.1 | yes | yes | Paystack/Flutterwave checkout (student), web content. |
| `youtube_player_iframe` | `^6.0.2` | 6.0.2 | yes | yes | YouTube study material and gallery videos. |
| `file_picker` | `12.0.0-beta.7` (pinned) | 12.0.0-beta.7 | yes | yes | Attachments. |
| `image_picker` | `^1.2.2` | 1.2.4 | yes | yes | Camera/gallery images. |
| `path_provider` / `external_path` | `^2.1.5` (`any` in teacher) / `^2.2.0` | 2.1.6 / 2.2.0 | yes | yes | Save locations for downloads. |
| `open_filex` | `^4.7.0` | 4.7.0 | yes | yes | Open downloaded files. |
| `flutter_pdfview` | `^1.4.4` | 1.4.5 | yes | yes | In-app PDF viewer. |
| `package_info_plus` | `^10.1.0` | 10.2.2 | yes | yes | Installed version for force update. |
| `table_calendar` | `^3.2.0` | 3.2.1 / 3.3.0 | yes | yes | Attendance and academic calendars. |
| `intl` | `any` | 0.20.2 / 0.20.3 | yes | yes | Date formatting. |
| `timeago` | `^3.7.1` | 3.7.1 | yes | yes | Relative times, localised from the language JSON. |
| `flutter_stripe` | `^12.6.0` | 12.6.0 | yes | no | Stripe payment sheet. |
| `flutter_tex` | `4.0.9` (pinned) | 4.0.9 | yes | no | Maths in online exam questions. |
| `wakelock_plus` | `^1.5.2` | 1.8.0 | yes | no | Keeps the screen on during online exams. |
| `video_player` | `^2.11.1` | 2.14.1 | yes | no | Uploaded study-material videos. |
| `fl_chart` | `^1.2.0` | 1.2.0 | yes | no | Report charts. |
| `carousel_slider` | `^5.1.2` | 5.1.2 | yes | no | Home sliders. |
| `equatable` | `^2.0.8` | 2.1.0 | yes | no | State equality. |
| `webview_flutter_plus` | override `0.4.7` | 0.4.7 | yes | no | Pinned through `dependency_overrides`. |
| `showcaseview` | `^5.0.2` | 5.1.0 | no | yes | Declared; not referenced in Dart code. |
| `dotted_line` | `^3.3.0` | 3.3.0 | no | yes | UI separators. |

Dev dependencies in both apps: `flutter_lints ^6.0.0` (6.0.0), `flutter_launcher_icons ^0.14.4` (0.14.4), `change_app_package_name ^1.5.0` (1.5.0), `flutter_test`.

Native Android dependencies added in `android/app/build.gradle.kts`: `androidx.multidex:multidex:2.0.1`, Firebase BoM `31.0.2` with `firebase-messaging`, `androidx.emoji2` 1.4.0 (works around a crash when pressing back during a Stripe payment).

## Static analysis

`analysis_options.yaml` in both apps includes `package:flutter_lints/flutter.yaml` and ignores `file_names` and `use_build_context_synchronously`. CI runs `flutter analyze --no-fatal-infos`, so info-level lints are reported but only warnings and errors fail the job. See [Building-and-Releasing-Apps](Building-and-Releasing-Apps#what-ci-checks).

## See also

- [Student-Parent-App](Student-Parent-App)
- [Teacher-App](Teacher-App)
- [Mobile-App-Configuration](Mobile-App-Configuration)
- [Building-and-Releasing-Apps](Building-and-Releasing-Apps)
- [Localization](Localization)
- [API-Overview](API-Overview)
- [Notifications](Notifications)
- [Architecture](Architecture)
