Common problems when installing, running and building eSchool, with the cause behind each and the fix. The last section lists known defects found in the code while this wiki was being written. They are not fixed yet. The end-user [FAQ on the documentation site](https://shawndaprawn.github.io/Eschool/faqs/) covers questions about the admin UI.

## Installation and first run

**Every page redirects to `/install`.**
The installer's `ToInstallMiddleware` runs on every web request until the file `admin-panel/storage/installed` exists. Normally the wizard's last step writes it. After a command-line install, create it yourself: `echo '{}' > storage/installed`. The file's contents are never read. See [Installation Wizard](Installation-Wizard#installed-state).

**`/install` returns 404.**
The app is already installed: `InstallerMiddleware` answers 404 once `storage/installed` exists. To reinstall, delete that file and empty the database.

**The default login from the README doesn't work.**
`admin-panel/README.md` lists `admin@admin.com` / `password`, which is out of date. `AddSuperAdminSeeder` creates **`superadmin@gmail.com` / `superadmin`**. Change the password after the first login.

**`php artisan db:seed` does nothing.**
`DatabaseSeeder` is empty. Run the seeders the installer uses, in this order: `InstallationSeeder`, `AddSuperAdminSeeder`, `emailseeder`. See [Getting Started](Getting-Started#option-b-command-line).

**Uploaded images and files return 404.**
The `public/storage` symlink is missing. Run `php artisan storage:link`. The wizard's Keys step does this, but a copied or restored install can lose the link.

**I changed `.env` and nothing happened.**
The config is cached. Run `php artisan optimize` again, or `php artisan optimize:clear` to turn caching off. Settings saved in the admin panel are not affected: they live in the database, and `SettingsConfigService` applies them on every request. See [Configuration](Configuration).

**Logos, the school name or demo mode were lost after `php artisan optimize`.**
That happened on code older than commit [e13ac7d](https://github.com/ShawnDaPrawn/Eschool/commit/e13ac7d), where views read these values with `env()`. Update to current `main`.

## Signing in from the apps

**The app can't reach the server.**
- Check `baseUrl` in `lib/utils/constants.dart`. It must be the server's address with no trailing slash. From an Android emulator, use `10.0.2.2`, not `localhost`.
- Android release builds of the student app, and every build of the teacher app, allow **HTTPS only**. iOS needs HTTPS too. Use an `https://` URL with a certificate from a public CA. See [Mobile App Configuration](Mobile-App-Configuration#https-requirement).

**Students can't sign in with their email.**
Students sign in with their **GR number** (`gr_number`), which is stored in `users.email`. Parents and teachers sign in with their email address.

**"Too many attempts" on login.**
`throttle:login` allows 5 attempts a minute per account and path, and 20 a minute per IP address (`RouteServiceProvider`). Wait a minute and try again.

**Students and parents were signed out, or can't sign in, after a new session year started.**
`EnsureStudentSessionIsActive` and `EnsureParentChildrenSessionIsActive` return 401 until the student has an active `student_sessions` row for the current session year. Promote the students into the new session year before you make it the current one. See [Academic Structure](Academic-Structure#session-middleware-for-the-apps).

**Every API call fails with code 103.**
The `session_year` setting points to a session year that doesn't exist, or isn't set. Pick a current session year in Settings.

**An expired login isn't sent back to the sign-in screen.**
When a request has no `Accept: application/json` header, Laravel answers a bad token with a 302 redirect to `/login`, not a 401 (`Authenticate::redirectTo`). The apps send only the `Authorization` header, so their log-out-on-401 handling never runs. Sign out and in again. Tokens last `SANCTUM_EXPIRATION` minutes, 30 days by default.

**The app shows "exam already attended" for something unrelated to exams.**
`CheckChild` rejects an unknown `child_id` with code 105. The student/parent app reads code 105 as the online-exam message (`errorMessageKeysAndCodes.dart`). The real cause is a child that doesn't belong to the parent signed in, often because the app still has a removed child selected.

**Error responses have `"code": null`.**
`ResponseService` looks up `config('constants.RESPONSE_CODE.EXCEPTION_ERROR')`, but there is no `config/constants.php`, so validation and permission errors carry `code: null`. Check the `error` and `message` fields instead. The HTTP status is 200 for these errors too.

## Push notifications

**Nobody gets push notifications.**
- Upload the Firebase service-account JSON and set the project ID in **System Settings → Notification Setting**. The file is stored at `storage/app/firebase/service_account.json`.
- The apps must use the same Firebase project: `google-services.json` and `GoogleService-Info.plist` in each app.

See [Notifications](Notifications#firebase-credentials).

**One user's pushes reach only one of their devices.**
Each user stores a single `fcm_id`, written at login, so the device that signed in last gets that user's pushes. A user whose `device_type` is not `android` or `ios` gets none. Broadcasts to everyone also go out on FCM topics such as `allAndroid`, `studentsIOS` and `teachersAndroid`.

## Payments

**The gateway says the payment succeeded, but the fee is still unpaid.**
Fees are marked paid only when the gateway's webhook reaches `POST /webhook/{stripe|razorpay|paystack|flutterwave}` and its signature checks out. Check three things:
- The webhook URL is registered in the gateway dashboard.
- The webhook secret is saved in the panel. For Flutterwave, that means the secret hash, which is required.
- The server can be reached from the internet over HTTPS.

See [Payments](Payments#payment-confirmation-webhooks).

**Pending payments turn into failed ones.**
Transactions left pending for more than an hour are marked failed whenever the fee details or transaction list are loaded. Start a new payment.

**Paystack or Flutterwave checkout never closes in the app.**
The app's webview waits for a redirect to `/response/{paystack|flutterwave}/success` on the `baseUrl` host. It never finishes if the panel is served from a different host than `baseUrl`.

## Building the apps

**The Android build fails with a google-services error.**
`build.gradle.kts` applies the `com.google.gms.google-services` and Crashlytics plugins. Each app already includes `android/app/google-services.json` for Firebase project `navigateed-d8c06`. The build fails if that file is missing, or if `applicationId` is no longer `tech.tokensoft.navigateed` / `tech.tokensoft.navigateed.teacher`. On iOS, `project.pbxproj` expects `ios/Runner/GoogleService-Info.plist`, which is committed for the same project.

**The release build fails on signing.**
The release build type always uses the signing config read from `android/key.properties`, which is gitignored. Create it from your keystore. See [Mobile App Configuration](Mobile-App-Configuration#android-release-signing).

**Gradle runs out of memory, or the machine slows to a crawl.**
`android/gradle.properties` sets `-Xmx8G`. Lower it on machines with less RAM.

**The force-update dialog appears on every launch, or never.**
`UiUtils.forceUpdate` compares the installed `version+build` with `app_version` / `ios_app_version` from the server:
- The iOS projects hard-code `FLUTTER_BUILD_NUMBER = 3`, so a server value with a build number such as `+39` forces an update on iOS every time.
- Version parts are compared one by one, so give the full `x.y.z` form.

See [Building and Releasing Apps](Building-and-Releasing-Apps#versioning).

**`flutter test` fails after I edit translations.**
`test/translations_test.dart` requires every language file to have exactly the keys in `en.json`. Add the new key to every file in `assets/languages/`. See [Localization](Localization).

## Admin panel

**A language falls back to English after its code was edited.**
`LanguageController::update` renames the language without moving its JSON file. Upload the file again after you change a language code.

## Known defects

These were found by reading the code while this wiki was written. None of them is fixed in `main` yet. Each entry names the file to start from.

| Area | Symptom | Where |
|---|---|---|
| Events | Both apps call `get-events-details` with GET, but the route is registered as POST only, so the event details screen gets HTTP 405 | `routes/api.php`, `e-school/lib/data/repositories/systemInfoRepository.dart` |
| API errors | Validation and permission errors return `code: null` because `config/constants.php` doesn't exist | `app/Services/ResponseService.php` |
| API auth | An expired token gets a 302 redirect instead of a 401, so the apps don't sign out | `app/Http/Middleware/Authenticate.php`, `lib/utils/api.dart` in both apps |
| API headers | `ResponseService` sends its response and calls `exit()`, so those responses skip the `SecurityHeaders`, CORS and rate-limit headers | `app/Services/ResponseService.php` |
| Parent app | Code 105 from `CheckChild` is shown as "exam already attended" | `e-school/lib/utils/errorMessageKeysAndCodes.dart` |
| Teacher marks | Re-submitting exam marks stops at the first existing row, because a success response exits inside the loop | `TeacherApiController::submitExamMarksBySubjects`, `submitExamMarksByStudent` |
| Teacher attendance | Absence notifications are saved only for the last absent student's parents | `TeacherApiController::submitAttendance` |
| Files | Physical files are not deleted with their record, because `File::deleting` checks the URL accessor rather than the disk path | `app/Models/File.php` |
| Student profile | `get-profile-data` can fail with an undefined `$child` in compulsory-fee mode without installments after the free-use date | `StudentApiController::getProfileDetails` |
| Receipts | The student receipt endpoint uses the student's first `fees_paids` row from any session year | `StudentApiController::feesPaidReceiptPDF` |
| Teacher app | `Api.examResults` points to `teacher/exam-marks`, which has no route. The method that uses it is never called | `e-school-teacher/lib/utils/api.dart` |

The [API Parent](API-Parent), [API Student](API-Student) and [API Teacher](API-Teacher) pages list more endpoint-level quirks in their "Known quirks" and "Gotchas" sections.

## See also

- [Getting Started](Getting-Started)
- [Configuration](Configuration)
- [Deployment](Deployment)
- [API Overview](API-Overview#gotchas)
- [Security Model](Security-Model)
