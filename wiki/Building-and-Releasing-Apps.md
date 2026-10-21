This page explains how to run, check and build the two Flutter apps (`e-school/` and `e-school-teacher/`) locally and for release: toolchain versions, the commands CI runs, the Android Gradle (Kotlin DSL) setup, iOS CocoaPods and Xcode settings, and how version numbers flow into the store builds. Configuration values you must set first are on [Mobile-App-Configuration](Mobile-App-Configuration).

## Toolchain

| Item | Value | Source |
|---|---|---|
| Flutter | 3.44.9, stable channel | `.github/workflows/ci.yml` (`subosito/flutter-action@v2`) |
| Minimum Flutter / Dart from the lockfile | `flutter: >=3.44.0`, `dart: >=3.12.0 <4.0.0` | `pubspec.lock` `sdks:` |
| Dart constraint in `pubspec.yaml` | `>=3.8.0 <4.0.0` | `environment.sdk` |
| Android Gradle Plugin | `com.android.application` 9.0.1 | `android/settings.gradle.kts` |
| Kotlin Gradle plugin | `org.jetbrains.kotlin.android` 2.3.20 | `android/settings.gradle.kts` |
| Google services plugin | `com.google.gms.google-services` 4.4.2 | `android/settings.gradle.kts` |
| Crashlytics Gradle plugin | `com.google.firebase.crashlytics` 3.0.2 | `android/settings.gradle.kts` |
| Gradle wrapper | 9.1.0 (`gradle-9.1.0-all.zip`) | `android/gradle/wrapper/gradle-wrapper.properties` |
| Java / Kotlin JVM target | 17 | `compileOptions` and `kotlin.compilerOptions.jvmTarget` in `android/app/build.gradle.kts` |
| Gradle JVM heap | `-Xmx8G -XX:MaxMetaspaceSize=4G` | `android/gradle.properties` |
| iOS platform (CocoaPods) | `platform :ios, '15.0'`, `use_frameworks!` | `ios/Podfile` |

The docs site's setup guide: https://shawndaprawn.github.io/Eschool/mobile-app/setup-flutter/

## Running locally

```bash
cd e-school            # or e-school-teacher
flutter pub get --enforce-lockfile
flutter run
```

Before the first run:

1. Choose the server. A plain `flutter run` talks to the production server; for another one pass `--dart-define=API_BASE_URL=...` (add `--dart-define=ENV=local` for an `http://` address) or `--dart-define-from-file=config/staging.json` ([Mobile-App-Configuration](Mobile-App-Configuration#build-settings)). Without a reachable server the app stays on the splash screen error.
2. `android/app/google-services.json` and `ios/Runner/GoogleService-Info.plist` are already committed for Firebase project `navigateed-d8c06`. Android builds fail if the JSON is removed, because the Google services Gradle plugin is applied, and the Xcode project lists the plist as a resource.
3. For iOS, `flutter pub get` must run before `pod install`: the `Podfile` reads `ios/Flutter/Generated.xcconfig` and raises an error if it is missing.
4. For a local `http://` server: the student app's debug build allows cleartext (debug manifest and `src/debug/res/xml/network_security_config.xml`); the teacher app has no such override, so use HTTPS or add one locally. iOS requires HTTPS in both apps.

`--enforce-lockfile` makes `pub get` fail instead of changing `pubspec.lock`, which is what CI does. Each app's `README.md` lists recovery commands (`flutter clean`, `flutter pub cache repair`, removing `ios/Pods` and `Podfile.lock`, `pod deintegrate`, `pod install`). Docs: https://shawndaprawn.github.io/Eschool/mobile-app/run-app/

## What CI checks

Job `apps` in [.github/workflows/ci.yml](https://github.com/ShawnDaPrawn/Eschool/blob/main/.github/workflows/ci.yml), run on pushes to `main`, pull requests, a weekly schedule (`17 5 * * 1`) and manual dispatch. It is a matrix over `[e-school, e-school-teacher]` with `fail-fast: false`, working directory set to the app folder:

```bash
flutter pub get --enforce-lockfile
flutter analyze --no-fatal-infos   # info-level lints are reported, warnings and errors fail
flutter test
```

`flutter test` runs `test/translations_test.dart`, the only test in each app, which checks that `appLanguages` and `assets/languages/*.json` match and that every language file has exactly the keys of `en.json` ([Localization](Localization#the-translations-test)).

CI does not build APKs, app bundles or iOS archives, and it never needs the Firebase files: `flutter analyze` and `flutter test` do not run Gradle or Xcode. A green CI run therefore says nothing about whether the native build succeeds. See [Testing-and-CI](Testing-and-CI) for the other jobs.

To reproduce CI locally:

```bash
cd e-school && flutter pub get --enforce-lockfile && flutter analyze --no-fatal-infos && flutter test
cd ../e-school-teacher && flutter pub get --enforce-lockfile && flutter analyze --no-fatal-infos && flutter test
```

## Android build

Since 3.5.0 both apps use the Gradle Kotlin DSL (`build.gradle.kts`, `settings.gradle.kts`); the Groovy files and the student app's `FlutterMultiDexApplication.java` were removed.

### `android/app/build.gradle.kts`

| Setting | Student app | Teacher app |
|---|---|---|
| Plugins | `com.android.application`, `dev.flutter.flutter-gradle-plugin`, `com.google.gms.google-services`, `com.google.firebase.crashlytics` | same |
| `namespace` / `applicationId` | `tech.tokensoft.navigateed` | `tech.tokensoft.navigateed.teacher` |
| `compileSdk` | `flutter.compileSdkVersion` | `flutter.compileSdkVersion` |
| `minSdk` / `targetSdk` | `flutter.minSdkVersion` / `flutter.targetSdkVersion` | same |
| `versionCode` / `versionName` | `flutter.versionCode` / `flutter.versionName` (from `pubspec.yaml`) | same |
| `ndkVersion` | `flutter.ndkVersion` | `"28.2.13676358"` |
| `externalNativeBuild.cmake.version` | not set | `"3.31.5"` |
| `lint.checkReleaseBuilds` | `false` (avoids fetching `play-services-tapandpay`, which the Stripe Android SDK references but is not in public Maven repositories) | not set |
| Extra dependencies | multidex 2.0.1, Firebase BoM 31.0.2 + `firebase-messaging`, `androidx.emoji2` 1.4.0 (three artifacts) | same |

`compileSdk`, `minSdk` and `targetSdk` are not pinned in the repository: they are the defaults of the Flutter Gradle plugin of the Flutter SDK you build with (3.44.x in CI). Run `flutter build apk` once and read the merged manifest, or check the Flutter SDK's `FlutterExtension`, if you need the exact numbers for a store listing. To pin them, replace the `flutter.*` references with numbers.

`android/gradle.properties` also sets `android.useAndroidX=true`, `android.newDsl=false` and `android.builtInKotlin=false` (added by the Flutter template). `android/build.gradle.kts` moves the build directory to `../../build` (the app's `build/` folder) and defines `clean`.

### Release build type

```kotlin
buildTypes {
    release {
        signingConfig = signingConfigs.getByName("release")
        isMinifyEnabled = true
        isShrinkResources = true
        proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
    }
}
```

The `release` signing config is filled from `android/key.properties` (`keyAlias`, `keyPassword`, `storeFile`, `storePassword`); see [Mobile-App-Configuration](Mobile-App-Configuration#android-release-signing). R8 minification and resource shrinking are on.

`android/app/proguard-rules.pro` (identical in both apps):

| Rule | Purpose |
|---|---|
| `-keep class io.flutter.**` (and `app`, `plugin`, `util`, `view`, `plugins` sub-packages), `-dontwarn io.flutter.embedding.**` | Flutter embedding. |
| `-keep class com.google.firebase.** { *; }` | Firebase. |
| `-ignorewarnings` | Suppresses all R8 warnings. |

### Building

```bash
flutter build apk                 # universal APK
flutter build apk --split-per-abi # per-ABI APKs (README)
flutter build appbundle           # AAB for Google Play
```

Every build command takes the environment's define file, so staging and production builds differ only by it: `flutter build appbundle --dart-define-from-file=config/staging.json` (or `config/production.json`). Without one, the build uses the production server ([Mobile-App-Configuration](Mobile-App-Configuration#build-settings)).

Outputs go to `build/app/outputs/` inside the app folder. Docs: https://shawndaprawn.github.io/Eschool/mobile-app/generate-release/

Manifest facts relevant to Play review: `READ_EXTERNAL_STORAGE` and `WRITE_EXTERNAL_STORAGE` are requested (with `requestLegacyExternalStorage="true"`), `READ_MEDIA_IMAGES`, `READ_MEDIA_VIDEO` and `com.google.android.gms.permission.AD_ID` are removed with `tools:node="remove"`, and backups are disabled (`allowBackup="false"`, `data_extraction_rules.xml`).

## iOS build

| Setting | Student app | Teacher app |
|---|---|---|
| `ios/Podfile` | `platform :ios, '15.0'`, `use_frameworks!`, Flutter's `flutter_install_all_ios_pods` / `flutter_additional_ios_build_settings` | same |
| `IPHONEOS_DEPLOYMENT_TARGET` (project level) | 13.0 | 13.0 |
| `IPHONEOS_DEPLOYMENT_TARGET` (Runner target) | 15.0 | not set, so 13.0 is inherited |
| `PRODUCT_BUNDLE_IDENTIFIER` | `tech.tokensoft.navigateed` | `tech.tokensoft.navigateed.teacher` |
| `SWIFT_VERSION` | 5.0 | 5.0 |
| `CODE_SIGN_ENTITLEMENTS` | `Runner/Runner.entitlements` (`aps-environment` = `development`) | same |
| Resources | `Runner/GoogleService-Info.plist` committed for `navigateed-d8c06` | same, teacher bundle id |
| `Info.plist` usage strings | camera, microphone, photo library | camera, photo library |
| Background modes | `fetch`, `remote-notification` | same |

The teacher project's Runner target stays at iOS 13.0 while the Podfile targets 15.0. If Xcode reports that a pod module has a higher minimum deployment target than the app, set the Runner target's deployment target to 15.0 as in the student project.

Typical release sequence (standard Flutter tooling; the docs site defers to https://flutter.dev/docs/deployment/ios):

```bash
flutter pub get --enforce-lockfile
cd ios && pod install && cd ..
flutter build ipa
```

Set the signing team in Xcode (no `DEVELOPMENT_TEAM` is committed) and register the bundle id and push capability in the Apple developer account.

## Versioning

| Place | Student app | Teacher app | Notes |
|---|---|---|---|
| `pubspec.yaml` `version` | `3.5.0+39` | `3.5.0+32` | `name+build`. Android `versionName`/`versionCode` come from here through `flutter.versionName` / `flutter.versionCode`. |
| `lib/main.dart` comment | `///v-3.5.0` | `///v-3.5.0` | informational |
| `ios/Runner/Info.plist` | `CFBundleShortVersionString = $(FLUTTER_BUILD_NAME)`, `CFBundleVersion = $(FLUTTER_BUILD_NUMBER)` | same | |
| `ios/Runner.xcodeproj/project.pbxproj` (Runner target) | `FLUTTER_BUILD_NAME = 3.5.0`, `FLUTTER_BUILD_NUMBER = 3`, `MARKETING_VERSION = 3.5.0`, `CURRENT_PROJECT_VERSION = 3` | same | |

Flutter writes `FLUTTER_BUILD_NAME` and `FLUTTER_BUILD_NUMBER` from `pubspec.yaml` (or `--build-name` / `--build-number`) into `ios/Flutter/Generated.xcconfig`, which `Release.xcconfig` includes. Xcode gives build settings defined on the target precedence over the target's base `.xcconfig`, so the hard-coded `FLUTTER_BUILD_NUMBER = 3` in both projects wins and iOS builds report build number 3 regardless of `pubspec.yaml`. Delete those two target-level settings (or keep them in sync) before uploading a new iOS build, and make sure the admin panel's `ios_app_version` / `teacher_ios_app_version` matches what the installed app actually reports, otherwise the force-update dialog can appear on every launch ([Mobile-Apps-Overview](Mobile-Apps-Overview#maintenance-mode-and-force-update)).

Release checklist:

1. Bump `version` in `pubspec.yaml` (build number must increase for both stores).
2. Fix or remove the iOS target-level `FLUTTER_BUILD_*` values as above.
3. Build and upload.
4. After the store release is live, update App Settings in the admin panel: `app_version` / `ios_app_version` (or the `teacher_*` keys) to the exact released value, then optionally set `force_app_update` to `1`.

Version history of the apps is on [Release-History](Release-History).

## See also

- [Mobile-App-Configuration](Mobile-App-Configuration)
- [Mobile-Apps-Overview](Mobile-Apps-Overview)
- [Testing-and-CI](Testing-and-CI)
- [Localization](Localization)
- [Release-History](Release-History)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)
