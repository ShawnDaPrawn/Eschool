This page lists every value a deployer has to set before shipping the two Flutter apps, with the exact file and identifier for each: server URL, app name, package name / bundle id, Firebase files, branding, colours, fonts, animations, signing and the server-side settings the apps read. Paths are relative to the app folder (`e-school/` for the student/parent app, `e-school-teacher/` for the teacher app) unless stated otherwise. The click-by-click guides are in the docs site's Mobile App section (https://shawndaprawn.github.io/Eschool/mobile-app/); this page gives the precise locations those guides refer to.

## Checklist

| # | Setting | Student/parent app | Teacher app |
|---|---|---|---|
| 1 | Admin panel URL, environment, flavour | `--dart-define` at build time (`config/production.json`, `config/staging.json`); see [Build settings](#build-settings) | same |
| 2 | Android app name | `android/app/src/main/AndroidManifest.xml` → `android:label="eSchool"` | same file → `android:label="eschool Teacher"` |
| 3 | iOS app name | `ios/Runner/Info.plist` → `CFBundleDisplayName` `eSchool`, `CFBundleName` `eSchool`; also `INFOPLIST_KEY_CFBundleDisplayName` in `ios/Runner.xcodeproj/project.pbxproj` | `CFBundleDisplayName` `eSchool Teacher`, `CFBundleName` `eSchool_teacher`; also `INFOPLIST_KEY_CFBundleDisplayName` in `project.pbxproj` |
| 4 | Android package | `android/app/build.gradle.kts` → `namespace` and `applicationId` = `za.co.example.eschool`; manifest `package`; `android/app/src/main/kotlin/za/co/example/eschool/MainActivity.kt` | `za.co.example.eschool.teacher`; `android/app/src/main/kotlin/za/co/example/eschool/teacher/MainActivity.kt` |
| 5 | iOS bundle id | `PRODUCT_BUNDLE_IDENTIFIER = za.co.example.eschool` (Debug/Release/Profile) in `ios/Runner.xcodeproj/project.pbxproj` | `za.co.example.eschool.teacher` |
| 6 | Firebase Android | add `android/app/google-services.json` | add `android/app/google-services.json` |
| 7 | Firebase iOS | add `ios/Runner/GoogleService-Info.plist` | add `ios/Runner/GoogleService-Info.plist` |
| 8 | Branding | `assets/images/branding/` | `assets/images/branding/` |
| 9 | Launcher icons | `flutter_launcher_icons` block in `pubspec.yaml` | same |
| 10 | Colours | `packages/eschool_core/lib/src/theme/brand_colors.dart` (shared) | same file |
| 11 | Font | `google_fonts/` + `GoogleFonts.poppinsTextTheme` in `lib/app/app.dart` | same |
| 12 | Languages | `lib/utils/appLanguages.dart`, `assets/languages/` | same |
| 13 | Release signing | `android/key.properties` + keystore | same |
| 14 | Version | `version:` in `pubspec.yaml` (and the iOS project values, see [Building-and-Releasing-Apps](Building-and-Releasing-Apps#versioning)) | same |
| 15 | Server-side | Admin panel App Settings (`app_*` keys), notification settings (Firebase project id and service account) | `teacher_*` keys |

## Build settings

Both apps read their server address, environment and flavour from `--dart-define` values given when the app is compiled, through `AppConfig` in the shared package ([`packages/eschool_core/lib/src/config/app_config.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/config/app_config.dart)). Staging and production builds of an app are the same code with different defines.

| Define | Meaning | Default when not given |
|---|---|---|
| `API_BASE_URL` | The admin panel's public URL, with no trailing `/`. Must be `https://` unless `ENV=local`. | `https://navigateed-admin-972068206844.africa-south1.run.app` (the production server every earlier build used) |
| `ENV` | `production`, `staging` or `local` | `production` |
| `FLAVOR` | Which shell the build is: `learner` (student and parent app) or `teacher`. `facilitator` and `vendor` are reserved for the teacher app's later flavours. | `learner` in `e-school`, `teacher` in `e-school-teacher` |

Each app has a define file per environment:

| File | `API_BASE_URL` | `ENV` |
|---|---|---|
| `config/production.json` | `https://navigateed-admin-972068206844.africa-south1.run.app` | `production` |
| `config/staging.json` | `https://staging-admin.navigateed.co.za` | `staging` |

```bash
flutter run                                             # production server, as before
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000 --dart-define=ENV=local   # a local panel from the Android emulator
flutter build appbundle --release --dart-define-from-file=config/staging.json
flutter build web --release --dart-define-from-file=config/production.json
```

A plain `flutter run` or `flutter build` with no define still talks to the production server, so existing release scripts keep working. A debug build with an unusable value (a trailing `/`, `http://` outside `ENV=local`, an unknown `ENV`) stops at start-up with the reason (`AppConfig.assertValid()` in `initializeApp()`); a release build is never stopped.

`lib/utils/constants.dart` keeps the names the code already uses, now read from `AppConfig`:

```dart
const String baseUrl = AppConfig.apiBaseUrl;
const String databaseUrl = AppConfig.apiUrl;      // '$baseUrl/api/'
const String storageUrl = AppConfig.storageUrl;   // '$baseUrl/storage/'
String get appFlavor => AppConfig.flavorOr('learner');   // 'teacher' in the teacher app
```

- Every endpoint in `lib/utils/api.dart` is `'${databaseUrl}...'`.
- `storageUrl` is used for server files referenced by relative path (notification images, dynamic-field files).
- The student app also uses `baseUrl` for the "school website" link on the login choice screen and, in `WebViewPaymentScreen`, as the host on which the Paystack/Flutterwave callback pages (`/response/paystack/success`, `/response/flutterwave/success`) are recognised. If the admin panel is served from a different host than `API_BASE_URL`, web payments never complete in the app.
- Docs: https://shawndaprawn.github.io/Eschool/mobile-app/integrate-admin-panel/

### HTTPS requirement

| | Release | Debug |
|---|---|---|
| Student app (Android) | `android:usesCleartextTraffic="false"` and `res/xml/network_security_config.xml` (`cleartextTrafficPermitted="false"`, system CAs only) | `src/debug/AndroidManifest.xml` turns cleartext on and `src/debug/res/xml/network_security_config.xml` trusts system and user CAs, so a local `http://` server or a debugging proxy works |
| Teacher app (Android) | no network security config; Android's default for the target SDK applies (cleartext blocked) | no debug override, so plain `http://` is blocked in debug too |
| Both (iOS) | no `NSAppTransportSecurity` entry in `Info.plist`, so App Transport Security requires HTTPS | same |

Use an `https://` `API_BASE_URL` with a certificate from a public CA for any build you hand out.

## App name

- Android: `android:label` on `<application>` in `android/app/src/main/AndroidManifest.xml`.
- iOS: `CFBundleDisplayName` in `ios/Runner/Info.plist` and `INFOPLIST_KEY_CFBundleDisplayName` in `ios/Runner.xcodeproj/project.pbxproj` (Xcode's "Display Name" field, as the guide shows).
- Docs: https://shawndaprawn.github.io/Eschool/mobile-app/change-app-name/

The Dart package names in `pubspec.yaml` (`eschool`, `eschool_teacher`) are not user-visible. The comment at the top of each `pubspec.yaml` warns not to change them, because every `import 'package:eschool/...'` would break.

## Package name and bundle id

Places that hold the Android package (student values shown; the teacher app adds `.teacher`):

| File | Value |
|---|---|
| `android/app/build.gradle.kts` | `namespace = "za.co.example.eschool"` |
| `android/app/build.gradle.kts` | `applicationId = "za.co.example.eschool"` |
| `android/app/src/main/AndroidManifest.xml` | `package="za.co.example.eschool"` |
| `android/app/src/main/kotlin/za/co/example/eschool/MainActivity.kt` | `package za.co.example.eschool` (folder path must match) |
| `ios/Runner.xcodeproj/project.pbxproj` | `PRODUCT_BUNDLE_IDENTIFIER = za.co.example.eschool;` (three build configurations) |

Both apps list `change_app_package_name` (1.5.0) as a dev dependency. The docs page uses `flutter pub run change_app_package_name:main your_new_package_name` for Android and Xcode for iOS: https://shawndaprawn.github.io/Eschool/mobile-app/change-package-name/. After running it, check every row of the table above, because the Gradle files are Kotlin DSL (`.kts`) since 3.5.0. The `google-services.json` and `GoogleService-Info.plist` you download must be registered in Firebase for the new ids.

`MainActivity` differs between the apps: the student app extends `FlutterFragmentActivity` (needed by `flutter_stripe`), the teacher app extends `FlutterActivity`. Keep that when moving the file.

## Firebase

The apps call `Firebase.initializeApp()` with no options, so configuration comes from the native files. There is no `firebase_options.dart` in either app. Both apps are registered in Firebase project `navigateed-d8c06`, and the client config files are committed:

| File | Expected at | Status in the repo |
|---|---|---|
| `google-services.json` | `e-school/android/app/`, `e-school-teacher/android/app/` | Present. Each file lists both Android clients; the Google services plugin selects the one matching `applicationId`. Required by the `com.google.gms.google-services` Gradle plugin (applied in `android/app/build.gradle.kts`). Not listed in any `.gitignore`. |
| `GoogleService-Info.plist` | `e-school/ios/Runner/`, `e-school-teacher/ios/Runner/` | Present, and referenced as a resource of the `Runner` target in `ios/Runner.xcodeproj/project.pbxproj`. The student plist bundle id is `tech.tokensoft.navigateed`; the teacher plist bundle id is `tech.tokensoft.navigateed.teacher`. Not listed in any `.gitignore`. |

These are client configs (an API key restricted to the app id), not the admin panel's service-account private key. Do not commit that private key.

Firebase products used: Cloud Messaging (`firebase_messaging`), Crashlytics (`firebase_crashlytics`, Gradle plugin `com.google.firebase.crashlytics` 3.0.2) and the Analytics SDK (declared, not called from Dart). Android uses Firebase BoM `31.0.2`. iOS `AppDelegate.swift` calls `FirebaseApp.configure()` if no app exists yet; `Info.plist` sets `FirebaseAppDelegateProxyEnabled` to `false` and enables the `fetch` and `remote-notification` background modes; `Runner.entitlements` contains `aps-environment` = `development`. APNs must be configured in Firebase for iOS push: https://firebase.flutter.dev/docs/messaging/apple-integration (linked from the docs page).

Android notification small icon: `@drawable/ic_launcher_foreground` (manifest meta-data `com.google.firebase.messaging.default_notification_icon` and `AwesomeNotifications().initialize('resource://drawable/ic_launcher_foreground', ...)`); the PNGs are in `android/app/src/main/res/drawable-*/`.

Both apps' notifications are sent by the admin panel through the FCM HTTP v1 API, so the admin panel must point at the same Firebase project: Settings > Notification Settings stores the project id (`project_id`) and the service account JSON (`service_account_file`), read by `admin-panel/app/Helpers/notification_helper.php`. See [Notifications](Notifications) and https://shawndaprawn.github.io/Eschool/admin-panel/notification-settings/.

Docs for the app side: https://shawndaprawn.github.io/Eschool/mobile-app/integrate-firebase/

## Branding assets

`assets/images/branding/` (declared in `pubspec.yaml` under `flutter.assets`):

| File | Used by | Student | Teacher |
|---|---|---|---|
| `appLogo.svg` | `Assets.appLogo`: splash screen and login screens | yes | yes |
| `ic_launcher.png` | `flutter_launcher_icons` `image_path` (Android and iOS launcher icon) | yes | yes |
| `ic_launcher_transparent.png` | `flutter_launcher_icons` `adaptive_icon_foreground` | yes | yes |
| `maintenance.svg` | maintenance screen | yes | yes |
| `noInternet.svg`, `fileNotFound.svg`, `somethingWentWrong.svg` | error containers | yes | yes |
| `fee_payment_due.svg` | `StudentFeePaymentDueScreen` | yes | no |

`flutter_launcher_icons` configuration (same in both `pubspec.yaml` files):

```yaml
flutter_launcher_icons:
  android: "ic_launcher"
  ios: true
  remove_alpha_ios: true
  image_path: "assets/images/branding/ic_launcher.png"
  adaptive_icon_foreground: "assets/images/branding/ic_launcher_transparent.png"
  adaptive_icon_background: "#FFFFFF"
```

Replace the files keeping their names, then run `dart run flutter_launcher_icons`. This regenerates `android/app/src/main/res/mipmap-*` and `ios/Runner/Assets.xcassets/AppIcon.appiconset/`. The notification icon (`drawable-*/ic_launcher_foreground.png`) is a separate resource; replace it by hand if needed. Other UI icons are in `assets/images/` (paths in `lib/utils/assets.dart`). Docs: https://shawndaprawn.github.io/Eschool/mobile-app/change-app-logo/

## Theme colours

Both apps use the colours of the Navigate Wealth design system, NavigateEd's sister platform, from one file in the shared package, [`packages/eschool_core/lib/src/theme/brand_colors.dart`](https://github.com/ShawnDaPrawn/Eschool/blob/main/packages/eschool_core/lib/src/theme/brand_colors.dart):

| Constant | Design system name | Value | Used as |
|---|---|---|---|
| `primary` | Primary Purple | `0xff6d28d9` | `colorScheme.primary`: buttons, links, active states. Seeds the scheme |
| `darkNavy` | Dark Navy | `0xff313653` | `colorScheme.inverseSurface`: screen headers and dark sections |
| `accent` | (Dark Navy) | `0xff313653` | Seeds the tertiary roles; `colorScheme.tertiaryContainer` exactly |
| `background` | White | `0xffffffff` | `colorScheme.surface` and `surfaceContainerLowest` |
| `lightGray` | Light Gray | `0xfff8f9fa` | `colorScheme.surfaceContainerLow`: input backgrounds, card fills |
| `text` | Black | `0xff000000` | `colorScheme.onSurface` |
| `mutedText` | Muted Gray | `0xff64748b` | `colorScheme.onSurfaceVariant`; seeds the other greys |
| `border` | Border Gray | `0x1a000000` | `colorScheme.outlineVariant` |
| `success` / `warning` / `danger` / `info` | Success Green, Warning Yellow, Error Red, (none) | `0xff22c55e`, `0xffeab308`, `0xffef4444`, `0xff3b82f6` | `StatusColors` (attendance, fees, results, the teacher's class colours); the text on each is picked for contrast. The design system has no info colour; this is Tailwind's blue-500 |

The remaining roles (containers, `outline`, the error roles) are generated by `AppTheme.lightColorScheme()` in `app_theme.dart`, and `lib/app/app.dart` in each app passes `AppTheme.light(...)` to `MaterialApp`. Change the brand in `brand_colors.dart` only, then follow [UI-Standards](UI-Standards#changing-the-brand): the contrast tests and the screenshot test in the shared package check the result. The Android launch screen is `res/drawable/launch_background.xml` (and `drawable-v21/`), referenced by `LaunchTheme` in `res/values/styles.xml` and `res/values-night/styles.xml`; `res/values/colors.xml` holds `ic_launcher_background` `#FFFFFF`. Docs: https://shawndaprawn.github.io/Eschool/mobile-app/change-app-theme/

## Font

The font is Poppins, bundled rather than downloaded: `google_fonts/` at the app root (not under `assets/`) contains the 18 `Poppins-*.ttf` files and `OFL.txt`, and is declared as the asset folder `google_fonts/` in `pubspec.yaml`. `lib/app/app.dart` uses `GoogleFonts.poppinsTextTheme(...)` and registers `google_fonts/OFL.txt` with `LicenseRegistry`. To change the font, replace the `poppinsTextTheme` call with the other family's `...TextTheme`, put that family's `.ttf` files (with the names the `google_fonts` package expects) into `google_fonts/`, and update the licence file. The docs page (https://shawndaprawn.github.io/Eschool/mobile-app/change-font/) says `assets/google_fonts/`; the folder in this repo is `google_fonts/`.

## Animations

| Setting | File | Default |
|---|---|---|
| `isApplicationItemAnimationOn` | `lib/utils/constants.dart` | `true`; `false` turns off all list/item appearance effects in `lib/utils/animationConfiguration.dart` |
| `listItemAnimationDelayInMilliseconds` | same | `100` (keep values at 10 or more, per the code comment) |
| `itemFadeAnimationDurationInMilliseconds` | same | `250` |
| `itemZoomAnimationDurationInMilliseconds` | same | `200` |
| `itemBounceScaleAnimationDurationInMilliseconds` | same | `200` |

Lottie files in `assets/animations/`: student app `onboarding.json` (login choice screen), `add_floating.json` (floating add button), `payment_process.json`, `payment_successful.json`, `payment_cancel.json` (payment verification; `payment_successful.json` also after an online exam is submitted), `music_playing.json` (`PlayVideoScreen`); teacher app `add_floating.json` only. Docs: https://shawndaprawn.github.io/Eschool/mobile-app/change-animations/

## Other constants in `lib/utils/constants.dart`

| Constant | Default | Meaning |
|---|---|---|
| `errorMessageDisplayDuration` | 3000 ms | Snack bar duration. |
| `offsetLimitPaginationAPIDefaultItemFetchLimit` | `15` | `limit` for offset/limit APIs (chat users and messages). |
| `notificationChannelKey`, `chatNotificationChannelKey` | `basic_channel`, `chat_notifications_channel` | Android notification channels. |
| `showDefaultCredentials` | `false` | Pre-fills demo logins. Student app: `defaultStudentGRNumber` `student123`, `defaultStudentPassword` `student123`, `defaultParentEmail` `parent@gmail.com`, `defaultParentPassword` `parent123`. Teacher app: `defaultTeacherEmail` `teacher@gmail.com`, `defaultTeacherPassword` `teacher123`. Keep it `false` in production. |
| `homeMenuBottomSheetAnimationDuration`, `changeSliderDuration`, `numberOfLatestNoticesInHomeScreen` | 300 ms, 5 s, 3 | Student app only. |

Chat limits are not in `constants.dart`; they come from the server's `chat_settings` (admin panel Chat Settings, https://shawndaprawn.github.io/Eschool/admin-panel/chat-settings/). The older docs page https://shawndaprawn.github.io/Eschool/mobile-app/chat-constraints/ still points to `constants.dart`.

## Languages

`lib/utils/appLanguages.dart` holds `defaultLanguageCode` (`'en'`) and the `appLanguages` list; translation files are in `assets/languages/`. Full details, including RTL and the test that checks the files: [Localization](Localization). Docs: https://shawndaprawn.github.io/Eschool/mobile-app/change-language/

## Android release signing

`android/app/build.gradle.kts` reads `rootProject.file("key.properties")`, i.e. `e-school/android/key.properties` or `e-school-teacher/android/key.properties`:

```properties
storePassword=<keystore password>
keyPassword=<key password>
keyAlias=<alias>
storeFile=<path to the .jks file>
```

- The `release` signing config uses `keyAlias`, `keyPassword`, `storeFile` (resolved with `file(...)` in the `app` module, so a relative path is relative to `android/app/`) and `storePassword`.
- The `release` build type always uses that signing config, so provide `key.properties` before running a release build.
- `key.properties`, `**/*.keystore` and `**/*.jks` are in `android/.gitignore`.
- Generating the keystore: https://shawndaprawn.github.io/Eschool/mobile-app/generate-release/

iOS signing is configured in Xcode (no `DEVELOPMENT_TEAM` is set in either project).

## Deep links

Neither app declares deep links: there are no `VIEW` intent filters with custom schemes or `autoVerify` in the Android manifests, no `CFBundleURLTypes` in `Info.plist` and no associated domains in the entitlements. The only extra intent filter is `FLUTTER_NOTIFICATION_CLICK` on `MainActivity` for notification taps. The teacher manifest also has a `<queries>` entry for `VIEW` + `https` (Android package visibility for opening web links).

## Server-side settings the apps depend on

Set in the admin panel; read by the apps through `GET /api/settings?type=app_settings` (details on [Mobile-Apps-Overview](Mobile-Apps-Overview#app-configuration-from-the-server)):

| Admin panel page | Keys | Notes |
|---|---|---|
| App Settings (`app-settings`, `SettingController::app_update`) | `app_link`, `ios_app_link`, `app_version`, `ios_app_version`, `force_app_update`, `app_maintenance`, `teacher_app_link`, `teacher_ios_app_link`, `teacher_app_version`, `teacher_ios_app_version`, `teacher_force_app_update`, `teacher_app_maintenance` | All twelve are required by the form validation. Versions are compared as `x.y.z` or `x.y.z+build`; see the force-update caveats on [Mobile-Apps-Overview](Mobile-Apps-Overview#maintenance-mode-and-force-update). Docs: https://shawndaprawn.github.io/Eschool/admin-panel/app-settings/ |
| Notification Settings | `project_id`, `service_account_file` | Same Firebase project as the apps' native files. |
| Fee / payment settings | `online_payment`, `compulsory_fee_payment_mode`, `is_student_can_pay_fees`, currency, gateway status and public keys | Student app only; see [Payments](Payments). |
| Chat Settings | `max_files_or_images_in_one_message`, `max_file_size_in_bytes` (entered in MB, multiplied by 1000000 by the API), `max_characters_in_text_message`, `automatically_messages_removed_days` | Both apps. |
| Server `.env` | `DEMO_MODE` | Sent as `is_demo`. |

## See also

- [Mobile-Apps-Overview](Mobile-Apps-Overview)
- [Building-and-Releasing-Apps](Building-and-Releasing-Apps)
- [Localization](Localization)
- [Notifications](Notifications)
- [Payments](Payments)
- [Configuration](Configuration)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)
